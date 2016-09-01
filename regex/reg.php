<?php
//1.解析正则表达式
//2.生成跳转表
//3.确认是否匹配

/*
 * 字母
 * alpha : abcdefghijklmnopqrstuvwxyz
 * 数字
 * digit : 0123456789
 * 原子字符
 * ch : alpha | digit
 * 表达式
 * expr : ( or ) | ch
 * *表达式
 * star : expr starRest
 * starRest : * | emp
 * 连接表达式
 * cat : star catRest
 * catRest : star catRest | emp
 * 或表达式
 * or : cat orRest
 * orRest : '|' cat orRest | emp
 */

class Reg {
	private $_str = '';
	private $_readPos = 0;
	private $_nfa;
	
	public function __construct($str) {
		$this->_str = strval($str);
		$this->_nfa = new NFA();
		$this->orExpr();
	}

	public function test($str) {
		return $this->_nfa->run($str);
	}

	public function go() {
		$this->_readPos ++;
	}

	public function back() {
		$this->_readPos = max(0, $this->_readPos-1);
	}

	public function read() {
		if($this->_readPos < strlen($this->_str)) {
			return $this->_str[$this->_readPos];
		}
		return null;
	}

	public function match($ch) {
		$currentCh = $this->read();
		if($ch == $currentCh) {
			$this->_log('match '.$ch);
			$this->go();
			return true;
		}
		throw new Exception("Syntax Error : Unexpected '{$currentCh}', '{$ch}' expected");
	} 

	public function alpha() {
		$this->_log('Entry alpha');
		$ch = $this->read();
		if(!$this->_isAlpha($ch)) {
			throw new Exception("Syntax Error : Unexpected '{$ch}', alpha expected");
		}
		$this->_log('find '.$ch);
		$this->go();
		$this->_log('Leave alpha');
		$status = new NFAStatus();
		$this->_nfa->addJump($status->start, $status->end, $ch);
		$this->_nfa->setStartStatus($status->start);
		$this->_nfa->setEndStatus($status->end);
		$this->_log('Leave alpha');
		return $status;
	}

	public function digit() {
		$this->_log('Entry digit');
		$ch = $this->read();
		if(!$this->_isDigit($ch)) {
			throw new Exception("Syntax Error : Unexpected '{$ch}', digit expected");
		}
		$this->_log('find '.$ch);
		$this->go();
		$this->_log('Leave digit');
		$status = new NFAStatus();
		$this->_nfa->addJump($status->start, $status->end, $ch);
		$this->_nfa->setStartStatus($status->start);
		$this->_nfa->setEndStatus($status->end);
		$this->_log('Leave digit');
		return $status;
	}

	public function ch() {
		$this->_log('Entry ch');
		$ch = $this->read();
		if($this->_isDigit($ch)) {
			$status = $this->digit();
		}
		else if($this->_isAlpha($ch)) {
			$status = $this->alpha();
		}
		else {
			throw new Exception("Syntax Error : Unexpected '{$ch}', alpha or digit expected");
		}
		$this->_nfa->setStartStatus($status->start);
		$this->_nfa->setEndStatus($status->end);
		$this->_log('Leave ch');
		return $status;
	}

	public function expr() {
		$this->_log('Entry expr');
		if($this->read() == '(') {
			$this->match('(');$status = $this->orExpr();$this->match(')');
		}
		else {
			$status = $this->ch();
		}
		$this->_nfa->setStartStatus($status->start);
		$this->_nfa->setEndStatus($status->end);
		$this->_log('Leave expr');
		return $status;
	}

	public function star() {
		$this->_log('Entry star');
		$status = $this->expr();
		if($this->read() == '*') {
			$this->match('*');
			$this->_nfa->addJump($status->start, $status->end, NFA::EMP);
			$this->_nfa->addJump($status->end, $status->start, NFA::EMP);
		}
		$this->_nfa->setStartStatus($status->start);
		$this->_nfa->setEndStatus($status->end);
		$this->_log('Leave star');
		return $status;
	}

