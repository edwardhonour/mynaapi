<?php
ini_set('display_errors',1);
ini_set('display_startup_errors',1);
header('Access-Control-Allow-Headers: Access-Control-Allow-Origin, Content-Type, Authorization');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET,PUT,POST,DELETE,PATCH,OPTIONS');
header('Content-type: application/json');
require_once('/var/www/classes/class.PSDB.php');

$output=array();

function getSql($data) {
     $X = new PSDB();
     $id=$data['parameters']['id'];
     $id2=$data['parameters']['id2'];
     $id3=$data['parameters']['id3'];
     $sql=$data['sql'];
     $sql=str_replace(':id',$id,$sql);
     $sql=str_replace(':id2',"'" . $id2 . "'", $sql);
     $sql=str_replace(':id3',"'" . $id3 . "'", $sql);

     $output=array();
     $output=$X->sql($sql);

     return $output;
}

function getForm($data) {
     $X = new PSDB();
     $id=$data['parameters']['id'];
     $id2=$data['parameters']['id2'];
     $id3=$data['parameters']['id3'];

     if ($id==''||$id=='0') {
         $output=$X->columns($table);
     } else {
         $sql="select * from " . $table . " where id = " . $id;
         $rs=$X->sql($sql);
         $output=$rs[0];
     }

     $output['table_name']=$table;
     $output['action']="insert";
     return $output;

}

function getSelect($data) {

     $X = new PSDB();
     $id=$data['parameters']['id'];
     $id2=$data['parameters']['id2'];
     $id3=$data['parameters']['id3'];

     $sql=$data['sql'];
     $sql=str_replace(':id',$id,$sql);
     $sql=str_replace(':id2',"'" . $id2 . "'", $sql);
     $sql=str_replace(':id3',"'" . $id3 . "'", $sql);

     $output=array();

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

     return $output;

}

function getCalendar($data) {

     $X = new PSDB();
     $id=$data['parameters']['id'];
     $id2=$data['parameters']['id2'];
     $id3=$data['parameters']['id3'];
     $sql=$data['sql'];
     $sql=str_replace(':id',$id,$sql);
     $sql=str_replace(':id2',"'" . $id2 . "'", $sql);
     $sql=str_replace(':id3',"'" . $id3 . "'", $sql);

     $output=array();
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
       return $output;

}

function postForm($data) {

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
    return $output;
}

if (!isset($data['q'])) $data['q']="sql";
if (!isset($data['parameters'])) {
	$data['parameters']=array();
	$data['parameters']['page']="";
	$data['parameters']['id']="";
	$data['parameters']['id2']="";
	$data['parameters']['id3']="";
} else {
        if (!isset($data['parameters']['page'])) $data['parameters']['page']="";
        if (!isset($data['parameters']['id'])) $data['parameters']['id']="";
        if (!isset($data['parameters']['id2'])) $data['parameters']['id2']="";
        if (!isset($data['parameters']['id3'])) $data['parameters']['id3']="";
}


$data = file_get_contents("php://input");
$data = json_decode($data, TRUE);

$aa=explode("/",$data['q']);
if (isset($aa[1])) {
     $data['q']=$aa[1];
     $data['parameters']['page']=$aa[1];
     if (isset($aa[2])) {
         $data['id']=$aa[2];
         $data['parameters']['id']=$aa[2];
         }
     if (isset($aa[3])) {
         $data['id2']=$aa[3];
         $data['parameters']['id2']=$aa[3];
         }
     if (isset($aa[4])) {
         $data['id3']=$aa[4];
         $data['parameters']['id3']=$aa[4];
         }
}

$output=array();
   switch ($data['q']) {
    case 'getsql':
          $output=getSql($data);
   	  break;
    case 'postform':
          $output=postForm($data);
   	  break;
    case 'getselect':
	  $output=getSelect($data);
	  break;
    case 'getform':
	  $output=getForm($data);
	  break;
    case 'getcalendar':
	  $output=getCalendar($data);
	  break;
    case 'ping':
	  $output=$data['parameters'];
	  break;
}

$o=array();
$o=str_replace('null','""',json_encode($output, JSON_HEX_TAG |
        JSON_HEX_APOS |
        JSON_HEX_QUOT |
        JSON_HEX_AMP |
        JSON_UNESCAPED_UNICODE));

echo $o;

?>
