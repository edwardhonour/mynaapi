<?php
ini_set('display_errors',1);
ini_set('display_startup_errors',1);
header('Access-Control-Allow-Headers: Access-Control-Allow-Origin, Content-Type, Authorization');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET,PUT,POST,DELETE,PATCH,OPTIONS');
header('Content-type: application/json');
require_once('/var/www/classes/class.PSDB.php');

$output=array();

function cryptoJsAesDecrypt($passphrase, $jsonString){
	$jsonString=str_replace("\\","",$jsonString);
	$jsonString=trim($jsonString,'"');

    $jsondata = json_decode($jsonString, true);
    try {
        $salt = hex2bin($jsondata["s"]);
        $iv  = hex2bin($jsondata["iv"]);
    } catch(Exception $e) { echo "WTF"; return null; }
    $ct = base64_decode($jsondata["ct"]);
    $concatedPassphrase = $passphrase.$salt;
    $md5 = array();
    $md5[0] = md5($concatedPassphrase, true);
    $result = $md5[0];
    for ($i = 1; $i < 3; $i++) {
        $md5[$i] = md5($md5[$i - 1].$concatedPassphrase, true);
        $result .= $md5[$i];
    }
    $key = substr($result, 0, 32);
    $data = openssl_decrypt($ct, 'aes-256-cbc', $key, true, $iv);
    return json_decode($data, true);
}

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

function getMenu($data) {
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
	    $o3['id']="";
	    $o3['title']="";
	    $o3['badge']="";
	    $i=0;
	    foreach($o2 as $name=>$value) {
	        if ($i==0) $o3['id']=$value;
		if ($i==1) $o3['title']=$value;
		if ($i==2) $o3['badge']=$value;
		$i++;
	    }
            array_push($output,$o3);
	}

     return $output;
    
}

function getForm($data) {
     $X = new PSDB();
     $table=$data['table'];
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
}

function postDelete($data) {

    $X = new PSDB();
    $post=array();
    $d=$data['data'];

    $id=$d['id'];

    $sql="delete from " . $d['table_name'] . " where id = " . $id;
    $X->execute($sql);

    $output=array();
    $output['error_code']=0;
    $output['id']=$id;
    return $output;

}

