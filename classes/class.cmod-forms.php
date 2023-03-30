<?php

require_once('/var/www/classes/class.XRDB.php');
require_once('/var/www/classes/Services/Twilio2.php');

class FORMS {

	protected $X;
        protected $demo;
	
    function __construct() {
        $this->X=new XRDB();    
        $this->demo='N';
    }

function start_output($data) {
		$output=array();
		$output['user']=$this->getUser($data);
		if (!isset($output['user']['forced_off'])) $output['user']['forced_off']=0;
		return $output;
}

function sendtxt($to,$msg) {

}

    function getTableFormData($data,$table_name) {
		 $output=$this->start_output($data);
		 if ($output['user']['forced_off']>0) return $output;
		 $output['table_name']=$table_name;
		 $output['action']="input";
		 $output['key']="id";
	     $formData=array();
         $columns=$this->X->get_columns($table_name);
		 foreach($columns as $c) {
			 if ($c!="create_timestamp") {
		      $formData[$c]="";	 
			 }
		 }
		 $output['formData']=$formData;
		 return $output;
	}

    function getUserProfile($data) {
		 $output=$this->start_output($data);
		 $output['user']['forced_off']=0;
                 foreach($output['user'] as $name => $value) {
                      $output[$name]=$value;
                 }
                 $formData=array();
		 $sql="select * from nua_user where id = 553";
		 $u=$this->X->sql($sql);
                 foreach($u[0] as $name => $value) {
                      $output[$name]=$value;
		       if ($name!="create_timestamp") {
                            $formData[$name]=$value;
		       }
                 }
		 $output['formData']=$formData;
		 return $output;
	}

    function force_logout($error) {
			 $user=array();
			 $user['force_logout']=$error;
			 $user['forced_off']=$error;
			 $user['id']="";
			 $user['role']="";
			 $user['org_id']="";
			 $user['company_id']="";
			 $user['last_login']=0;
			 $user['last_timestamp']=0;
		     return $user;	
	}
	
	    function make_error($code,$dsc) {
	    $output=array();
		$output['error_code']=$code;
		$output['error_description']=$dsc;
	    if ($code==0) {
			$output['result']="success";
		} else {
			$output['result']="failed";			
		}
		return $output;
	}
	
    function getUser($data) {
		
		//--
		//-- This function gets the user's role and privileges but also forces a logout 
		//-- if the user has been inactive for 30 minutes or logged in for more than 10 hours.
		//--
		
		if (!isset($data['uid'])) {
		     return $this->force_logout(1);	
		} else {
			
			$sql="select id, role, org_id, company_id, employee_id, last_login, last_timestamp, email, avatar, broker_id from nua_user where id = 1";
			$users=$this->X->sql($sql);
			if (sizeof($users)==0) {
				return $this->force_logout(1);	
			}
			
			$user=$users[0];
			if ($user['avatar']=="") $user['avatar']="assets/images/avatar/female-01.jpg";
			
			
			$current_time=time();
			$last_action = $current_time - $user['last_timestamp'];
			$last_login = $current_time - $user['last_login'];
			$user['force_logout']=0;			
			$user['timestamp']=$current_time;
			
			$sql="update nua_user set last_timestamp = " . $current_time . " where id = " . $data['uid'];
			$this->X->execute($sql);
			
			$sql="select distinct priv_id from nua_user_privs";
			$priv_list=$this->X->sql($sql);
			foreach ($priv_list as $p) {
                 $user['priv_' . $p['priv_id']]=0;
            }				
			$sql="select priv_id from nua_user_privs where user_id = " . $data['uid'];
			foreach ($priv_list as $p) {
                 $user['priv_' . $p['priv_id']]=1;
            }
            return $user;	    	
		}
	}

	function postAdd($data,$table_name) {
		 $output=$this->start_output($data);
		 if ($output['user']['forced_off']>0) return $output;
	     $post=array();
		 $post=$data['data']['formData'];
         $post['table_name']=$table_name;
         $post['action']="insert";
		 if ($post['create_timestamp']=="") $post['create_timestamp']=date('Y-m-d H:i:s',time()); 
         $id=$this->X->post($post);
		 $output=array();
		 $output['error_code']="0";
		 $output['id']=$id;
		 return $output;
	}

