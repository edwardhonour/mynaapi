<?php
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

$id=$data['id'];
$sql=$data['sql'];
$sql=str_replace(':id',$id,$sql);

$X = new PSDB();

$output=array();
$output=$X->sql($sql);

$o=array();
$o=str_replace('null','""',json_encode($output, JSON_HEX_TAG |
        JSON_HEX_APOS |
        JSON_HEX_QUOT |
        JSON_HEX_AMP |
        JSON_UNESCAPED_UNICODE));

echo $o;

?>
