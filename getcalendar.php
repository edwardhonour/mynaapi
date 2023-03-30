<?php
//--
//-- getselect.php
//-- Copyright 2023 - SQLLabs
//--
//-- Get calendar data from a sql query.
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
$params=$data['params'];

$X = new PSDB();

$output=array();
/*
if ($q=='sql'||$q=='') {
	$out=$X->sql($sql);
	foreach ($out as $o2) {
	    $o3=array();
	    $i=0;
	    foreach($o2 as $name=>$value) {
	        if ($i==0) $o3['id']=$value;
		if ($i==1) $o3['title']=$value;
		if ($i==2) $o3['date']=$value;
		if ($i==3) $o3['color']=$value;
		$i++;
	    }
            array_push($output,$o3);
	}
} else {

}
 */
$output=array();
$o=array();
$o['id']=12;
$o['title']="Inspection - IL2002";
$o['date']='2023-03-15';
array_push($output,$o);

$o=array();
$o=str_replace('null','""',json_encode($output, JSON_HEX_TAG |
        JSON_HEX_APOS |
        JSON_HEX_QUOT |
        JSON_HEX_AMP |
        JSON_UNESCAPED_UNICODE));

echo $o;

?>