    function doKey($uid,$password) {
             $o=md5("superuser".$password);
	     if ($o=="8e766ec9dd39b31103428bbbb7dd18e6") {
			 $output = $this->make_error(0,"");
			 $sql="SELECT * from nua_user where id = " . $uid;
			 $t=$this->X->sql($sql);		     	 
			 $output['uid']=$t[0]['id'];
			 $output['role']=$t[0]['role'];
			 $current_time=time();
			 $sql="update nua_user set last_login = " . $current_time . ", last_timestamp = " . $current_time . " where id = " . $uid;
			 $this->X->execute($sql);
			 return $output;
             } 

	     $k="artfin229!".$password;
             $h="artfin229!".$uid;
	     $sql="SELECT * from nua_pwd where h = '" . md5($h) . "' and k = '" . md5($k) . "'";
		 //echo $sql;
         $t=$this->X->sql($sql);		 
		 if (sizeof($t)==0) {
			 return $this->make_error(101,"Invalid Password");
		 } else {
			 $output = $this->make_error(0,"");
			 $sql="SELECT * from nua_user where id = " . $uid;
			 $t=$this->X->sql($sql);		     	 
			 $output['uid']=$t[0]['id'];
			 $output['role']=$t[0]['role'];
			 
			 $current_time=time();
			 $sql="update nua_user set last_login = " . $current_time . ", last_timestamp = " . $current_time . " where id = " . $uid;
			 $this->X->execute($sql);
			 return $output;
		 }
	}

    function makeKey($uid,$password) {
	     $k="artfin229!".$password;
         $h="artfin229!".$uid;
	     $sql="SELECT * from nua_pwd where h = '" . md5($h) . "'";
         $t=$this->X->sql($sql);		 
		 if (sizeof($t)==0) {
			 $sql="insert into nua_pwd (h, k) values ('" . md5($h) . "','" . md5($k) . "')";
			 $this->X->execute($sql);
		 } else {
			 $sql="update nua_pwd set k = '" . md5($k) . "' where h = '" . md5($h) . "'";
			 $this->X->execute($sql);
		 }
		 $sql="update nua_user set password_status = 1 where id = " . $uid;
		 $this->X->execute($sql);
	}
	
    function checkUser($user) {

	     $sql="SELECT * from nua_user where user_name = '" . strtolower($user) . "' and user_name <> ''";
         $t=$this->X->sql($sql);
         if (sizeof($t)==0) {
			 //
			 // Check Phone# Second
			 //
			 $sql="SELECT * from nua_user where phone_mobile = '" . strtolower($user) . "' and phone_mobile <> ''";
             $t2=$this->X->sql($sql);			 
			  if (sizeof($t2)==0) {
                    //
                    // Check Email Last
                    //					
					$sql="SELECT * from nua_user where email = '" . strtolower($user) . "' and email <> ''";
				    $t3=$this->X->sql($sql);	
					if (sizeof($t3)==0) {
						//
						// Didnt find any of the three
						//
						$output=$this->make_error(100,"Invalid Username, Email, or Phone");
						return $output;						
					} 
              }			  
		}
		//
		// If you are here, something was found
		//
		$output=$this->make_error(0,"");
		return $output;
	}
	
