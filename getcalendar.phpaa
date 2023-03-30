<?php
//--
//-- getselect.php
//-- Copyright 2023 - SQLLabs
//--
//-- Get select box data from a sql query.
//--
ini_set('display_errors',1);
ini_set('display_startup_errors',1);
header('Access-Control-Allow-Headers: Access-Control-Allow-Origin, Content-Type, Authorization');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET,PUT,POST,DELETE,PATCH,OPTIONS');
header('Content-type: application/json');
require_once('/var/www/classes/class.PSDB.php');

if (isset($_COOKIE['uid'])) { $uid=$_COOKIE['uid']; } else { $uid=55009; }

$output=array();

$data = file_get_contents("php://input");
$data = json_decode($data, TRUE);

$q=$data['q'];
$sql=$data['sql'];
$formData=$data['form'];

$X = new PSDB();

$output=array();

if ($q=='sql'||$q=='') {
	$out=$X->sql($sql);
	foreach ($out as $o2) {
	    $o3=array();
	    $i=0;
	    foreach($o2 as $name=>$value) {
	        if ($i==0) $o3['id']=$value;
		if ($i==1) $o3['option']=$value;
		$i++;
	    }
            array_push($output,$o3);
	}
} else {

}

$o=array();
$o=str_replace('null','""',json_encode($output, JSON_HEX_TAG |
        JSON_HEX_APOS |
        JSON_HEX_QUOT |
        JSON_HEX_AMP |
        JSON_UNESCAPED_UNICODE));

echo $o;

?>
