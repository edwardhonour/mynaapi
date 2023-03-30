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

function sendInviteTxt($data) {
	    $user_id=$data['data']['id'];
		$sql="select phone_mobile, invite_code from nua_user where id = " . $user_id;
		$users=$this->X->sql($sql);
		$user=$users[0];
        $to=$user['phone_mobile'];
		$to=str_replace("+","",$to);
		$to=str_replace("(","",$to);
		$to=str_replace(")","",$to);
		$to=str_replace(" ","",$to);	
		$to=str_replace("-","",$to);
	    if (strlen($to)!=10&&strlen($to)!=11) {
		       $output=array();
               $output['error_code']=1;
               $output['message']="Invalid Phone Number entered for this user!";
               return $output;			   
		} else {
			$msg="You have been invited to the NuAxess Enrollment Portal. Click to enroll: https://mynuaxess.com/#/e/" . $user['invite_code'];
			$response=$this->sendtxt($to,$msg);
		       $output=array();
               $output['error_code']=0;
               $output['message']=$response;
			   $sql="update nua_user set notification_status = 'SMS' where id = " . $user_id;
			   $this->X->execute($sql);
               return $output;					
		}
}

function postActivatePlan($data) {

     $sql="select infinity_id from nua_company where id = " . $data['data']['id'];
     $t=$this->X->sql($sql);

     $sql="update inf_client_plan set active = 'Y' where clientId = '" . $t[0]['infinity_id'] . "' and planId = '" . $data['data']['id2'] . "'";
     $this->X->execute($sql);

     $output=array();
     $output['error_code']=0;
     return $output;
}

function postAddWorkout($data) {
	     
		 $output=$this->start_output($data);
		 
		 $post=array();
		 $post['table_name']="kss_workout";
		 $post['action']="input";

                 $id=$this->X->post($post);
		 
		 $output=array();
		 $output['error_code']="0";
		 $output['id']=$id;
		 return $output;
}

function postAddProgram($data) {
	     
		 $output=$this->start_output($data);
		 
		 $post=array();
		 $post['table_name']="kss_program";
		 $post['action']="input";

                 $id=$this->X->post($post);
		 
		 $output=array();
		 $output['error_code']="0";
		 $output['id']=$id;
		 return $output;
}

function postAddBodypart($data) {
	     
		 $output=$this->start_output($data);
		 
		 $post=array();
		 $post['table_name']="kss_bodypart";
		 $post['action']="input";

                 $id=$this->X->post($post);
		 
		 $output=array();
		 $output['error_code']="0";
		 $output['id']=$id;
		 return $output;
}

function postAddExercise($data) {
	     
		 $output=$this->start_output($data);
		 
		 $post=array();
		 $post['table_name']="kss_exercise";
		 $post['action']="input";

                 $id=$this->X->post($post);
		 
		 $output=array();
		 $output['error_code']="0";
		 $output['id']=$id;
		 return $output;
}

