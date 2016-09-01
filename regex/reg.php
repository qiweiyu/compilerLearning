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
	
	public function __construct($str) {
		$this->_str = $str;
		$this->orExpr();
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
		if($this->_isAlpha($ch)) {
			$this->_log('find '.$ch);
			$this->go();
			$this->_log('Leave alpha');
			return true;
		}
		throw new Exception("Syntax Error : Unexpected '{$ch}', alpha expected");
	}

	public function digit() {
		$this->_log('Entry digit');
		$ch = $this->read();
		if($this->_isDigit($ch)) {
			$this->_log('find '.$ch);
			$this->go();
			$this->_log('Leave digit');
			return true;
		}
		throw new Exception("Syntax Error : Unexpected '{$ch}', digit expected");
	}

	public function ch() {
		$this->_log('Entry ch');
		$ch = $this->read();
		if($this->_isDigit($ch)) {
			$this->digit();
		}
		else if($this->_isAlpha($ch)) {
			$this->alpha();
		}
		else {
			throw new Exception("Syntax Error : Unexpected '{$ch}', alpha or digit expected");
		}
		$this->_log('Leave ch');
	}

	public function expr() {
		$this->_log('Entry expr');
		if($this->read() == '(') {
			$this->match('(');$this->orExpr();$this->match(')');
		}
		else {
			$this->ch();
		}
		$this->_log('Leave expr');
	}

	public function star() {
		$this->_log('Entry star');
		$this->expr();
		if($this->read() == '*') {
			$this->match('*');
		}
		$this->_log('Leave star');
	}

	public function cat() {
		$this->_log('Entry cat');
		$this->star();
		while(true) {
			if(in_array($this->read(), array('|',')', null), true)) break;
			$this->star();
		}
		$this->_log('Leave cat');
	}

	public function orExpr() {
		$this->_log('Entry or');
		$this->cat();
		while(true) {
			if($this->read() == '|'){
				$this->match('|');$this->cat();
			}
			else {
				break;
			}
		}
		$this->_log('Leave or');
	}


	private function _isAlpha($ch) {
		return ord($ch) >= ord('a') && ord($ch) <= ord('z');
	}

	private function _isDigit($ch) {
		return ord($ch) >= ord('0') && ord($ch) <= ord('9');
	}

	private function _log($str) {
		var_dump($str);
	}
}