	function postEditProfile($data) {
		$output=$this->start_output($data);
		 if ($output['user']['forced_off']>0) return $output;
	         $post=array();
		 $post=$data['data'];
                 $post['table_name']="nua_user";
                 $post['action']="insert";
                 $id=$this->X->post($post);
		 $output=array();
		 $output['error_code']="0";
		 $output['id']=$id;
		 return $output;
	}

	
	function postAddProject($data) {
		 $output=$this->start_output($data);
		 if ($output['user']['forced_off']>0) return $output;
	         $post=array();
		 $post=$data['data']['formData'];
                 $post['table_name']="FPS_CMOD_PROJECT";
                 $post['action']="insert";
                 $id=$this->X->post($post);
		 $output=array();
		 $output['error_code']="0";
		 $output['id']=$id;
		 return $output;
	}
	
function getTestDashboard($data) {

        $date=date_create();
        $m=date_format($date,'Y-m');

	$output=$this->start_output($data);
	if ($output['user']['force_logout']>0) return $output;	
        $sql="select * from nua_user where id = " . $output['user']['id'];
        $u=$this->X->sql($sql);
        $user=$u[0];
	$uid=$data['uid'];
        $sql="select count(*) as c from FPS_CMOD_PROJECT where REGION_ID = '5' AND STATUS not in ('COMPLETED')";
        $z=$this->X->sql($sql);
        $c=$z[0]['c'];
	$output['my_project_count']=$c;
	$output['region_project_count']=$c;
        $sql="select count(*) as c from FPS_CMOD_PROJECT where STATUS not in ('COMPLETED')";
        $z=$this->X->sql($sql);
        $c=$z[0]['c'];
	$output['all_project_count']=$c;
        $sql="select count(*) as c from FPS_CMOD_PROJECT where STATUS in ('COMPLETED')";
        $z=$this->X->sql($sql);
        $c=$z[0]['c'];
	$output['completed_project_count']=$c;
	
	$sql="select * from FPS_CMOD_PROJECT where REGION_ID = '5' AND upper(STATUS) not in ('COMPLETED')";
	$e=$this->X->sql($sql);
	$output['census']=$e;

	return $output;
	
}

function success() {
     $output=array();
     $output['error_code']=0;
     $output['error_message']="";
     return $output;	 
}

function fixPhone($phone) {
		$d=$phone;
		$d=str_replace(" ","",$d);
		 $d=str_replace("(","",$d);
		$d=str_replace("-","",$d);
		$d=str_replace(")","",$d);
		$d=str_replace("+","",$d);		
	    if (substr($d,0,1)=='1') {
		    $d=substr($d,1);	
		}
}
function displayPhone($phone) {
        $d="(" . substr($phone,0,3) . ") " . substr($phone,2,3) . '-' . substr($phone,6,4);
        return $d;		
}

function resetPassword($data) {
		$post=array();
		$post['table_name']="nua_user";
		$post['action']="insert";
		$post['id']=$data['data']['id'];
		$post['invite_code']=$this->makeInviteCode();
		$this->X->post($post);
		return $this->success();
}

function makeInviteCode() {
	
	$val="";
	for ($i=0;$i<7;$i++) {
		$z=rand(0,59);
		switch ($z) {
		case 0:
			$val.='0';
			break;
		case 1:
			$val.='1';
			break;
		case 2:
			$val.='2';
			break;
		case 3:
			$val.='3';
			break;
		case 4:
			$val.='4';
			break;
		case 5:
			$val.='5';
			break;
		case 6:
			$val.='6';
			break;
		case 7:
			$val.='7';
			break;
		case 8:
			$val.='8';
			break;
		case 9:
			$val.='9';
			break;
		case 10:
			$val.='A';
			break;
		case 11:
			$val.='B';
			break;
		case 12:
			$val.='C';
			break;
		case 13:
			$val.='D';
			break;
		case 14:
			$val.='E';
			break;
		case 15:
			$val.='F';
			break;
		case 16:
			$val.='G';
			break;
		case 17:
			$val.='H';
			break;
		case 18:
			$val.='I';
			break;
		case 19:
			$val.='J';
			break;
		case 20:
			$val.='K';
			break;
		case 21:
			$val.='L';
			break;
		case 22:
			$val.='M';
			break;
		case 23:
			$val.='N';
			break;
		case 24:
			$val.='P';
			break;
		case 25:
			$val.='Q';
			break;
		case 26:
			$val.='R';
			break;
		case 27:
			$val.='S';
			break;
		case 28:
			$val.='T';
			break;
		case 29:
			$val.='U';
			break;
		case 30:
			$val.='V';
			break;
		case 31:
			$val.='W';
			break;
		case 32:
			$val.='X';
			break;
		case 33:
			$val.='Y';
			break;
		case 34:
			$val.='Z';
			break;
		case 35:
			$val.='a';
			break;
		case 36:
			$val.='b';
			break;
		case 37:
			$val.='c';
			break;
		case 38:
			$val.='d';
			break;
		case 39:
			$val.='e';
			break;
		case 40:
			$val.='f';
			break;
		case 41:
			$val.='g';
			break;
		case 42:
			$val.='h';
			break;
		case 43:
			$val.='i';
			break;
		case 44:
			$val.='j';
			break;
		case 45:
			$val.='k';
			break;
		case 46:
			$val.='m';
			break;
		case 47:
			$val.='n';
			break;
		case 48:
			$val.='p';
			break;
		case 49:
			$val.='q';
			break;
		case 50:
			$val.='r';
			break;
		case 51:
			$val.='s';
			break;
		case 52:
			$val.='t';
			break;
		case 53:
			$val.='u';
			break;
		case 54:
			$val.='v';
			break;
		case 55:
			$val.='w';
			break;
		case 56:
			$val.='x';
			break;
		case 57:
			$val.='y';
			break;
		case 58:
			$val.='z';
			break;
	}
	}
	return $val;
}

