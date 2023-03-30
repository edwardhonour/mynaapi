<?php
ini_set('display_errors',1);
ini_set('display_startup_errors',1);
header('Access-Control-Allow-Headers: Access-Control-Allow-Origin, Content-Type, Authorization');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET,PUT,POST,DELETE,PATCH,OPTIONS');
header('Content-type: application/json');
require_once('/var/www/classes/class.PSDB.php');

$output=array();

$data = file_get_contents("php://input");
$data = json_decode($data, TRUE);

$X = new PSDB();

$post=array();
$d=$data['data'];

foreach($d as $name=>$value) {
   if ($name!='submit'&&$name!='create_timestamp') {
       $post[$name]=$value;
   }
}
$id=$X->post($post);
$output=array();
$output['error_code']=0;
$output['id']=$id;
$o=str_replace('null','""',json_encode($output, JSON_HEX_TAG |
        JSON_HEX_APOS |
        JSON_HEX_QUOT |
        JSON_HEX_AMP |
        JSON_UNESCAPED_UNICODE));

echo $o;

?>
