<?php

ini_set('display_errors',1);
ini_set('display_startup_errors',1);
header('Access-Control-Allow-Headers: Access-Control-Allow-Origin, Content-Type, Authorization');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET,PUT,POST,DELETE,PATCH,OPTIONS');
header('Content-type: application/json');

require_once('/var/www/classes/class.XRDB.php');
$X=new XRDB();

$data = file_get_contents("php://input");
$data = json_decode($data, TRUE);

//$sql="select * from nua_user where id = " . $data['uid'];
//$user=$X->sql($sql);
//$role=$user[[0]['role'];


$output='[
{
		"id": "home",
		"title": "Advisors",
		"subtitle": "",
		"type": "group",
		"icon": "heroicons_outline:home",	
		"children": [
{ "id": "db", "title": "Home", "type": "basic", "icon": "heroicons_outline:clipboard-check", "link": "/sadmin" },
{ "id": "db", "title": "Facility Management", "type": "basic", "icon": "heroicons_outline:clipboard-check", "link": "/facility-list" },
{ "id": "db", "title": "Stakeholders", "type": "basic", "icon": "heroicons_outline:clipboard-check", "link": "/stakeholder-list" },
{ "id": "db", "title": "Assessments", "type": "basic", "icon": "heroicons_outline:clipboard-check", "link": "/assessment-list" },
{ "id": "db", "title": "Administration", "type": "basic", "icon": "heroicons_outline:clipboard-check", "link": "/template-list" },
{ "id": "dbi", "title": "Account", "type": "basic", "icon": "heroicons_outline:clipboard-check", "link": "/account-management" }
]
}
]';

   $arr=json_decode($output,true);
   $output=array();
   $output['default']=$arr;
   $output['compact']=array();
   $output['futuristic']=array();
   $output['horizontal']=array();   
  echo json_encode($output);

?>