	public function cat() {
		$this->_log('Entry cat');
		$status = $this->star();
		while(true) {
			if(in_array($this->read(), array('|',')', null), true)) break;
			$starStatus = $this->star();
			$this->_nfa->addJump($status->end, $starStatus->start, NFA::EMP);
			$status->end = $starStatus->end;
		}
		$this->_nfa->setStartStatus($status->start);
		$this->_nfa->setEndStatus($status->end);
		$this->_log('Leave cat');
		return $status;
	}

	public function orExpr() {
		$this->_log('Entry or');
		$status = $this->cat();
		while(true) {
			if($this->read() == '|'){
				$this->match('|');
				$catStatus = $this->cat();
				$orStatus = new NFAStatus();
				$this->_nfa->addJump($orStatus->start, $status->start, NFA::EMP);
				$this->_nfa->addJump($orStatus->start, $catStatus->start, NFA::EMP);
				$this->_nfa->addJump($status->end, $orStatus->end, NFA::EMP);
				$this->_nfa->addJump($catStatus->end, $orStatus->end, NFA::EMP);
				$status = $orStatus;
			}
			else {
				break;
			}
		}
		$this->_nfa->setStartStatus($status->start);
		$this->_nfa->setEndStatus($status->end);
		$this->_log('Leave or');
		return $status;
	}

	private function _isAlpha($ch) {
		return ord($ch) >= ord('a') && ord($ch) <= ord('z');
	}

	private function _isDigit($ch) {
		return ord($ch) >= ord('0') && ord($ch) <= ord('9');
	}

	private function _log($str) {
		//var_dump($str);
	}
}

class NFAStatus {
	public $start;
	public $end;
	public function __construct() {
		$this->start = self::getNextStatusId();
		$this->end = self::getNextStatusId();
	}

	public static function getNextStatusId() {
		static $id = 1;
		return $id++;
	}
}

class NFA {
	const EMP = -1;
	protected $_jumpTable = array();
	protected $_startStatus = null;
	protected $_endStatus = null;
	protected $_statusList = array();
	public function addJump($start, $end, $char) {
		if(!isset($this->_jumpTable[$start])) {
			$this->_jumpTable[$start] = array();
		}
		if(!isset($this->_jumpTable[$start][$char])) {
			$this->_jumpTable[$start][$char] = array();
		}
		$this->_jumpTable[$start][$char][$end] = $end;
	}
	public function setStartStatus($status) {
		$this->_startStatus = $status;
	}
	public function setEndStatus($status) {
		$this->_endStatus = $status;
	}
	public function run($str) {
		//计算start status的empClosure
		//对每个char，计算move和它的empClosure
		//char结束后，看是否在endStatus里面
		$str = strval($str);
		$this->_statusList = array();
		$this->_addStatus($this->_startStatus);
		$len = strlen($str);
		for($i = 0; $i < $len; $i++) {
			$ch = $str[$i];
			$oldStatusList = $this->_statusList;
			$this->_statusList = array();
			foreach($oldStatusList as $status) {
				$jumpStatusList = $this->_move($status, $ch);
				foreach($jumpStatusList as $jumpStatus) {
					$this->_addStatus($jumpStatus);
				}
			}
		}
		return isset($this->_statusList[$this->_endStatus]);
	}
	protected function _addStatus($status) {
		if(!isset($this->_statusList[$status])) {
			$this->_statusList[$status] = $status;
			foreach($this->_move($status, self::EMP) as $status) {
				if(!isset($this->_statusList[$status])) {
					$this->_addStatus($status);
				}
			}
		}
	}
	protected function _move($status, $ch) {
		if(isset($this->_jumpTable[$status][$ch])) {
			return $this->_jumpTable[$status][$ch];
		}
		return array();
	}
}