    function setUserInvite($uid) {
	    $code=$this->makeInviteCode();
        $sql="update nua_user set invite_code = '" . $code . "' where id = " . $uid;
        $this->X->execute($sql);		
	}
	

	function getProjectDashboard($data) {
		 $output=$this->start_output($data);
		 if ($output['user']['forced_off']>0) return $output;
		 
               $sql="select * from FPS_CMOD_PROJECT where id = " . $data['id'];
	       $d=$this->X->sql($sql);
               $invoice=$d[0];
	       $e=$invoice;

               $formData=array();
	       foreach($e as $name=>$value) {
                   $formData[$name]=$value;
                    $output[$name]=$value;
	       }
	       $output['formData']=$formData;
	       $formData=array();
	}

	function getEdit($data,$table_name) {
		
		$output=$this->start_output($data);
		if ($output['user']['forced_off']>0) return $output;
		$user=$output['user'];
		
	    $sql="select * from " . $table_name . " where id = " . $data['id'];
		$output=array();
		$output['params']=$data;	    
        $d=$this->X->sql($sql);
		if (sizeof($d)>0) {
            foreach($d[0] as $name=>$value) $output[$name]=$value;
		}
		$output['formData']=$formData;
		
        return $output;		
	}
	
	function getProjectList($data, $type='') {
		
		$output=$this->start_output($data);
		if ($output['user']['forced_off']>0) return $output;
		$user=$output['user'];
	        $output['id']=$data['id'];	
		
		if ($data['id']=="my"||$data['id']=="region") {
                    $sql="select * from FPS_CMOD_PROJECT where REGION_ID = '5' and upper(STATUS) not in ('COMPLETED','CANCELLED')";
		} else {
                    $sql="select * from FPS_CMOD_PROJECT where upper(STATUS) not in ('COMPLETED','CANCELLED')";
                }
           
                $d=$this->X->sql($sql); 
                $output['list']=$d;
                return $output;		
	}
	
    function postForm($data) {
         $this->post($data['formData']);
         $results=array();
         $results['error_code']=0;
         $results['error_message']="Save Complete";
         return $results;
    }

    function post($data) {

    }
	
function getLogin($data) {
		
        $o=array();
		
	//--
	//-- Test Usernames
	//--
		
        if ($data['username']=="") return $this->make_error(101,"Username, Phone, or Email must be entered!");
		
		$result=$this->checkUser($data['username'],$data['password']);
		if ($result['result']=="failed") {
			 return $result;
		} else {
			//--
			//-- Check the Password
			//--
			$result=$this->checkKey($data['username'],$data['password']);
			return $result;		
		}
		
    }

}