function postForm($data) {


    $X = new PSDB();
    $post=array();
    $d=$data['data'];
    if (isset($d['triggers'])) {
       $t=cryptoJsAesDecrypt("hide-triggers", json_encode($d['triggers']));
       $d['triggers']=$t;
    } else {
       $triggers=array();
    }

    if (isset($d['triggers'])) {
    $triggers=$d['triggers'];
    } else {
       $triggers=array();
    }

    $post_insert_triggers=array();

    $vars=array();
    $sql="";
    foreach($triggers as $t)  {
            if ($d['id']=='0'||$d['id']=='') {
		 if ($t['type']=='pre-insert-trigger') {
                      $s=$t['sql'];
		      $words=explode(" ",$s);
		      $into=0;
		      $where=0;
		      $i=0;
		      foreach($words as $w) {
			  if ($w=="into") $into++;
			  if ($w=="where") $where++;
                          if (strpos($w,":")!==false) {
			     if ($into==0&&$where==0) {
                                $words[$i]="";      
			     }
			     if ($into>0&&$where==0) {
                                $w2=$w;
				$w2=str_replace(":","",$w2);
				$w2=str_replace(",","",$w2);
				array_push($vars,$w2);
				$words[$i]="";
			     }
			     if ($where>0) {
                                $w2=$w;
				$w2=str_replace(":","",$w2);
				$w2=str_replace(",","",$w2);
                                $z2=$d[$w2];
				if (is_numeric($z2)) {
				   $words[$i]=$z2;
				} else {
				   $words[$i]="'" . $z2 . "'";
				}
			     }
   			  }
			  $i++;
		      }
		      foreach($words as $w) { if ($w!="into")  $sql.=$w . " "; } 
		      $rs=$X->sqlNum($sql);
		      if (sizeof($rs)>0) {
                          $t=$rs[0];
			  $i=0;
			  foreach($vars as $v) {
                              $d[$v]=$t[$i];
			      $i++;
			  }
		      }
	         }
	    } 
	     if ($t['type']=='pre-update-trigger') {
                      $s=$t['sql'];
		      $words=explode(" ",$s);
		      $into=0;
		      $where=0;
		      $i=0;
		      foreach($words as $w) {
			  if ($w=="into") $into++;
			  if ($w=="where") $where++;
                          if (strpos($w,":")!==false) {
			     if ($into==0&&$where==0) {
                                $words[$i]="";      
			     }
			     if ($into>0&&$where==0) {
                                $w2=$w;
				$w2=str_replace(":","",$w2);
				$w2=str_replace(",","",$w2);
				array_push($vars,$w2);
				$words[$i]="";
			     }
			     if ($where>0) {
                                $w2=$w;
				$w2=str_replace(":","",$w2);
				$w2=str_replace(",","",$w2);
                                $z2=$d[$w2];
				if (is_numeric($z2)) {
				   $words[$i]=$z2;
				} else {
				   $words[$i]="'" . $z2 . "'";
				}
			     }
   			  }
			  $i++;
		      }
		      foreach($words as $w) { if ($w!="into")  $sql.=$w . " "; } 
		      $rs=$X->sqlNum($sql);
		      if (sizeof($rs)>0) {
                          $t=$rs[0];
			  $i=0;
			  foreach($vars as $v) {
                              $d[$v]=$t[$i];
			      $i++;
			  }
		      }
	         }

	     if ($t['type']=='post-insert-trigger') {
                      $s=$t['sql'];
		      $words=explode(" ",$s);
		      $into=0;
		      $where=0;
		      $update=0;
		      $insert=0;
		      $i=0;
		      foreach($words as $w) {
			  if ($w=="set") $into++;
			  if ($w=="where") $where++;
			  if ($w=="update") $update++;
			  if ($w=="insert") $insert++;
                          if (strpos($w,":")!==false) {
			     if ($into==0&&$where==0) {
                                $words[$i]="";      
			     }
			     if ($into>0&&$where==0) {
				     /*
                                $w2=$w;
				$w2=str_replace(":","",$w2);
				$w2=str_replace(",","",$w2);
				array_push($vars,$w2);
				$words[$i]="";
				      */
			     }
			     if ($where>0) {
                                $w2=$w;
				$w2=str_replace(":","",$w2);
				$w2=str_replace(",","",$w2);
                                $z2=$d[$w2];
				if (is_numeric($z2)) {
				   $words[$i]=$z2;
				} else {
				   $words[$i]="'" . $z2 . "'";
				}
			     }
   			  }
			  $i++;
		      }
		      foreach($words as $w) { if ($w!="into")  $sql.=$w . " "; } 
		      array_push($post_insert_triggers,$sql);

	         }
    }

    foreach($d as $name=>$value) {
       if ($name!='submit'&&$name!='create_timestamp'&&$name!="triggers"&&$name!="delete") {
          $post[$name]=$value;
       }
    }
    if ($post['id']=='0'||$post['id']=='') { 
       $insert="Y";
    } else {
       $insert="N";
    }
    $id=$X->post($post);
    if ($insert=="Y") {
         foreach($post_insert_triggers as $p) {
             $sql=$p;
	     $s2=str_replace(":id",$id,$sql);
	     $X->execute($s2);
         }
    }
    $output=array();
    $output['error_code']=0;
    $output['id']=$id;
    return $output;
}

$data = file_get_contents("php://input");
$data = json_decode($data, TRUE);

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
    case 'getmenu':
          $output=getMenu($data);
   	  break;
    case 'postform':
          $output=postForm($data);
   	  break;
    case 'postdelete':
          $output=postDelete($data);
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
$o=json_encode($output, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP );

echo $o;
 
?>
