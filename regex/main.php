<?php
require_once(__DIR__.'/reg.php');

$testStr = array(
	'abc',
	'abde',
	'a1b23',
);

$testReg = array(
		'[a-zA-Z]*',
		'[a-z]'
);

$reg = new Reg('abc|123|abc(zsd)213|a*b*c*|123a*(c|d)*345');