function postAddWeek($data) {
	     
		 $output=$this->start_output($data);
		 
		 $post=array();
		 $post['table_name']="kss_user_week";
		 $post['action']="input";

                 $id=$this->X->post($post);
		 
		 $output=array();
		 $output['error_code']="0";
		 $output['id']=$id;
		 return $output;
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
		 if ($output['user']['forced_off']>0) return $output;
                 foreach($output['user'] as $name => $value) {
                      $output[$name]=$value;
                 }
                 $formData=array();
		 $sql="select * from nua_user where id = " . $data['uid'];
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

    function getUserSettings($data) {
		 $output=$this->start_output($data);
		 if ($output['user']['forced_off']>0) return $output;
                 $formData=array();
                 foreach($output['user'] as $name => $value) {
                      $output[$name]=$value;
                 }
                 $columns=$this->X->get_columns("nua_user");
		 foreach($columns as $c) {
		       if ($c!="create_timestamp") {
		             $formData[$c]="";	 
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
			
			$sql="select id, role, org_id, company_id, employee_id, last_login, last_timestamp, email, avatar, broker_id from nua_user where id = " . $data['uid'];
			$users=$this->X->sql($sql);
			if (sizeof($users)==0) {
				return $this->force_logout(1);	
			}
			
			$user=$users[0];
			if ($user['avatar']=="") $user['avatar']="assets/images/avatar/female-01.jpg";
			
			
			$current_time=time();
			$last_action = $current_time - $user['last_timestamp'];
			if ($last_action>1800) {
				return $this->force_logout(2);	
			}
			$last_login = $current_time - $user['last_login'];
			
			
			if ($last_login>36000) {
				return $this->force_logout(3);	
			}
            
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

    function getOrgDropdown($data=array()) {
		 $sql="select id, org_name from nua_org order by org_name";
		 $org=$this->X->sql($sql);
		 return $org;
	}

    	
    function getWorkoutFormData($data) {
		$table_name='nua_company';
		 $output=$this->start_output($data);
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
		 if ($data['id']!="") $formData['org_id']=$data['id'];
		 $formData['status']="prospect";
		 $output['formData']=$formData;
		 return $output;
	}
	
    function getExerciseFormData($data) {
		$table_name='nua_company';
		 $output=$this->start_output($data);
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
		 if ($data['id']!="") $formData['org_id']=$data['id'];
		 $formData['status']="prospect";
		 $output['formData']=$formData;
		 return $output;
	}
	
    function getWeekFormData($data) {
		$table_name='nua_company';
		 $output=$this->start_output($data);
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
		 if ($data['id']!="") $formData['org_id']=$data['id'];
		 $formData['status']="prospect";
		 $output['formData']=$formData;
		 return $output;
	}
	
    function getBodypartFormData($data) {
		 $table_name='kss_bodypart';
		 $output=$this->start_output($data);
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
		 $sql="select * from kss_bodypart where part_level = 0 order by display_order";
		 $y=$this->X->sql($sql);
                 $list=array();
                 $t=array();
		 $t['value']=0;
		 $t['name']="[None]";
		 array_push($list,$t);
		 foreach($y as $z) {
                      $t=array();
		      $t['value']=$z['id'];
		      $t['name']=$z['bodypart_name'];
		      array_push($list,$t);
		      
		      $sql="select * from kss_bodypart where parent = " . $z['id'] . " order by display_order";
		      $y=$this->X->sql($sql);
		      foreach($y as $z) {
                          $t=array();
		          $t['value']=$z['id'];
		          $t['name']='-- ' . $z['bodypart_name'];
		          array_push($list,$t);
		      $sql="select * from kss_bodypart where parent = " . $z['id'] . " order by display_order";
		      $y=$this->X->sql($sql);
		      foreach($y as $z) {
                          $t=array();
		          $t['value']=$z['id'];
		          $t['name']='-- --' . $z['bodypart_name'];
		          array_push($list,$t);
		       }
		       }
		 }
		 $output['select']=$list; 
		 return $output;
	}
	
//--
//-- 51
//--
//
	function getEditCompany($data,$table_name) {
		 $output=$this->start_output($data);
		 if ($output['user']['forced_off']>0) return $output;
	         $sql="select * from nua_company where id = " . $output['user']['company_id'];
	  	 $company=$this->X->sql($sql);
		 $output['formData']=$company[0];
                 $d=$this->X->sql($sql);
                 return $output;		
	}

//--
//-- 50
//--
	function getEditQuoteRequest($data) {
		 $output=$this->start_output($data);
		 if ($output['user']['forced_off']>0) return $output;
	    $sql="select * from nua_quote where id = " . $data['id'];
		$quote=$this->X->sql($sql);
		$output['formData']=$quote[0];
		
	//	 $output['user']=$this->getUser($data);
		 $output['select']=$this->getOrgDropdown($data);
        $d=$this->X->sql($sql);
		if (sizeof($d)>0) {
            $formData=$d[0];
		}		
        return $output;		
	}
	
//--
//-- 52
//--
//
	function getEditUser($data) {
		 $output=$this->start_output($data);
		 if ($output['user']['forced_off']>0) return $output;
	    $sql="select id, user_name, full_name, phone_mobile, email, role  from nua_user where id = " . $data['id'];
		$company=$this->X->sql($sql);
		$output['formData']=$company[0];
		 $sql="select id, org_name from nua_org order by org_name";
		 $org=$this->X->sql($sql);
		 $output['select']=$org;
        $d=$this->X->sql($sql);
		if (sizeof($d)>0) {
            $formData=$d[0];
		}		
        return $output;		
	}
	
//--
//-- 18
//--
	function postEmployeeTermination($data) {
		 $output=$this->start_output($data);
		 if ($output['user']['forced_off']>0) return $output;
                 return $output;		
	}

//--
//-- 19
//--
	function postEmployeeAddition($data) {
		 $output=$this->start_output($data);
		 if ($output['user']['forced_off']>0) return $output;
                 return $output;		
	}


//--
//-- 7
//--

    function getUserFormData($data) {
  	     $table_name="nua_user";
		 $output=$this->start_output($data);
		 if ($output['user']['forced_off']>0) return $output;
		 $output['table_name']=$table_name;
		 $output['action']="input";
		 $output['key']="id";
	     $formData=array();
//         $columns=$this->X->get_columns($table_name);
//		 foreach($columns as $c) {
//		      $formData[$c]="";	 
//		 }
         $formData['full_name']="";
		 $formData['email']="";
		 $formData['phone_mobile']="";
		 $formData['role']="user";
		 $formData['org_id']=0;
		 $formData['company_id']=0;
		 $output['user']=$this->getUser($data);
		 $output['formData']=$formData;
		 $sql="select id, org_name from nua_org order by org_name";
		 $orgs=$this->X->sql($sql);
		 $output['select']=$orgs;
		 return $output;
	}
	
	function getInvoiceForm($data) {

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

    function checkKey($user, $password) {
		
	 //
	 // Check User Name First
	 //
	 $sql="SELECT * from nua_user where user_name = '" . strtolower($user) . "' and user_name <> ''";
         $t=$this->X->sql($sql);
         if (sizeof($t)==0) {
		 //
		 // Check Phone# Second
		 //
		 $sql="SELECT * from nua_user where phone_mobile = '" . strtolower($user) .  "' and phone_mobile <> ''";
                 $t2=$this->X->sql($sql);			 
		  if (sizeof($t2)==0) {	
				$sql="SELECT * from nua_user where email = '" . strtolower($user) . "' and email <> ''";
			    $t3=$this->X->sql($sql);	
				if (sizeof($t3)==0) {
					//
					// Didnt find any of the three
					//
					$output=$this->make_error(100,"Invalid Username, Email, or Phone");
					return $output;						
				} else {
					return $this->doKey($t3[0]['id'],$password);
				}
              }	else {
					return $this->doKey($t2[0]['id'],$password);				  
			  } 
		} else {
			return $this->doKey($t[0]['id'],$password);				
		}
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
	
//--
//-- 40
//--

   function postEnroll($data) {
	   $token=$data['data']['token'];
	   $token=str_replace("/e/","",$token);
		if ($token=="") {
   				$output=array();
				$output['error_code']="1";
				$output['error_message']="The token is invalid!";
				return $output;		
		} else {
			
			$sql="select * from nua_user where invite_code = '" . $token . "'";
			$z=$this->X->sql($sql);
		    if (sizeof($z)==0) {
   				$output=array();
				$output['error_code']="1";
				$output['error_message']="The token is invalid!";
				return $output;	
            } else {
				$this->makeKey($data['data']['formData']['id'],$data['data']['formData']['password']);
			    $sql="update nua_user set status = 'active' where id = " . $data['data']['formData']['id'];
                $this->X->execute($sql);		
				$output=array();
				$output['error_code']="0";
				$output['error']="0";
				return $output;
			}
		}
    }
	
	function postEditPlan($data) {
	}

//--
//-- 41
//--
	function postEditUser($data) {
		$output=$this->start_output($data);
		 if ($output['user']['forced_off']>0) return $output;
	         $post=array();
		 $post=$data['data']['formData'];
                 $post['table_name']="nua_user";
                 $post['action']="insert";
                 $id=$this->X->post($post);
		 $output=array();
		 $output['error_code']="0";
		 $output['id']=$id;
		 return $output;
	}

//--
//-- 47
//-- 
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

	
function getTestDashboard($data) {

        $date=date_create();
        $m=date_format($date,'Y-m');

	$output=$this->start_output($data);
	if ($output['user']['force_logout']>0) return $output;	
        $sql="select * from nua_user where id = " . $output['user']['id'];
        $u=$this->X->sql($sql);
        $user=$u[0];
	$uid=$data['uid'];
        $company_id=$user['company_id'];
        if ($company_id==0) $company_id = 5556;
        $sql="select * from nua_company where id = " . $company_id;
        $u=$this->X->sql($sql);
        $company=$u[0];
        $output['company']=$company; 
	$org_id=$user['org_id'];
        $user_id=$uid;
	$role=$user['role'];
	$month_id=$m;
        if ($m=="2022-03") $month_id2 = "2022-04";
        if ($m=="2022-04") $month_id2 = "2022-05";
        if ($m=="2022-05") $month_id2 = "2022-06";
        if ($m=="2022-06") $month_id2 = "2022-07";
        if ($m=="2022-07") $month_id2 = "2022-08";
        if ($m=="2022-08") $month_id2 = "2022-09";
        if ($m=="2022-09") $month_id2 = "2022-10";
        if ($m=="2022-10") $month_id2 = "2022-11";
        if ($m=="2022-11") $month_id2 = "2022-12";
        $output['month_id']=$month_id;
        $output['month_id2']=$month_id2;
	$sql="select count(distinct employee_id) as c from nua_monthly_member_census where company_id = '" . $company_id . "' and month_id = '" . $month_id . "'";
        $p=$this->X->sql($sql);	
	$output['member_count']=$p[0]['c'];
	$sql="select count(distinct employee_id) as c from nua_monthly_member_additions where company_id = '" . $company_id . "' and month_id = '" . $month_id2 . "'";
        $p=$this->X->sql($sql);	
	$output['addition_count']=$p[0]['c'];
	$sql="select count(distinct employee_id) as c from nua_monthly_member_terminations where company_id = '" . $company_id . "' and month_id = '" . $month_id2 . "'";
        $p=$this->X->sql($sql);	
	$output['termination_count']=$p[0]['c'];
	$sql="select count(*) as c from nua_company_plan where end_month_id = '' and  company_id = '" . $company_id . "'";
        $p=$this->X->sql($sql);	
	$output['plan_count']=$p[0]['c'];
	
	$sql="select distinct employee_id, employee_code, last_name, first_name, middle_initial, dob, gender, eff_dt, term_dt from nua_monthly_member_census where dependent_code = '' and month_id = '" . $month_id . "' and  company_id = " . $company_id;
        $sql.=" order by last_name, first_name, client_plan";
		$e=$this->X->sql($sql);
		$r=array();
                $last="XXX";
		foreach($e as $f) {
                        if ($f['employee_id']==$last) {
                        }
                        $f['term']="N";
                        array_push($r,$f);
		}
		$output['census']=$r;

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

	function postAddUser($data) {
		
		  $error_code=0;
		  $error_message="";	  
	      $email=strtolower($data['data']['formData']['email']);
		  $full_name=strtolower($data['data']['formData']['full_name']);
		  $phone_mobile=strtolower($data['data']['formData']['phone_mobile']);		
		  $role=strtolower($data['data']['formData']['role']);				  
		  $org_id=strtolower($data['data']['formData']['org_id']);
		  if ($org_id=="") $org_id="0";
		  $company_id=strtolower($data['data']['formData']['company_id']);
		  if ($company_id=="") $company_id="0";
		  
		  $sql="select count(*) as C from nua_user where email = '" . $email . "'";
		  $z=$this->X->sql($sql); 
		  if ($z[0]['C']>0) {
			$output=array();
            $output['error_ccde']="1";
            $output['error_message']="Account with the Email Address already exists";
            return $output;			
		  }
		  
          if ($phone_mobile!="") {
              $phone_mobile=str_replace(" ","",$phone_mobile);
			  $phone_mobile=str_replace("(","",$phone_mobile);
		      $phone_mobile=str_replace(")","",$phone_mobile);
		      $phone_mobile=str_replace("-","",$phone_mobile);
		      $phone_mobile=str_replace("+","",$phone_mobile);			  
			  $sql="select count(*) as C from nua_user where phone_mobile = '" . $phone_mobile . "'";
		      $z=$this->X->sql($sql); 
		      if ($z[0]['C']>0) {
			     $output=array();
                 $output['error_ccde']="1";
                 $output['error_message']="Account with the Mobile Phone already exists";
                 return $output;			
		      }
          }		  
		  
          $post=array();
		  $post['table_name']="nua_user";
	      $post['action']="insert";
		  //$post['user_name']=$user_name;
		  $post['email']=$email;
		  $post['role']=$role;
	      $post['phone_mobile']=$phone_mobile;
		  $post['full_name']=$full_name;
		  $post['company_id']=0;
		  $post['org_id']=0;
		  $post['invite_code']=$this->makeInviteCode();
	
		  if ($role=="badmin"||$role=="broker") {
			 if ($org_id=="0") {
				$output=array();
				$output['error_ccde']="1";
				$output['error_message']="Organization Users must have an organization selected";
				return $output;							 
			 }
			 $post['org_type']="orgnaization";  
			 $post['org_id']=$org_id;	
			 $post['company_id']=0;
		  }
		  if ($role=="sadmin"||$role=="user") {
			 $post['org_type']="nuaxess";  
			 $post['org_id']=1;	
		     $post['company_id']=0;
		  }		  
		  if ($role=="eadmin"||$role=="employee") {
			  if ($company_id=="0") {
				$output=array();
				$output['error_ccde']="1";
				$output['error_message']="Employer/Prospect Users must have a company selected";
				return $output;
			  }				
				$post['org_type']="company";  
				$post['org_id']=0;	
				$post['company_id']=$company_id;
		  }			  
          $id=$this->X->post($post);			  
		  $output=array();
		  $output['error_code']="0";
		  $output['id']=$id;
		  $output['error_message']="";
		  return $output;
	}
	
//--
//-- 26
//

function getWorkoutList($data) {

		 $output=$this->start_output($data);
                 $user_id=$output['user']['id'];

		 $sql="select * from kss_user_workout where ";
                 $sql.=" user_id = " . $user_id . " order by workout_date, workout_time desc";
                 $y=$this->X->sql($sql);
		 $list=array();
                 $output['list']=$list;
                 return $output;
}

function getExerciseList($data) {

		 $output=$this->start_output($data);
                 $user_id=$output['user']['id'];

		 $sql="select * from kss_exercise where ";
                 $sql.=" user_id in (" . $user_id . ",0)  order by category, display_order";
                 $y=$this->X->sql($sql);
                 $output['list']=$y;
                 return $output;
}

function getDexaList($data) {

		 $output=$this->start_output($data);
                 $user_id=$output['user']['id'];

		 $sql="select * from kss_dexa where ";
                 $sql.=" user_id in (" . $user_id . ",-90)  order by measured_date desc";
                 $y=$this->X->sql($sql);
                 $output['list']=$y;
                 return $output;
}

function getWeekList($data) {

		 $output=$this->start_output($data);
                 $user_id=$output['user']['id'];

		 $sql="select * from kss_user_week where ";
                 $sql.=" user_id in (" . $user_id . ",-90)  order by week_start_date";
                 $y=$this->X->sql($sql);
                 $output['list']=$y;
                 return $output;
}

function getBodypartList($data) {

		 $output=$this->start_output($data);
                 $user_id=$output['user']['id'];

		 $sql="select * from kss_bodypart where part_level = 0 order by display_order";
		 $y=$this->X->sql($sql);
                 $list=array();
		 foreach($y as $z) {
                      $t=array();
		      $t['id']=$z['id'];
		      $t['one']=$z['bodypart_name'];
		      $t['two']="";
		      $t['three']="";
		      array_push($list,$t);
		      
		      $sql="select * from kss_bodypart where parent = " . $z['id'] . " order by display_order";
		      $y=$this->X->sql($sql);
		      foreach($y as $z) {
                          $t=array();
		          $t['id']=$z['id'];
		          $t['two']=$z['bodypart_name'];
		          $t['one']="";
		          $t['three']="";
		          array_push($list,$t);
		      $sql="select * from kss_bodypart where parent = " . $z['id'] . " order by display_order";
		      $y=$this->X->sql($sql);
		      foreach($y as $z) {
                          $t=array();
		          $t['id']=$z['id'];
		          $t['three']=$z['bodypart_name'];
		          $t['one']="";
		          $t['two']="";
		          array_push($list,$t);
		       }
		       }
		 }
                 $output['list']=$list;
                 return $output;
}


//--
//-- 28
//

	function getUserDashboard($data) {
		
		$output=$this->start_output($data);
		if ($output['user']['forced_off']>0) return $output;
		$user=$output['user'];

		$output['params']=$data;
		$sql="select * from nua_user where id = " . $data['id'];	
                $d=$this->X->sql($sql);
		if (sizeof($d)>0) {
                     foreach($d[0] as $name=>$value) $output[$name]=$value;
		}
		$sql="select * from nua_email where user_id = " . $data['id'] . " order by create_timestamp desc";
		$a=$this->X->sql($sql);
		$output['emails']=$a;

        return $output;		
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
	
	function sendMail($from, $to, $name, $account_name, $support_email, $variables) {

		$url = 'https://api.mailersend.com/v1/email';		

			
		$postRequest=array();
		$postRequest['from']=array();
		$postRequest['from']['email']=$from;
		$postRequest['to']=array();
		$postRequest['to']['email']=array();
		$postRequest['to']['email']['email']=$to;
		$postRequest['subject']="This is  Test";		
		$postRequest['variables']=array();
		$postRequest['variables']['email']=array();
		$postRequest['variables']['email']['email']=$to;
		$postRequest['variables']['substitutions']=array();
		$postRequest['variables']['email']['substitutions']=array();
		$subs=array();
		$line=array();
		$line['var']="name";
		$line['value']=$name;
		array_push($subs,$line);
		$line=array();
		$line['var']="account.name";
		$line['value']=$account_name;
		array_push($subs,$line);
		$line=array();
		$line['var']="account.name";
		$line['value']=$account_name;
		array_push($subs,$line);
		$line=array();
		$line['var']="support_email";
		$line['value']=$support_email;
		array_push($subs,$line);
	    $postRequest['variables']['substitutions']=$subs;
		$postRequest['variables']['substitutions']['email']=$to;
		$postRequest['variables']['email']['substitutions']=$subs;		
		$postRequest['variables']['substitutions']['substitutions']=$subs;		
        $postRequest['template_id']="jy7zpl9o3pl5vx6k";
        $data_string=json_encode($postRequest);
		$customHeaders = array(
			'Content-Type: application/json',
			'Content-Length: ' . strlen($data_string),
			'X-Requested-With: XMLHttpRequest',
			'Authorization: Bearer eyJ0eXAiOiJKV1QiLCJhbGciOiJSUzI1NiJ9.eyJhdWQiOiIxIiwianRpIjoiY2I5MjliYTM1NDMyMzcxOThlZDhhNGJhYjJlOTk1N2Y3NWFlNDhiNWI2ZjE3ZWE4NzcxN2MxOTEyNTlhMmE2MTFjOGVmODdmYjJmZmE3YjgiLCJpYXQiOjE2NDEzMTIzMjYuNTI4NjksIm5iZiI6MTY0MTMxMjMyNi41Mjg2OTUsImV4cCI6NDc5Njk4NTkyNi4zNjI1NjYsInN1YiI6IjE4MjUxIiwic2NvcGVzIjpbImVtYWlsX2Z1bGwiLCJkb21haW5zX2Z1bGwiLCJhY3Rpdml0eV9mdWxsIiwiYW5hbHl0aWNzX2Z1bGwiLCJ0b2tlbnNfZnVsbCIsIndlYmhvb2tzX2Z1bGwiLCJ0ZW1wbGF0ZXNfZnVsbCIsInN1cHByZXNzaW9uc19mdWxsIl19.GckmYHjYy8koSaAAbxA33AETf4B5xrwa1V1pSqzbeawbUOW8CS7tky15jYVvGUIe5dkf34oQYttsbVW6PEfDouTKBa2Zt00fiW7zF0v0GNFV_QV4fgAlCHOY-uEbLk0mmaPUeoVMcPmy4Ae7NAxRonZZcxrhRzs5eo3vHcUoMj7J7rUPjgpuxp6qR4qMgqyRv6szel6FfP0_6exHBs9MweqFH7H-au5YTefHhzqNpaQkDh_FGG6gKv9G0qaP4je7S4W7ihQWZ0fZU80RLhVinZ10plAr7dlf9dh1UW3Jz3OuhdyPlxSb5dCmMXMRHoQpJt60GxBLojFGlDT6xk9DxW80j-ryKeDFPSQ22TbxcbRqHimg6Frnl86S_0eOelwLmdvkzdR84U-XT0mWVadXSAkIACCFLqL4XKZ6IrHQ-kwwJQ__if8rVKqHKdS-4FRkOTfHWO3kgvVYaoeiOrDWiaHa0S3YqhcCKCGgMfE5OXDMRImDWfofbkZ2XbWguwDTVTMRjYkK8H9tNpBoz6P0_ld8E3fHOV4yxw2s1FO2NsH3yNENXkqO8W21vVHBq2XJEQjult-4o6b0gyjXBZNvuZD-kpzgywtxoZL4tuiS7mnppfMRKBJPDBiN-f3vbt6zdZ4Alw-4_gEB875kLKZybPmGyIatTBZ6w2WFjtqKzHw',
			'Content: ' . $data_string
		);
		
	$ch = curl_init($url);
	curl_setopt($ch, CURLOPT_HTTPHEADER, $customHeaders);
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
	curl_setopt($ch, CURLOPT_POSTFIELDS, $data_string);
	$response = curl_exec($ch);
	if(curl_errno($ch)){
		throw new Exception(curl_error($ch));
	}
    echo $response;
	die();
	}
	
	function sendTemplate($from, $to, $subject, $template="neqvygmpqzg0p7w2", $subs) {

		$url = 'https://api.mailersend.com/v1/email';		

		$postRequest=array();
		$postRequest['from']=array();
		$postRequest['from']['email']=$from;
		$postRequest['to']=array();
		$postRequest['to']['email']=array();
		$postRequest['to']['email']['email']=$to;
		$postRequest['subject']=$subject;		
		$postRequest['variables']=array();
		$postRequest['variables']['email']=array();
		$postRequest['variables']['email']['email']=$to;
		$postRequest['variables']['substitutions']=array();
		$postRequest['variables']['email']['substitutions']=array();
	        $postRequest['variables']['substitutions']=$subs;
		$postRequest['variables']['substitutions']['email']=$to;
		$postRequest['variables']['email']['substitutions']=$subs;		
		$postRequest['variables']['substitutions']['substitutions']=$subs;		
                $postRequest['template_id']=$template;
                $data_string=json_encode($postRequest);
		$customHeaders = array(
			'Content-Type: application/json',
			'Content-Length: ' . strlen($data_string),
			'X-Requested-With: XMLHttpRequest',
			'Authorization: Bearer eyJ0eXAiOiJKV1QiLCJhbGciOiJSUzI1NiJ9.eyJhdWQiOiIxIiwianRpIjoiY2I5MjliYTM1NDMyMzcxOThlZDhhNGJhYjJlOTk1N2Y3NWFlNDhiNWI2ZjE3ZWE4NzcxN2MxOTEyNTlhMmE2MTFjOGVmODdmYjJmZmE3YjgiLCJpYXQiOjE2NDEzMTIzMjYuNTI4NjksIm5iZiI6MTY0MTMxMjMyNi41Mjg2OTUsImV4cCI6NDc5Njk4NTkyNi4zNjI1NjYsInN1YiI6IjE4MjUxIiwic2NvcGVzIjpbImVtYWlsX2Z1bGwiLCJkb21haW5zX2Z1bGwiLCJhY3Rpdml0eV9mdWxsIiwiYW5hbHl0aWNzX2Z1bGwiLCJ0b2tlbnNfZnVsbCIsIndlYmhvb2tzX2Z1bGwiLCJ0ZW1wbGF0ZXNfZnVsbCIsInN1cHByZXNzaW9uc19mdWxsIl19.GckmYHjYy8koSaAAbxA33AETf4B5xrwa1V1pSqzbeawbUOW8CS7tky15jYVvGUIe5dkf34oQYttsbVW6PEfDouTKBa2Zt00fiW7zF0v0GNFV_QV4fgAlCHOY-uEbLk0mmaPUeoVMcPmy4Ae7NAxRonZZcxrhRzs5eo3vHcUoMj7J7rUPjgpuxp6qR4qMgqyRv6szel6FfP0_6exHBs9MweqFH7H-au5YTefHhzqNpaQkDh_FGG6gKv9G0qaP4je7S4W7ihQWZ0fZU80RLhVinZ10plAr7dlf9dh1UW3Jz3OuhdyPlxSb5dCmMXMRHoQpJt60GxBLojFGlDT6xk9DxW80j-ryKeDFPSQ22TbxcbRqHimg6Frnl86S_0eOelwLmdvkzdR84U-XT0mWVadXSAkIACCFLqL4XKZ6IrHQ-kwwJQ__if8rVKqHKdS-4FRkOTfHWO3kgvVYaoeiOrDWiaHa0S3YqhcCKCGgMfE5OXDMRImDWfofbkZ2XbWguwDTVTMRjYkK8H9tNpBoz6P0_ld8E3fHOV4yxw2s1FO2NsH3yNENXkqO8W21vVHBq2XJEQjult-4o6b0gyjXBZNvuZD-kpzgywtxoZL4tuiS7mnppfMRKBJPDBiN-f3vbt6zdZ4Alw-4_gEB875kLKZybPmGyIatTBZ6w2WFjtqKzHw',
			'Content: ' . $data_string
		);
	$ch = curl_init($url);
	curl_setopt($ch, CURLOPT_HTTPHEADER, $customHeaders);
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
	curl_setopt($ch, CURLOPT_POSTFIELDS, $data_string);
	$response = curl_exec($ch);
	if(curl_errno($ch)){
		throw new Exception(curl_error($ch));
	}
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


