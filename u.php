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
if ($data['uid']=="") $data['uid']='0';
	
$sql="select * from nua_user where id = " . $data['uid'];
$user=$X->sql($sql);
if (sizeof($user)==0) {
$user='{
    "id"    : "cfaad35d-07a3-4447-a6c3-d8c3d54fd5df",
    "name"  : "User Not Found",
    "email" : "nouser@nuaxess.org",
    "avatar": "assets/images/avatars/brian-hughes.jpg",
    "status": "online"
}';
 $arr=json_decode($user,true);  
 echo json_encode($arr);
} else {
   $u=array();
   $u['id']= "cfaad35d-07a3-4447-a6c3-d8c3d54fd5df";
   $u['name']=$user[0]['first_name'] . " " . $user[0]['last_name'];
   $u['email']=$user[0]['email'];
   if ($user[0]['avatar']!="") {
		$u['avatar']=$user[0]['avatar'];
   } else {
        $u['avatar']="assets/images/avatars/brian-hughes.jpg";
   }	   
   $u['status']="online";


$output='[
{
		"id": "home",
		"title": "Advisors",
		"subtitle": "",
		"type": "group",
		"icon": "heroicons_outline:home",	
		"children": [
{ "id": "db", "title": "Home", "type": "basic", "icon": "heroicons_outline:clipboard-check", "link": "/sadmin" },
{ "id": "db", "title": "Facility Management", "type": "basic", "icon": "heroicons_outline:clipboard-check", "link": "/facilities" },
{ "id": "db", "title": "Stakeholders", "type": "basic", "icon": "heroicons_outline:clipboard-check", "link": "/stakeholders" },
{ "id": "db", "title": "Assessments", "type": "basic", "icon": "heroicons_outline:clipboard-check", "link": "/assessments" },
{ "id": "db", "title": "Administration", "type": "basic", "icon": "heroicons_outline:clipboard-check", "link": "/templates" },
{ "id": "dbi", "title": "Account", "type": "basic", "icon": "heroicons_outline:clipboard-check", "link": "/accounts" }
]
}
]';

   $arr=json_decode($output,true);
   $output=array();
   $output['default']=$arr;
   $output['compact']=array();
   $output['futuristic']=array();
   $output['horizontal']=array();   

   $results=array();
   $results['user']=$user[0];
   $results['navigation']=$output;

    echo json_encode($results);
}
?>
