<?php
require_once(__DIR__.'/reg.php');

$testData = array(
	//test char
	'a' => array(
		'a'=>true,
		'b'=>false,
	),
	//test digit
	'4' => array(
		'4'=>true,
		'9'=>false,
	),
	//test cat
	'abc123'=>array(
		'abc123'=>true,
		'ab23'=>false,
		'abc1234'=>false,
		'abc122'=>false,
	),
	//test or
	'a1b2|x3y4'=>array(
		'a1b2'=>true,
		'x3y4'=>true,
		'a1b2x3y4'=>false,
		'a1x3y4'=>false,
		'a1b2x3'=>false,
	),
	//test star
	'ab*c'=>array(
		'ac'=>true,
		'abc'=>true,
		'abbc'=>true,
		'ab'=>false,
		'bc'=>false,
	),
	//test brackets
	'a(b|c)*dde'=>array(
		'abcdde'=>true,
		'adde'=>true,
		'abcbcbdde'=>true,
		'abcbcbcbce'=>false,
	),
	//test repeat
	'a(a|b)*aabba'=>array(
		'aabba'=>false,
		'aaabba'=>true,
		'aabbaaabba'=>true,
		'aabbaa'=>false,
	),
);

foreach($testData as $regStr=>$testList) {
	$reg = new Reg($regStr);
	foreach($testList as $test=>$res) {
		$testRes = $reg->test($test);
		printf("%-20s%-15s%-10s%-10s\n", $regStr, $test, $testRes?'MATCH':'MISMATCH', ($res == $testRes)?'PASS':'FAIL');
	}
}