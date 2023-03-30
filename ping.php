<?php
ini_set('display_errors',1);
ini_set('display_startup_errors',1);
header('Access-Control-Allow-Headers: Access-Control-Allow-Origin, Content-Type, Authorization');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET,PUT,POST,DELETE,PATCH,OPTIONS');
header('Content-type: application/json');

$output=array();


$data = file_get_contents("php://input");
$data = json_decode($data, TRUE);
$aa=explode("/",$data['path']);
$output=array();
$output['id']="";
$output['id2']="";
$output['id3']="";

if (isset($aa[1])) {
     if (isset($aa[2])) {
         $output['id']=$aa[2];
         }
     if (isset($aa[3])) {
         $output['id2']=$aa[3];
         }
         if (isset($aa[4])) {
         $output['id3']=$aa[4];
         }
}

$o=array();
$o=str_replace('null','""',json_encode($output, JSON_HEX_TAG |
        JSON_HEX_APOS |
        JSON_HEX_QUOT |
        JSON_HEX_AMP |
        JSON_UNESCAPED_UNICODE));

echo $o;

?>
