<?php
ini_set('display_errors',1);
ini_set('display_startup_errors',1);
header('Access-Control-Allow-Headers: Access-Control-Allow-Origin, Content-Type, Authorization');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET,PUT,POST,DELETE,PATCH,OPTIONS');
require_once('/var/www/classes/class.PSDB.php');

$output=array();
$X=new PSDB();
$table=$_GET['id'];

$cols=$X->get_cols($table);
$title="desc-".str_replace('_','-',$table);
$prefix=str_replace('-','',$title);

echo '<code>';
echo '"' . $title . '": { <br>';
echo ' "scope": "html", <br>';
echo ' "prefix": "' . $prefix . '", <br>';
echo ' "body": [<br>';
echo ' "&lt;&excl;--",<br>';
foreach ($cols as $c) {
   echo ' "' . $c['Field'] . ' ' . $c['Type'] . '", <br>';
}
echo '"--&gt;",<br>';
echo ' ], "description": "' . $title . '" },<br>';
echo '</code>';

?>
