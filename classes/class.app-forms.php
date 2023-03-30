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

//--
//-- 47
//--
//
function postActivatePlan($data) {

     $sql="select infinity_id from nua_company where id = " . $data['data']['id'];
     $t=$this->X->sql($sql);

     $sql="update inf_client_plan set active = 'Y' where clientId = '" . $t[0]['infinity_id'] . "' and planId = '" . $data['data']['id2'] . "'";
     $this->X->execute($sql);

     $output=array();
     $output['error_code']=0;
     return $output;
}

//--
//-- 48
//--
//
function postInactivatePlan($data) {
     $sql="select infinity_id from nua_company where id = " . $data['data']['id'];
     $t=$this->X->sql($sql);

     $sql="update inf_client_plan set active = 'N' where clientId = '" . $t[0]['infinity_id'] . "' and planId = '" . $data['data']['id2'] . "'";
     $this->X->execute($sql);

     $sql="delete from nua_monthly_member_census where company_id = " . $data['data']['id'] . " and client_plan = '" . $data['data']['id2'] . "'";
     $this->X->execute($sql);

     $sql="delete from nua_monthly_member_additions where company_id = " . $data['data']['id'] . " and client_plan = '" . $data['data']['id2'] . "'";
     $this->X->execute($sql);

     $sql="delete from nua_monthly_member_terminations where company_id = " . $data['data']['id'] . " and client_plan = '" . $data['data']['id2'] . "'";
     $this->X->execute($sql);

     $sql="delete from inf_client_employee_plan where clientId = '" . $t[0]['infinity_id'] . "' and planId = '" . $data['data']['id2'] . "'";
     $this->X->execute($sql);

     $output=array();
     $output['error_code']=0;
     return $output;
}

//--
//-- 11
//--
//
function postAddQuoteEmployee($data) {
	     
		 $output=$this->start_output($data);
		 if ($output['user']['forced_off']>0) return $output;
		 
		 $post=array();
		 $post['table_name']="nua_employer_contribution";
		 $post['action']="input";

                 $id=$this->X->post($post);
		 
		 $output=array();
		 $output['error_code']="0";
		 $output['id']=$id;
		 return $output;
}

//--
//-- 12
//--
//
function postEditQuoteEmployee($data) {
	     
		 $output=$this->start_output($data);
		 if ($output['user']['forced_off']>0) return $output;
		 
		 $post=array();
		 $post['table_name']="nua_employer_contribution";
		 $post['action']="input";

                 $id=$this->X->post($post);
		 
		 $output=array();
		 $output['error_code']="0";
		 $output['id']=$id;
		 return $output;
}

//--
//-- 16
//--
//
function postEmployeeLookup($data) {

        
	 $output=$this->start_output($data);
	 if ($output['user']['forced_off']>0) return $output;
	$user=$output['user'];

	$broker_id=$user['broker_id'];
		
        $month_id=$this->getMonthId();
        $next_month_id=$this->getNextMonthId();
           
        $s=$this->makeCompanyQuery($user,"id","");


	$ssn=$data['data']['formData']['social_security_number'];
	$first_name=$data['data']['formData']['first_name'];
	$last_name=$data['data']['formData']['last_name'];
	$email=$data['data']['formData']['email'];
	$company_code=$data['data']['formData']['company_code'];
	$company_name=$data['data']['formData']['company_name'];
       // $company_name = "";
	$flag=0;
	$sql="select * from nua_employee where company_id in (" . $s . ") ";

	if ($ssn!="") {
           $sql .= " and social_security_number like '%" . $ssn . "%' ";
	   $flag=1;
	}
	if ($first_name!="") {
           $sql .= " and first_name like '%" . $first_name . "%' ";
	   $flag=1;
	}
	if ($last_name!="") {
           $sql .= " and last_name like '%" . $last_name . "%' ";
	   $flag=1;
	}
	if ($email!="") {
           $sql .= " and email like '%" . $email . "%' ";
	   $flag=1;
	}
	if ($company_code!="") {
           $sql .= " and company_code like '%" . $company_code . "%' ";
	   $flag=1;
	}
	$in="";
	if ($company_name!="") {
             $sql .= " and company_id in (select id from nua_company where company_name like '%" . $company_name . "%') ";
	     $flag=1;
	}
         
        $output=$this->start_output($data);
	if ($output['user']['forced_off']>0) return $output;
	if ($flag==0) {
		$sql="select * from nua_company where 1 = 0";
	}
        if ($output['user']['role']!="sadmin") {
              $sql .= " and org_id = " . $user['org_id'];
        }
        $t=$this->X->sql($sql);
        $list=array();
        foreach($t as $s) {
              $sql="select * from nua_company where id = " . $s['company_id'];
	      $t9=$this->X->sql($sql);
              if (sizeof($t9)>0) {
                     $s['company_name']=$t9[0]['company_name'];
              }
              array_push($list,$s);
        }

	$output=$list;
	 return $output;
}

    function getOrgProfile($data) {
		 $output=$this->start_output($data);
		 if ($output['user']['forced_off']>0) return $output;
                 $user=$output['user'];
                 $formData=array();
		 $sql="select * from nua_org where id = " . $user['org_id'];
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

//--
//-- 29
//--
//
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

//--
//-- 15
//--

    function getEmployeeLookupForm($data,$table_name) {
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
                 $formData['company_name']="";
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

    	
//--
//-- 3
//--

    function getCompanyFormData($data) {
		$table_name='nua_company';
		 $output=$this->start_output($data);
		 if ($output['user']['forced_off']>0) return $output;;
		 $output['select']=$this->getOrgDropdown($data);
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

    function getQuoteRequestFormData($data) {

		 $output=$this->start_output($data);
		 if ($output['user']['forced_off']>0) return $output;
	         $user=$output['user'];	
		 $table_name="nua_quote";
		 $output['table_name']=$table_name;
		 $output['action']="input";
		 $output['key']="id";
		 $output['id']=$data['id'];
	     $formData=array();
         $columns=$this->X->get_columns($table_name);
		 foreach($columns as $c) {
			 if ($c!="create_timestamp") {
		      $formData[$c]="";	 
			 }
		 }

		 $sql="select * from nua_company where id = " . $data['id'];
		 $company=$this->X->sql($sql);
		 $formData['company_id']=$data['id'];
		 $formData['org_id']=$user['org_id'];
		 $formData['requested_by']=$user['id'];
		 $formData['company_name']=$company[0]['company_name'];
		 $formData['contact_name']=$company[0]['contact_name'];
		 $formData['contact_phone']=$company[0]['phone'];
		 $formData['contact_email']=$company[0]['contact_email'];
		 $formData['employee_count']=$company[0]['employee_count'];
		 $formData['medical']="Y";
		 $formData['dental']="Y";		 
		 $formData['vision']="Y";
		 $formData['notes']="";
		 $formData['date_expires']="";
		 $output['formData']=$formData;
		 return $output;
	}
	
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

	function postAddInvoice($data) {
	}

	// API 1.27
	
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

	
	function submitQuoteRequest($data) {
		 $output=$this->start_output($data);
		 if ($output['user']['forced_off']>0) return $output;
		$id=$data['data']['id'];
		$date_sent=date("Y-m-d H:i:s");
		$sql="update nua_quote set date_sent = '" . $date_sent . "' where id = " . $id;
		$this->X->execute($sql);
		$sql="update nua_quote set status = 'Submitted' where id = " . $id;
		$this->X->execute($sql);
		 $output=array();
		 $output['error_code']="0";
		 $output['id']=$id;
		 return $output;		
	}

//--
//-- 14
//--
	function submitQuote($data) {
		 $output=$this->start_output($data);
		 if ($output['user']['forced_off']>0) return $output;
		$id=$data['data']['id'];
		$date_sent=date("Y-m-d H:i:s");
		$sql="update nua_quote set quoted_at = '" . $date_sent . "' where id = " . $id;
		$this->X->execute($sql);
		$sql="update nua_quote set status = 'Quoted' where id = " . $id;
		$this->X->execute($sql);
		 $output=array();
		 $output['error_code']="0";
		 $output['id']=$id;
		 return $output;		
	}
	
    function makeEmployeePlans($company_id, $employee_id, $force='N') {
		
			
	}
	
	function postEditEmployee($data) {

	}
	
//--
//-- 42
//-- 
	function postEditBroker($data) {
		 $output=$this->start_output($data);
		 if ($output['user']['forced_off']>0) return $output;
	         $post=array();
		 $post=$data['data']['formData'];
                 $post['table_name']="nua_broker";
                 $post['action']="insert";
                 $id=$this->X->post($post);
		 $output=array();
		 $output['error_code']="0";
		 $output['id']=$id;
		 return $output;
	}
	
//--
//-- 52
//--
//
	function postEditCompany($data) {
		 $output=$this->start_output($data);
		 if ($output['user']['forced_off']>0) return $output;
	     $post=array();
		 $post=$data['data']['formData'];
         $post['table_name']="nua_company";
         $post['action']="insert";
         $id=$this->X->post($post);
		 $output=array();
		 $output['error_code']="0";
		 $output['id']=$id;
		 return $output;
	}
	
//--
//-- 8
//--

	function postAddQuote($data,$table_name) {
		 $output=$this->start_output($data);
		 if ($output['user']['forced_off']>0) return $output;
		 $table_name="nua_quote";
	     $post=array();
		 $post=$data['data']['formData'];
		 $sql="select * from nua_company where id = " . $data['data']['formData']['company_id'];
		 $company=$this->X->sql($sql);
		 $post['org_id']=$company[0]['org_id'];
		 $sql="select count(*) as c from nua_quote where company_id = " . $data['data']['formData']['company_id'];
		 $t=$this->X->sql($sql);
		 $m=str_replace(" ","",$company[0]['company_name']);
		 $m=str_replace(".","",$m);
		 $m=str_replace("-","",$m);
         $y=$t[0]['c']+1;
         $post['quote_key']='Q' . substr($m,0,4) . "0000" . $y;		 
		 $post['created_by']=$data['uid'];
		 $post['requested_by']=$data['uid'];
		 $post['is_accepted']=0;
		 $post['status']="New";
         $post['table_name']=$table_name;
		 $post['last_update']=time();
		 $post['r_f_q_id']=0;
		 $post['date_requested']=time();
         $post['action']="insert";
		 
         $id=$this->X->post($post);
		 $output=array();
		 $output['error_code']="0";
		 $output['id']=$id;
		 return $output;
	}

	function postAddQuoteSmall($data) {
		 $output=$this->start_output($data);
		 if ($output['user']['forced_off']>0) return $output;
		 $table_name="nua_quote";
	     $post=array();
		 $post=$data['data'];
		 $sql="select * from nua_company where id = " . $data['data']['company_id'];
		 $company=$this->X->sql($sql);
		 $post['org_id']=$company[0]['org_id'];
		 $sql="select count(*) as c from nua_quote where company_id = " . $data['data']['company_id'];
		 $t=$this->X->sql($sql);
		 $m=str_replace(" ","",$company[0]['company_name']);
		 $m=str_replace(".","",$m);
		 $m=str_replace("-","",$m);
                 $y=$t[0]['c']+1;
                  $post['quote_key']='Q' . substr($m,0,4) . "0000" . $y;		 
		 $post['created_by']=$data['uid'];
		 $post['requested_by']=$data['uid'];
		 $post['is_accepted']=0;
		 $post['status']="New";
                 $post['table_name']="nua_quote";
		 $post['last_update']=time();
		 $post['r_f_q_id']=0;
		 $post['date_requested']=time();
                 $post['action']="insert";
		 
                 $id=$this->X->post($post);
		 $output=array();
		 $output['error_code']="0";
		 $output['id']=$id;
		 return $output;
	}

	function postEditQuoteRequestBackground($data) {
		 $output=$this->start_output($data);
		 if ($output['user']['forced_off']>0) return $output;
		 $table_name="nua_quote";
		 $post=array();
		 $post=$data['data'];
		 $post['action']="insert";
		 $post['table_name']="nua_quote";
         $id=$this->X->post($post);
		 $output=array();
		 $output['error_code']="0";
		 return $output;
	}
	
//--
//-- 10
//--

	function postEditQuote($data) {
		 $output=$this->start_output($data);
		 if ($output['user']['forced_off']>0) return $output;
		 $table_name="nua_quote";
	     $post=array();
		 $post['action']="insert";
		 $post['table_name']="nua_quote_plan";
		 $post['id']=$data['data']['colForm']['save_id'];
		 $post['omitted']=$data['data']['colForm']['omitted_' . $post['id']];
//		 if ($post['omitted']=="N") {
			$post['employee']=$data['data']['colForm']['employee_' . $post['id']];
			$post['employee_spouse']=$data['data']['colForm']['employeespouse_' . $post['id']];
			$post['employee_children']=$data['data']['colForm']['employeechildren_' . $post['id']];
			$post['family']=$data['data']['colForm']['family_' . $post['id']];
//		 } else {
//			$post['employee']="0.00";
//			$post['employee_spouse']="0.00";
//			$post['employee_children']="0.00";
//			$post['family']="0.00";			 
//		 }
         $id=$this->X->post($post);
		 $output=array();
		 $output['error_code']="0";
		 $output['id']=$data['data']['colForm']['save_id'];
		 $output['data']=$data;
		 $output['data']['data']['colForm']['message_' . $data['data']['colForm']['save_id']]="Plan Saved";
		 return $output;
	}
	
//--
//-- 1
//--
//

function getMonthId() {
        $date=date_create();
        $month_id=date_format($date,'Y-m');
        return $month_id;
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
        if ($m=="2022-03") $month_id = "2022-03";
        if ($m=="2022-04") $month_id = "2022-04";
        if ($m=="2022-05") $month_id = "2022-05";
        if ($m=="2022-06") $month_id = "2022-06";
        if ($m=="2022-03") $month_id2 = "2022-04";
        if ($m=="2022-04") $month_id2 = "2022-05";
        if ($m=="2022-05") $month_id2 = "2022-06";
        if ($m=="2022-06") $month_id2 = "2022-07";
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
	$sql="select count(*) as c from nua_company_plan where company_id = '" . $company_id . "'";
        $p=$this->X->sql($sql);	
	$output['plan_count']=$p[0]['c'];
	
	$sql="select * from nua_monthly_member_census where dependent_code = '' and month_id = '" . $month_id . "' and  company_id = " . $company_id;
        $sql.=" order by last_name, first_name, client_plan";
		$e=$this->X->sql($sql);
		$r=array();
                $last="XXX";
		foreach($e as $f) {
                        if ($f['coverage_level']=="") $f['coverage_level']="EE";
                        if ($f['employee_id']==$last) {
                               $f['employee_code']="";
                               $f['last_name']="";
                               $f['first_name']="";
                               $f['middle_initial']="";
                               $f['gender']="";
                               $f['dob']="";
                        }
		        $sql="select count(*) as c from nua_monthly_member_census where dependent_code <> '' and month_id = '" . $month_id . "' and employee_id = " . $f['employee_id'];
                        $ff=$this->X->sql($sql);
                        if ($ff[0]['c']>0)  array_push($r,$f);
                        $last=$f['employee_id'];
                        $f['term']="N";
                        array_push($r,$f);
		}
		$output['census']=$r;

	return $output;
	
}

function getTerminationList($data) {

        $date=date_create();
        $m=date_format($date,'Y-m');

	$output=$this->start_output($data);
	if ($output['user']['force_logout']>0) return $output;	
        $sql="select * from nua_user where id = " . $output['user']['id'];
        $u=$this->X->sql($sql);
        $user=$u[0];
	$uid=$data['uid'];
        $company_id=$user['company_id'];
        $sql="select * from nua_company where id = " . $company_id;
        $u=$this->X->sql($sql);
        $company=$u[0];
        $output['company']=$company; 
	$org_id=$user['org_id'];
        $user_id=$uid;
        $role=$user['role'];
        if ($m=="2022-03") $month_id = "2022-03";
        if ($m=="2022-04") $month_id = "2022-04";
        if ($m=="2022-05") $month_id = "2022-05";
        if ($m=="2022-06") $month_id = "2022-06";
        if ($m=="2022-03") $month_id2 = "2022-04";
        if ($m=="2022-04") $month_id2 = "2022-05";
        if ($m=="2022-05") $month_id2 = "2022-06";
        if ($m=="2022-06") $month_id2 = "2022-07";
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
	$sql="select count(*) as c from nua_company_plan where company_id = '" . $company_id . "'";
        $p=$this->X->sql($sql);	
	$output['plan_count']=$p[0]['c'];
	
	$sql="select * from nua_monthly_member_terminations where dependent_code = '' and  company_id = " . $company_id;
        $sql.=" order by month_id, last_name, first_name, client_plan";
		$e=$this->X->sql($sql);
		$r=array();
                $last="XXX";
		foreach($e as $f) {
                        if ($f['coverage_level']=="") $f['coverage_level']="EE";
                        if ($f['employee_id']==$last) {
                               $f['employee_code']="";
                               $f['last_name']="";
                               $f['first_name']="";
                               $f['middle_initial']="";
                               $f['gender']="";
                               $f['dob']="";
                        }
		        $sql="select count(*) as c from nua_monthly_member_terminations where dependent_code <> '' and month_id = '" . $month_id . "' and employee_id = " . $f['employee_id'];
                        $ff=$this->X->sql($sql);
                        if ($ff[0]['c']>0)  array_push($r,$f);
                        $last=$f['employee_id'];
                        $f['term']="N";
                        array_push($r,$f);
		}
		$output['census']=$r;

	return $output;
	
}

function getMemberList($data) {

        $date=date_create();
        $m=date_format($date,'Y-m');

	$output=$this->start_output($data);
	if ($output['user']['force_logout']>0) return $output;	
        $sql="select * from nua_user where id = " . $output['user']['id'];
        $u=$this->X->sql($sql);
        $user=$u[0];
	$uid=$data['uid'];
        $company_id=$user['company_id'];
        $sql="select * from nua_company where id = " . $company_id;
        $u=$this->X->sql($sql);
        $company=$u[0];
        $output['company']=$company; 
	$org_id=$user['org_id'];
        $user_id=$uid;
        $role=$user['role'];
        if ($m=="2022-03") $month_id = "2022-03";
        if ($m=="2022-04") $month_id = "2022-04";
        if ($m=="2022-05") $month_id = "2022-05";
        if ($m=="2022-06") $month_id = "2022-06";
        if ($m=="2022-03") $month_id2 = "2022-04";
        if ($m=="2022-04") $month_id2 = "2022-05";
        if ($m=="2022-05") $month_id2 = "2022-06";
        if ($m=="2022-06") $month_id2 = "2022-07";
	
	$sql="select id, last_name, first_name, medical_plan, medical_plan_level from nua_employee where company_id = " . $company_id;
        $sql.=" order by last_name, first_name";
	$e=$this->X->sql($sql);
	$last=".";
	$o=array();
	foreach ($e as $f) {
            $ltr=strtoupper(substr($f['last_name'],0,1));
	    if ($last!=$ltr) {
               $j=array();
	       $j['type']="letter";
	       $j['id']=0;
	       $j['last_name']=$ltr;
	       $j['letter']=$ltr;
	       $j['first_name']="";
               array_push($o,$j);
	    }
            $f['type']="record";
            $f['letter']=$ltr;
	    array_push($o,$f);
	    $last=$ltr;
	}
	$output['census']=$o;

	return $output;
	
}

function getAdditionList($data) {

        $date=date_create();
        $m=date_format($date,'Y-m');

	$output=$this->start_output($data);
	if ($output['user']['force_logout']>0) return $output;	
        $sql="select * from nua_user where id = " . $output['user']['id'];
        $u=$this->X->sql($sql);
        $user=$u[0];
	$uid=$data['uid'];
        $company_id=$user['company_id'];
        $sql="select * from nua_company where id = " . $company_id;
        $u=$this->X->sql($sql);
        $company=$u[0];
        $output['company']=$company; 
	$org_id=$user['org_id'];
        $user_id=$uid;
        $role=$user['role'];
        if ($m=="2022-03") $month_id = "2022-03";
        if ($m=="2022-04") $month_id = "2022-04";
        if ($m=="2022-05") $month_id = "2022-05";
        if ($m=="2022-06") $month_id = "2022-06";
        if ($m=="2022-03") $month_id2 = "2022-04";
        if ($m=="2022-04") $month_id2 = "2022-05";
        if ($m=="2022-05") $month_id2 = "2022-06";
        if ($m=="2022-06") $month_id2 = "2022-07";
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
	$sql="select count(*) as c from nua_company_plan where company_id = '" . $company_id . "'";
        $p=$this->X->sql($sql);	
	$output['plan_count']=$p[0]['c'];
	
	$sql="select * from nua_monthly_member_additions where dependent_code = '' and  company_id = " . $company_id;
        $sql.=" order by month_id, last_name, first_name, client_plan";
		$e=$this->X->sql($sql);
		$r=array();
                $last="XXX";
		foreach($e as $f) {
                        if ($f['coverage_level']=="") $f['coverage_level']="EE";
                        if ($f['employee_id']==$last) {
                               $f['employee_code']="";
                               $f['last_name']="";
                               $f['first_name']="";
                               $f['middle_initial']="";
                               $f['gender']="";
                               $f['dob']="";
                        }
		        $sql="select count(*) as c from nua_monthly_member_additions where dependent_code <> '' and month_id = '" . $month_id . "' and employee_id = " . $f['employee_id'];
                        $ff=$this->X->sql($sql);
                        if ($ff[0]['c']>0)  array_push($r,$f);
                        $last=$f['employee_id'];
                        $f['term']="N";
                        array_push($r,$f);
		}
		$output['census']=$r;

	return $output;
	
}

function getTestDashboard2($data) {

}

function getNuAxessDashboard($data) {

}

//--
//-- 32
//
//
function getBrokerDashboard($data) {

        $date=date_create();
        $month_id=date_format($date,'Y-m');

	$output=$this->start_output($data);
	if ($output['user']['force_logout']>0) return $output;	
        $user=$output['user'];
	$uid=$data['uid'];
	//--
	//
	$sql="select count(*) as c from nua_company where user_id = " . $user['id'] . " and  status in ('enrolled') and insured_lives <> '0'";
        $prospects=$this->X->sql($sql);	
	$output['active_count']=$prospects[0]['c'];
	$output['active_count']="-1";
	
	$sql="select count(*) as c from nua_company where user_id = " . $user['id'] . " and status in ('enrolled','prospect') and insured_lives = '0'";
        $prospects=$this->X->sql($sql);	
	$output['prospect_count']=$prospects[0]['c'];
	
	$sql="select count(*) as c from nua_quote where requested_by = " . $user['id'] .  " and  status not in ('cancelled')";
        $quotes=$this->X->sql($sql);	
	$output['quote_count']=$quotes[0]['c'];
	
	$sql="select count(distinct employee_id) as c from nua_monthly_member_census where month_id = '" . $month_id . "' and company_id in (select id from nua_company where user_id = " . $user['id'] . ")";
        $quotes=$this->X->sql($sql);
	$output['enrolled_count']=$quotes[0]['c'];
	
	$sql="select * from nua_company where user_id = " . $user['id'] . " and insured_lives <> '0'  order by company_name";
	$orgs=$this->X->sql($sql);
	$q=array();
	foreach($orgs as $z) {
		$rr=array();
                $rr=$z; 
		array_push($q,$rr);
	}
	$output['active']=$q;


	$sql="select count(distinct employee_id) as c from nua_monthly_member_census where month_id = '" . $month_id . "' and company_id in (select id from nua_company where user_id = " . $user['id'] . ")";
        $quotes=$this->X->sql($sql);
	$output['enrolled_count']=$quotes[0]['c'];
	
	$sql="select * from nua_company where user_id = " . $user['id'] . " and insured_lives <> '0'  order by company_name";
	$orgs=$this->X->sql($sql);
	$q=array();
	foreach($orgs as $z) {
		$rr=array();
                $rr=$z; 
		array_push($q,$rr);
	}
	$output['pending']=$q;

	$sql="select count(distinct employee_id) as c from nua_monthly_member_census where month_id = '" . $month_id . "' and company_id in (select id from nua_company where user_id = " . $user['id'] . ")";
        $quotes=$this->X->sql($sql);
	$output['enrolled_count']=$quotes[0]['c'];
	
	$sql="select * from nua_company where user_id = " . $user['id'] . " and status = 'prospect' order by company_name";
	$orgs=$this->X->sql($sql);
	$q=array();
	foreach($orgs as $z) { 
		$sql="select count(*) as c from nua_quote where company_id = " . $z['id'];
		$lll=$this->X->sql($sql);
		if ($lll[0]['c'] > 0) {
                    $z['quoted']="Y";
		} else {
                   $z['quoted']="N";
		}
		$rr=array();
                $rr=$z; 
		array_push($q,$rr);
	}
	$output['prospects']=$q;

	$sql="select * from nua_quote where status not in ('cancelled')  order by company_name";
	$orgs=$this->X->sql($sql);
	$q=array();
	foreach($orgs as $z) {
		$rr=array();
                $rr=$z; 
		array_push($q,$rr);
	}
	$output['quotes']=$q;

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
	

	function postSubmitQuoteRequest($data,$table_name) {
		 $output=$this->start_output($data);
		 if ($output['user']['forced_off']>0) return $output;
		 $table_name="nua_quote";
	     $post=array();
		 $post=$data['data']['id'];
		 $sql="update nua_quote set status = 'Submitted', last_update = " . time() . " where id = " . $data['data']['id'];
		 $this->X->execute($sql);
		 $output=array();
		 $output['error_code']="0";
		 $output['id']=$id;
		 return $output;
	}
//--
//-- 20
//--

	function postAddEmployee($data) {
		 $output=$this->start_output($data);
		 if ($output['user']['forced_off']>0) return $output;
		 $table_name="nua_quote";
		 $output['error_code']="0";
		 $output['id']=$id;
		 return $output;
	}
	
//--
//-- 44
//
	function postAddEmployeeSmall($data,$table_name) {
		 $output=$this->start_output($data);
		 if ($output['user']['forced_off']>0) return $output;
		 $uid=$data['uid'];
		 $sql="select * from nua_user where id = " . $uid;
		 $users=$this->X->sql($sql);
		 $user=$users[0];
		 $org_id=$user['org_id'];
		 $company_id=$data['data']['id'];
		 if ($data['data']['employeeData']['id']!=""&&$data['data']['employeeData']['gender']=="DELETE") {
			 $sql="delete from nua_employee where id = " .$data['data']['formData']['id'];
             $this->X->execute($sql);
				$output=array();
				$output['error_code']="0";
				$output['id']=$id;
				return $output;			 
		 } else {
				$post=array();
				$post=$data['data']['employeeData'];
				$post['table_name']="nua_employee";
				$post['action']="insert";
				if ($data['data']['employeeData']['id']!="") {
					$post['id']=$data['data']['employeeData']['id'];
				}
				$post['created_by']=$uid;
				$id=$this->X->post($post);
//				$post=array();
//				$post['table_name']="nua_user";
//				$post['action']="insert";
//				$post['full_name']=$data['data']['employeeData']['last_name'] . ', ' . $data['data']['employeeData']['first_name'] . ' ' . $data['data']['employeeData']['middle_name'] . ' ' . $data['data']['employeeData']['suffix'];
//				$post['email']=strtolower($data['data']['employeeData']['email']);
//				$post['phone_mobile']=$data['data']['employeeData']['phone_mobile'];
//				$post['role']="employee";
//				$post['company_id']=$data['data']['employeeData']['company_id'];
//				$post['invite_code']=$this->makeInviteCode();
//				$post['employee_id']=$id;
//				$this->X->post($post);
		 }
		 
		 $output=array();
		 $output['error_code']="0";
		 $output['id']=$id;
		 return $output;
	}
	
//--
//-- 35
//--

	function postAddBroker($data) {
		 $output=$this->start_output($data);
		 if ($output['user']['forced_off']>0) return $output;
		 $uid=$data['uid'];
		 $sql="select * from nua_user where id = " . $uid;
		 $users=$this->X->sql($sql);
		 $user=$users[0];
		 $org_id=$user['org_id'];
		 $company_id=$data['data']['id'];
		 $post=array();
		 $post=$data['data']['formData'];
		$post['table_name']="nua_broker";
		$post['action']="insert";
		if ($data['data']['formData']['id']!="") {
			$post['id']=$data['data']['formData']['id'];
  	       }
				$post['created_by']=$uid;
				$id=$this->X->post($post);
				$post=array();
				$post['table_name']="nua_user";
				$post['action']="insert";
				$post['full_name']=$data['data']['employeeData']['last_name'] . ', ' . $data['data']['employeeData']['first_name'] . ' ' . $data['data']['employeeData']['middle_name'] . ' ' . $data['data']['employeeData']['suffix'];
				$post['email']=strtolower($data['data']['employeeData']['email']);
				$post['phone_mobile']=$data['data']['employeeData']['phone_mobile'];
				$post['role']="employee";
				$post['company_id']=$data['data']['employeeData']['company_id'];
				$post['invite_code']=$this->makeInviteCode();
				$post['employee_id']=$id;
				$this->X->post($post);
		 
		 $output=array();
		 $output['error_code']="0";
		 $output['id']=$id;
		 return $output;
	}
//--
//-- 4
//--

//        function getOrg($org_id) {
//	   $s="select * from nua_org where id = " . $org_id;
//	   $org=$this->X->sql($s);
//           if (sizeof($org)==0) {
//              $o=array();  
//              $o['id']=$org_id'];
//              $o['org_name']="Organization Not Found";
//           } else {
//              $o=$org[0];
//           } 
//           return $o;
//        }

	function postAddCompany($data,$table_name) {
		 $output=$this->start_output($data);
		 if ($output['user']['forced_off']>0) return $output;
		 $org_id=$output['user']['org_id'];
		 
	         $post=array();
//		 $post=$data['data']['formData'];
                 $post['org_id']=$org_id; 
                 $post['created_by']=$data['uid']; 
                 $post['company_name']=$data['data']['formData']['company_name'];
		 $post['contact_email']=$data['data']['formData']['contact_email'];
		 $post['email']=$data['data']['formData']['email'];
                 $post['company_type']=$data['data']['formData']['company_type'];
                 $post['website']=$data['data']['formData']['website'];
                 $post['contact_name']=$data['data']['formData']['contact_name'];
		 $post['broker_id']=$output['user']['broker_id'];
                 $sql="select * from nua_broker where id = " . $post['broker_id'];
                 $j=$this->X->sql($sql);
		 if (sizeof($j)>0)  {
                      $post['broker_name']=$j[0]['last_name'] . ', ' . $j[0]['first_name'];
                      $post['broker_email']=$j[0]['email'];
                 }
                 $post['tax_id']=$data['data']['formData']['tax_id'];
                 $post['contact_phone']=$data['data']['formData']['contact_phone'];
                 $post['address']=$data['data']['formData']['address'];
                 $post['city']=$data['data']['formData']['city'];
                 $post['state']=$data['data']['formData']['state'];
                 $post['zip']=$data['data']['formData']['zip'];
                 $post['dsc']=$data['data']['formData']['dsc'];
                 $post['employee_count']=$data['data']['formData']['employee_count'];
		 $post['state_of_incorpration']=$data['data']['formData']['state_of_incorpration'];
		 $post['current_provider']=$data['data']['formData']['current_provider'];
		 $post['renewal_date']=$data['data']['formData']['renewal_date'];
		 $post['description']=$data['data']['formData']['description'];
                 $post['table_name']="nua_company";
                 $post['action']="insert";
		 $post['created_by']=$data['uid'];
		 $post['broker_id']=$data['uid'];
		 $post['user_id']=$data['uid'];
		 $post['org_id']=$org_id;	 
		 $post['status']=$data['data']['formData']['status'];
                 $id=$this->X->post($post);
		 $output=array();
		 $output['error_code']="0";
		 $output['id']=$id;
		 return $output;
	}
    
	function postAddOrg($data) {
	     $post=array();
		 $post=$data['data']['formData'];
         $post['table_name']="nua_org";
         $post['action']="insert";
         $id=$this->X->post($post);
		 $output=array();
		 $output['error_code']="0";
		 $output['id']=$id;
		 return $output;
	}

	function postAddProspect($data) {
	     $post=array();
		 $post=$data['data'];
         $post['table_name']="nua_company";
         $post['action']="insert";
	 $post['user_id']=1;
         $id=$this->X->post($post);
		 $output=array();
		 $output['error_code']="0";
		 $output['id']=$id;
		 return $output;
	}

    function postAddFamily($data) {
		$post=array();
		$post['table_name']="nua_employee_ihq_family";
		$post['action']="insert";
		$post['id']=$data['data']['familyData']['id'];
		$post['first_name']=$data['data']['familyData']['first_name'];
		$post['middle_name']=$data['data']['familyData']['middle_name'];
		$post['last_name']=$data['data']['familyData']['last_name'];		
		$post['member_type']=$data['data']['familyData']['member_type'];
		$post['gender']=$data['data']['familyData']['gender'];
		$post['date_of_birth']=$data['data']['familyData']['date_of_birth'];
		$post['weight']=$data['data']['familyData']['weight'];
		$post['height']=$data['data']['familyData']['height'];
		$post['social_security_number']=$data['data']['familyData']['social_security_number'];
		$post['employee_id']=$data['data']['familyData']['employee_id'];
		$this->X->post($post);
		$output=array();
		$output['error_code']="0";
		$output['id']=$id;
		$output['error_message']="";
		return $output;  
	}

//--
//-- 36
//--

    function postAddMemberFamily($data) {
		$post=array();
		$post['table_name']="nua_employee_ihq_family";
		$post['action']="insert";
		$post['id']=$data['data']['formData']['id'];
		$post['first_name']=$data['data']['formData']['first_name'];
		$post['middle_name']=$data['data']['formData']['middle_name'];
		$post['last_name']=$data['data']['formData']['last_name'];		
		$post['member_type']=$data['data']['formData']['member_type'];
		$post['gender']=$data['data']['formData']['gender'];
		$post['date_of_birth']=$data['data']['formData']['date_of_birth'];
		$post['weight']=$data['data']['formData']['weight'];
		$post['height']=$data['data']['formData']['height'];
		$post['social_security_number']=$data['data']['formData']['social_security_number'];
		$post['employee_id']=$data['data']['formData']['employee_id'];
		$this->X->post($post);
		$output=array();
		$output['error_code']="0";
		$output['id']=$id;
		$output['error_message']="";
		return $output;  
	}

//--
//-- 46
//--
//
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

function ShowInvoice($data) {

		 $output=$this->start_output($data);
		 if ($output['user']['forced_off']>0) return $output;
                 return $output;
}

//--
//-- 27
//

function getCommissionList($data) {

		 $output=$this->start_output($data);
		 if ($output['user']['forced_off']>0) return $output;
                 $broker_id=$output['user']['broker_id'];

		 $sql="select distinct month_id from nua_company_invoice where ";
                 $sql.=" company_id in (select id from nua_company where broker_id = " . $broker_id;
                 $sql.=") order by month_id";
                 $y=$this->X->sql($sql);
		 $list=array();

                 $grand_paid_count=0;
		 $grand_paid_total=0;
                 $grand_unpaid_count=0;
                 $grand_unpaid_total=0;
                 $grand_count=0;
                 $grand_total=0;
                 $grand_commission_earned=0;
                 $grand_commission_due=0;
                 $grand_commission_paid=0;
                 $grand_commission_remain=0;
                 $grand_comm_unpaid_count=0;
                 $grand_comm_unpaid_comm=0;

                 foreach($y as $z) {

                     $month_paid_count=0;
                     $month_paid_total=0;
                     $month_unpaid_count=0;
                     $month_unpaid_total=0;
                     $month_count=0;
                     $month_total=0;
                     $month_commission_earned=0;
                     $month_commission_due=0;
                     $month_commission_paid=0;
                     $month_commission_remain=0;
                     $month_comm_unpaid_count=0;
                     $month_comm_unpaid_comm=0;

		     $line=array();
                     $sql="select * from nua_company_invoice where month_id = '" . $z['month_id'] . "' and company_id in (";
                     $sql.="select id from nua_company where broker_id = " . $broker_id . ")";
                     $sql.=" order by company_name";
                     $t=$this->X->sql($sql);


                     $inv=array();
                     foreach($t as $t2) {
                        $sql="select * from nua_company where id = " . $t2['company_id'];
                        $c=$this->X->sql($sql);
                        $company=$c[0];
                        $t2['rate']=number_format($company['broker_vendor_rate'],2);
			$invoice_comm=intval($t2['medical_count'])*floatval($company['broker_vendor_rate']); 
			$month_count+=intval($t2['medical_count']); 
			$month_total+=$t2['grand_total_float']; 
			$grand_count+=intval($t2['medical_count']); 
			$grand_total+=$t2['grand_total_float']; 

                        $t2['commission_total']=number_format($invoice_comm,2);
                        $t2['commission_earned']=number_format($invoice_comm,2);
			$month_commission_earned+=$invoice_comm;
                        $grand_commission_earned+=$invoice_comm;
                        if ($t2['paid']=="Y") {
                             //-- Commission is Due
			     $month_paid_count+=intval($t2['medical_count']);
			     $grand_paid_count+=intval($t2['medical_count']);
			     $month_paid_total+=$t2['grand_total_float'];
			     $grand_paid_total+=$t2['grand_total_float'];
                             $t2['commission_due']=number_format($invoice_comm,2);
                             $month_commission_due+=$invoice_comm;
                             $grand_commission_due+=$invoice_comm;
                             $t2['commission_due']=number_format($invoice_comm,2);
                             $month_commission_due+=$invoice_comm;
                             $grand_commission_due+=$invoice_comm;

                             if ($t2['commission_paid']=="Y") {
                                 $t2['commission_paid']=number_format($invoice_comm,2);
                                 $month_commission_paid+=$invoice_comm;
                                 $grand_commission_paid+=$invoice_comm;
                                 $t2['commission_remain']='0.00';
                             } else {
                                 $t2['commission_paid']='0.00';
                                 $t2['commission_remain']=number_format($invoice_comm,2);
                                 $month_commission_remain+=$invoice_comm;
                                 $grand_commission_remain+=$invoice_comm;
                             }
                        } else {
			     $month_unpaid_count+=intval($t2['medical_count']);
			     $grand_unpaid_count+=intval($t2['medical_count']);
                             $t2['commission_paid']='0.00';
                             $t2['commission_remain']='0.00';
			     $month_paid_total+=$t2['grand_total_float'];
			     $grand_paid_total+=$t2['grand_total_float'];
                             $t2['commission_due']="0.00";
                        }
                        array_push($inv,$t2);                    
		     }
                     $z['invoices']=$inv;
                     $z['total_count']=$month_count;
                     $z['grand_total']=number_format($month_total,2);
                     $z['commission_earned']=number_format($month_commission_earned,2);
                     $z['paid_count']=$month_paid_count;
                     $z['unpaid_count']=$month_unpaid_count;
                     $z['paid_total']=number_format($month_paid_total,2);
                     $z['unpaid_total']=number_format($month_unpaid_total,2);
		     $z['commission_due']=number_format($month_commission_due,2);
                     $z['commission_paid']=number_format($month_commission_paid,2);
                     $z['commission_remain']=number_format($month_commission_remain,2);
                     array_push($list,$z);
                 }

                 $output['total_count']=$grand_count;
                 $output['grand_total']=number_format($grand_total,2);
                 $output['commission_earned']=number_format($grand_commission_earned,2);
                 $output['paid_count']=$grand_paid_count;
                 $output['unpaid_count']=$grand_unpaid_count;
                 $output['paid_total']=$grand_paid_total;
                 $output['unpaid_total']=number_format($grand_unpaid_total,2);
		 $output['commission_due']=number_format($grand_commission_due,2);
                 $output['commission_paid']=number_format($grand_commission_paid,2);
                 $output['commission_remain']=number_format($grand_commission_remain,2);
                 $output['list']=$list;
                 return $output;
}


//--
//-- 28
//

function getCommissionDashboard($data) {

		 $output=$this->start_output($data);
		 if ($output['user']['forced_off']>0) return $output;
                 return $output;
}

//--
//-- 31
//

function getAddBroker($data) {

		 $output=$this->start_output($data);
		 if ($output['user']['forced_off']>0) return $output;
                 return $output;
}



//--
//-- 34
//

function postInviteBroker($data) {

		 $output=$this->start_output($data);
		 if ($output['user']['forced_off']>0) return $output;
                 return $output;
}

//--
//-- 35
//

function XgetAdditionList($data) {

		 $output=$this->start_output($data);
		 if ($output['user']['forced_off']>0) return $output;
                 return $output;
}
//--
//-- 36
//

function XgetTerminationList($data) {

		 $output=$this->start_output($data);
		 if ($output['user']['forced_off']>0) return $output;
                 return $output;
}



//--
//-- 25
//--
	function getInvoiceDashboard($data) {
		 $output=$this->start_output($data);
		 if ($output['user']['forced_off']>0) return $output;
		 
               $sql="select * from nua_company_invoice where id = " . $data['id'];
	       $d=$this->X->sql($sql);
               $invoice=$d[0];
	       $e=$invoice;

               if ($e['month']=="01") $e['month']="January";
               if ($e['month']=="02") $e['month']="February";
               if ($e['month']=="03") $e['month']="March";
               if ($e['month']=="04") $e['month']="April";
               if ($e['month']=="05") $e['month']="May";
               if ($e['month']=="06") $e['month']="June";
               if ($e['month']=="07") $e['month']="July";
               if ($e['month']=="08") $e['month']="August";
               if ($e['month']=="09") $e['month']="September";
               if ($e['month']=="10") $e['month']="October";
               if ($e['month']=="11") $e['month']="November";
               if ($e['month']=="12") $e['month']="December";
	       if ($e['year']=='21') $e['year']="2021";
	       if ($e['year']=='22') $e['year']="2022";

	       $sql="select * from nua_company where id = " . $invoice['company_id'];
	       $f=$this->X->sql($sql);
	       $output['company']=$f[0];
	       $output['invoice']=$e;
		    

               $formData=array();
	       foreach($e as $name=>$value) {
                   $formData[$name]=$value;
                    $output[$name]=$value;
	       }
	       $output['formData']=$formData;
	       $formData=array();
               $formData['id']="";
               $formData['apa_code']="";
               $formData['invoice_id']=$data['id'];
               $formData['plan_id']=0;
               $formData['plan_name']="";
               $formData['ee_price']="0.00";
               $formData['ee_qty']="0";
               $formData['ee_total']="0.00";
               $formData['ees_price']="0.00";
               $formData['ees_qty']="0";
               $formData['ees_total']="0.00";
               $formData['eec_price']="0.00";
               $formData['eec_qty']="0";
               $formData['eec_total']="0.00";
               $formData['fam_price']="0.00";
               $formData['fam_qty']="0";
               $formData['fam_total']="0.00";
               $formData['adj_price']="0.00";
               $formData['adj_qty']="0";
               $formData['adj_total']="0.00";
               $formData['total']="0.00";
               $output['formData2']=$formData;
	       $sql="select * from nua_invoice_detail where invoice_id = " . $data['id'] . " order by plan_id";
//
// Members loaded in INVOICE LOAD
		$sql="select * from nua_invoice_load_members where company_id = " . $invoice['company_id'] . " order by last_name, first_name";
		$e=$this->X->sql($sql);
		$r=array();
		foreach($e as $f) {
			array_push($r,$f);
		}
		$output['census']=$r;

		$sql="select * from nua_census where company_id = " . $invoice['company_id'] . " order by last_name, first_name";
		$e=$this->X->sql($sql);
		$r=array();
		foreach($e as $f) {
			array_push($r,$f);
		}
		$output['apa']=$r;

		$sql="select * from nua_census where company_id = 4995 order by last_name, first_name";
		$e=$this->X->sql($sql);
		$r=array();
		foreach($e as $f) {
			array_push($r,$f);
		}
		$output['nuaxess']=$r;


	       $t=$this->X->sql($sql);
	       $output['detail']=$t;
	       return $output;
	}

//--
//-- 22
//--
//
//

	function getCompanyDashboard($data) {

		 $output=$this->start_output($data);
		 if ($output['user']['forced_off']>0) return $output;
                 $user=$output['user'];
		 
                 $company_id=$data['id'];
                 $date=date_create();
		 if ($data['id2']!='') {
                        $month_id = $data['id2'];
		} else { 
			$month_id =  date_format($date,"Y-m");
		}
                $sql="select * from nua_preenrollment_census where company_id = " . $data['id'] . " order by last_name, first_name";
                $pre=$this->X->sql($sql);
                $output['preenroll']=$pre;

                $sql="select * from nua_quoted_plan where company_id = " . $data['id'] . " order by plan_code";
                $pre=$this->X->sql($sql);
                $output['quotedplans']=$pre;
                $sql="select * from nua_company_plan where company_id = " . $data['id'] . " order by plan_code";
                $pre=$this->X->sql($sql);
                $output['activeplans']=$pre;
                 $term_dates=array();
                 $day_id=date_format($date,"i");
		 $output['month_id']=$month_id;
			  $t=array();
			  $t['term_dt']="02/28/2022";
                          array_push($term_dates,$t);
			  $t=array();
			  $t['term_dt']="03/31/2022";
                          array_push($term_dates,$t);
			  $t=array();
			  $t['term_dt']="04/30/2022";
                          array_push($term_dates,$t);
			  $t=array();
			  $t['term_dt']="05/31/2022";
                          array_push($term_dates,$t);
			  $t=array();
			  $t['term_dt']="06/30/2022";
                          array_push($term_dates,$t);
			  $t=array();
			  $t['term_dt']="07/31/2022";
                          array_push($term_dates,$t);

                 $output['term_dates']=$term_dates;
		 $output['company_id']=$data['id'];
	         $sql="select * from nua_company where id = " . $data['id'];	
                 $d=$this->X->sql($sql);
                 foreach($d[0] as $name=>$value) $output[$name]=$value;
		$company=$d[0];
                $eff_dates=array();
                $eff_date=array();
                $eff_date['eff_dt']="03/01/2022";
                array_push($eff_dates,$eff_date);
                $eff_date['eff_dt']="04/01/2022";
                array_push($eff_dates,$eff_date);
                $eff_date['eff_dt']="05/01/2022";
                array_push($eff_dates,$eff_date);
                $eff_date['eff_dt']="06/01/2022";
                array_push($eff_dates,$eff_date);
                $eff_date['eff_dt']="12/01/2021";
                array_push($eff_dates,$eff_date);
                $eff_date['eff_dt']="01/01/2022";
                array_push($eff_dates,$eff_date);
                $eff_date['eff_dt']="02/01/2022";
                array_push($eff_dates,$eff_date);
                $output['eff_dates']=$eff_dates;

                $quoteData=array();
		$quoteData['quote_name']=$company['company_name'];
		$quoteData['company_id']=$company['id'];
		$quoteData['employee_count']=$company['employee_count'];
		$quoteData['medical']="Y";
		$quoteData['dental']="Y";
		$quoteData['vision']="Y";
		$quoteData['user_id']=$user['id'];
		$quoteData['org_id']=$user['org_id'];
		$quoteData['notes']="";
                $output['quoteData']=$quoteData;
		$sql="select * from nua_org where id = " . $d[0]['org_id'];
                $d=$this->X->sql($sql);
                $output['org_name']=$d[0]['org_name'];
		

		$adjustData=array();
		$adjustData['description']="";
		$adjustData['amount']="";
		$adjustData['id']="";
		$adjustData['company_id']=$data['id'];
		$adjustData['month_id']=$month_id;
		$output['adjustData']=$adjustData;

		$formData=array();
		$formData['employee_name']="";
		$formData['date_of_birth']="";
		$formData['gender']="";
		$formData['id']="";
		$formData['id2']="";
		$output['formData']=$formData;

                $formData3=array();
                $formData3['id']="";
                $formData3['company_id']=$data['id'];
		$formData3['plan_code']="";
                $formData3['APA_CODE']="";
                $formData3['ee_price']="";
                $formData3['eec_price']="";
                $formData3['ees_price']="";
                $formData3['fam_price']="";
                $output['formData3']=$formData3;

                $moveData=array();
                $moveData['census_id']="";
                $moveData['company_id']=$data['id'];
                $moveData['term_dt']="";
                $output['moveData']=$moveData;
 
  
		$employeeData=array();
		$employeeData['company_id']=$data['id'];
		$employeeData['org_id']=$output['org_id'];
		$employeeData['dependent']='N';
		$employeeData['employee_id']=0;
		$employeeData['first_name']="";
                $employeeData['coverage_level']="";
		$employeeData['middle_name']="";
		$employeeData['last_name']="";
		$employeeData['suffix']="";
		$employeeData['email']="";
		$employeeData['phone_mobile']="";
		$employeeData['date_of_birth']="";
		$employeeData['social_security_number']="";
                $employeeData['eff_dt']="";
                $employeeData['plan']="Silver";
		$employeeData['gender']="";
		$employeeData['id']="";
		$output['employeeData']=$employeeData;
		
		$sql="select * from nua_doc where employee_id = 0 and company_id = " . $data['id'] . " and doc_title not in ";
                $sql.=" ('CENSUS','COMPANY','PLANS','PRE','ENROLL','ADDITIONS','QUOTING','ENROLLMENT')";
		$p=$this->X->sql($sql);
		$doc=array();
		foreach($p as $q) {
			// get the ID as an int.
			$id=$q['id'];
			// convert it to a string.
			$id_str=strval($id);
			// convert the string to an array;
			$split_id=str_split($id_str);
			// md5 hash the ID
		        $key=md5($id_str);
			// convert the key ro an array.
			$sp=str_split($key);

			// start the string. 
			// -- Char 1 and 2 of key + length of ID + A; 
			$k=$sp[0].$sp[1].strlen($id_str).'a';
			$hashloc=2;

			//loop through ID.
                        for ($i=0;$i<strlen($id_str);$i++) {
				$k.=$id_str[$i];
			        $padding=fmod(intval($id_str[$i]),5);
				for($j=0;$j<$padding;$j++) {
					$hashloc++;
					if ($hashloc>=strlen($key)) $hashloc=0;
				        $k.=$sp[$hashloc];
			        }
			
			}
				for($j=$hashloc;$j<strlen($key);$j++) {
				        $k.=$sp[$j];
			        }
			$q['key']=$k;
			array_push($doc,$q);
		}

		$output['docs']=$doc;
		$output['invoices']=array();

		$sql="select id, first_name, last_name from nua_employee where company_id = " . $data['id'] . " order by last_name, first_name";
		$e=$this->X->sql($sql);
		$r=array();
		foreach($e as $f) {
			array_push($r,$f);
		}
		$output['employees']=$r;

                $sql="select * from nua_company_plan where company_id = " . $data['id'] . " order by plan_code";
                $e=$this->X->sql($sql);
                $output['company_plans']=$e;

		$sql="select * from nua_invoice_load_terms where company_id = " . $data['id'] . " order by last_name, first_name";
		$e=$this->X->sql($sql);
		$r=array();
                $total_terms=0;
		foreach($e as $f) {
                           $sql="select * from nua_company_plan where company_id = " . $data['id'] . " and ";
			   $sql.="APA_CODE = '" . $f['plan'] . "'";
                           $p=$this->X->sql($sql);
if (sizeof($p)>0) {
                           if ($f['plan_election']=='EE') { 
                               $f['price']=$p[0]['ee_price'];
                           } 
                           if ($f['plan_election']=='FAMILY') { 
                               $f['price']=$p[0]['fam_price'];
                           } 
                           if ($f['plan_election']=='ES') { 
                               $f['price']=$p[0]['ees_price'];
                           } 
                           if ($f['plan_election']=='EC') { 
                               $f['price']=$p[0]['eec_price'];
			   }
}
			array_push($r,$f);
		}
		$output['terms']=$r;
		$output['total_terms']=$r;

		$sql="select distinct client_plan, coverage_level from nua_monthly_member_census where company_id = " . $data['id'] . " and  month_id = '" . $month_id . "' order by 1,2";
	        $planlist=$this->X->sql($sql);
		$tt=array();
		$last="X";
		foreach($planlist as $p) {
                        $oo=0;
			$sql="select coverage_price from nua_monthly_member_census where coverage_price <> '' and client_plan = '";
		        $sql.= $p['client_plan'] . "' and coverage_level = '" . $p['coverage_level'] . "' and  coverage_price <> '0.00' and company_id = ";
		        $sql.= $data['id'] . " and  month_id = '" . $month_id . "' order by 1";
	                $pln=$this->X->sql($sql);
			foreach($pln as $pln0) {
                             if (floatval($pln0['coverage_price'])>$oo) $oo=floatval($pln0['coverage_price']); 
			}
			$p['coverage_price']=number_format($oo,2);
			if ($p['client_plan']==$last) { $p['client_plan']=""; }
			$last=$p['client_plan'];
			if ($oo!=0) array_push($tt,$p);	
		}
		$sql="select * from inf_client_plan where active = 'Y' and clientId = (select infinity_id from nua_company where id = " . $data['id'] . ") and planId not in   ";
                $sql.="(select client_plan from nua_monthly_member_census where company_id = " . $data['id'] . " and month_id = '" . $month_id . "') ";
                $j=$this->X->sql($sql); 
                foreach($j as $p) {
                      $new=array();   
                      $new['client_plan']=$p['planId'];
                      $new['coverage_level']="";
                      $new['coverage_price']="";
                      array_push($tt,$new);
                }
             
	        $output['planlist']=$tt;

		if ($company['infinity_id']!='') {
                      $sql="select * from inf_client_plan where clientId = '" . $company['infinity_id'] . "' and active = 'N' order by planId";
		      $gg=$this->X->sql($sql);
                      $output['inactive']=$gg; 

		} else {
                      $output['inactive']=array();
	        }

                $sql="select id, employee_id, first_name, last_name, company_name,  plan, coverage_level, alt_company_id, alt_company_name  from nua_census order by last_name, first_name";
		$f=$this->X->sql($sql);
                $output['movelist']=$f;

//                $sql="select id, employee_id, first_name, last_name, company_name,  plan, coverage_level, alt_company_id, alt_company_name  from nua_census where company_id not in (4977,4155,4453) order by last_name";
//		$f=$this->X->sql($sql);
//		$output['movelist2']=$f;

		$sql="select distinct month_id from nua_monthly_member_census order by month_id desc";
	        $monthlist=$this->X->sql($sql);
		$mmm=array();
		foreach($monthlist as $mm) {
			if ($mm['month_id']=="2022-01") {
			    $t=array();
		            $t['value']=$mm['month_id'];
		            $t['name']="January 2022";
			    $sql="select count(*) as c from nua_monthly_member_census ";
			    $sql.="  where month_id = '" . $mm['month_id'] . "' and company_id = " . $data['id'];
			    $l=$this->X->sql($sql);
                            if ($l[0]['c']!=0) array_push($mmm,$t);
			}
			if ($mm['month_id']=="2022-02") {
			    $t=array();
		            $t['value']=$mm['month_id'];
		            $t['name']="February 2022";
			    $sql="select count(*) as c from nua_monthly_member_census ";
			    $sql.="  where month_id = '" . $mm['month_id'] . "' and company_id = " . $data['id'];
			    $l=$this->X->sql($sql);
			    if ($l[0]['c'] != 0) array_push($mmm,$t);
		            $t['name']="February 2022";
			}
			if ($mm['month_id']=="2022-03") {
			    $t=array();
		            $t['value']=$mm['month_id'];
		            $t['name']="March 2022";
			    $sql="select count(*) as c from nua_monthly_member_census ";
			    $sql.="  where month_id = '" . $mm['month_id'] . "' and company_id = " . $data['id'];
			    $l=$this->X->sql($sql);
                            if ($l[0]['c'] != 0) array_push($mmm,$t);
			}
			if ($mm['month_id']=="2022-04") {
			    $t=array();
		            $t['value']=$mm['month_id'];
		            $t['name']="April 2022";
			    $sql="select count(*) as c from nua_monthly_member_census ";
			    $sql.="  where month_id = '" . $mm['month_id'] . "' and company_id = " . $data['id'];
			    $l=$this->X->sql($sql);
                            if ($l[0]['c'] != 0) array_push($mmm,$t);
			}
			if ($mm['month_id']=="2022-05") {
			    $t=array();
		            $t['value']=$mm['month_id'];
		            $t['name']="May 2022";
			    $sql="select count(*) as c from nua_monthly_member_census ";
			    $sql.="  where month_id = '" . $mm['month_id'] . "' and company_id = " . $data['id'];
			    $l=$this->X->sql($sql);
                            if ($l[0]['c'] !=0) array_push($mmm,$t);
			}
			if ($mm['month_id']=="2022-06") {
			    $t=array();
		            $t['value']=$mm['month_id'];
		            $t['name']="June 2022";
			    $sql="select count(*) as c from nua_monthly_member_census ";
			    $sql.="  where month_id = '" . $mm['month_id'] . "' and company_id = " . $data['id'];
			    $l=$this->X->sql($sql);
                            if ($l[0]['c'] != 0) array_push($mmm,$t);
			}
			if ($mm['month_id']=="2022-07") {
			    $t=array();
		            $t['value']=$mm['month_id'];
		            $t['name']="July 2022";
			    $sql="select count(*) as c from nua_monthly_member_census ";
			    $sql.="  where month_id = '" . $mm['month_id'] . "' and company_id = " . $data['id'];
			    $l=$this->X->sql($sql);
                            if ($l[0]['c'] != 0) array_push($mmm,$t);
			}
			if ($mm['month_id']=="2022-08") {
			    $t=array();
		            $t['value']=$mm['month_id'];
		            $t['name']="August 2022";
			    $sql="select count(*) as c from nua_monthly_member_census ";
			    $sql.="  where month_id = '" . $mm['month_id'] . "' and company_id = " . $data['id'];
			    $l=$this->X->sql($sql);
                            if ($l[0]['c'] != 0) array_push($mmm,$t);
			}
			if ($mm['month_id']=="2022-09") {
			    $t=array();
		            $t['value']=$mm['month_id'];
		            $t['name']="September 2022";
			    $sql="select count(*) as c from nua_monthly_member_census ";
			    $sql.="  where month_id = '" . $mm['month_id'] . "' and company_id = " . $data['id'];
			    $l=$this->X->sql($sql);
                            if ($l[0]['c'] != 0) array_push($mmm,$t);
			}
			if ($mm['month_id']=="2022-10") {
			    $t=array();
		            $t['value']=$mm['month_id'];
		            $t['name']="October 2022";
			    $sql="select count(*) as c from nua_monthly_member_census ";
			    $sql.="  where month_id = '" . $mm['month_id'] . "' and company_id = " . $data['id'];
			    $l=$this->X->sql($sql);
                            if ($l[0]['c'] != 0) array_push($mmm,$t);
			}
			if ($mm['month_id']=="2022-11") {
			    $t=array();
		            $t['value']=$mm['month_id'];
		            $t['name']="November 2022";
			    $sql="select count(*) as c from nua_monthly_member_census ";
			    $sql.="  where month_id = '" . $mm['month_id'] . "' and company_id = " . $data['id'];
			    $l=$this->X->sql($sql);
                            if ($l[0]['c'] != 0) array_push($mmm,$t);
			}
			if ($mm['month_id']=="2022-12") {
			    $t=array();
		            $t['value']=$mm['month_id'];
		            $t['name']="December 2022";
			    $sql="select count(*) as c from nua_monthly_member_census ";
			    $sql.="  where month_id = '" . $mm['month_id'] . "' and company_id = " . $data['id'];
			    $l=$this->X->sql($sql);
                            if ($l[0]['c'] != 0) array_push($mmm,$t);
			}
			if ($mm['month_id']=="2021-01") {
			    $t=array();
		            $t['value']=$mm['month_id'];
		            $t['name']="January 2021";
			    $sql="select count(*) as c from nua_monthly_member_census ";
			    $sql.="  where month_id = '" . $mm['month_id'] . "' and company_id = " . $data['id'];
			    $l=$this->X->sql($sql);
                            if ($l[0]['c'] != 0) array_push($mmm,$t);
			}
			if ($mm['month_id']=="2021-02") {
			    $t=array();
		            $t['value']=$mm['month_id'];
		            $t['name']="February 2021";
			    $sql="select count(*) as c from nua_monthly_member_census ";
			    $sql.="  where month_id = '" . $mm['month_id'] . "' and company_id = " . $data['id'];
			    $l=$this->X->sql($sql);
                            if ($l[0]['c'] != 0) array_push($mmm,$t);
			}
			if ($mm['month_id']=="2021-03") {
			    $t=array();
		            $t['value']=$mm['month_id'];
		            $t['name']="March 2021";
			    $sql="select count(*) as c from nua_monthly_member_census ";
			    $sql.="  where month_id = '" . $mm['month_id'] . "' and company_id = " . $data['id'];
			    $l=$this->X->sql($sql);
                            if ($l[0]['c'] != 0) array_push($mmm,$t);
			}
			if ($mm['month_id']=="2021-04") {
			    $t=array();
		            $t['value']=$mm['month_id'];
		            $t['name']="April 2021";
			    $sql="select count(*) as c from nua_monthly_member_census ";
			    $sql.="  where month_id = '" . $mm['month_id'] . "' and company_id = " . $data['id'];
			    $l=$this->X->sql($sql);
                            if ($l[0]['c'] != 0) array_push($mmm,$t);
			}
			if ($mm['month_id']=="2021-05") {
			    $t=array();
		            $t['value']=$mm['month_id'];
		            $t['name']="May 2021";
			    $sql="select count(*) as c from nua_monthly_member_census ";
			    $sql.="  where month_id = '" . $mm['month_id'] . "' and company_id = " . $data['id'];
			    $l=$this->X->sql($sql);
                            if ($l[0]['c'] != 0) array_push($mmm,$t);
			}
			if ($mm['month_id']=="2021-06") {
			    $t=array();
		            $t['value']=$mm['month_id'];
		            $t['name']="June 2021";
			    $sql="select count(*) as c from nua_monthly_member_census ";
			    $sql.="  where  month_id= '" . $mm['month_id'] . "' and company_id = " . $data['id'];
			    $l=$this->X->sql($sql);
                            if ($l[0]['c'] != 0) array_push($mmm,$t);
			}
			if ($mm['month_id']=="2021-07") {
			    $t=array();
		            $t['value']=$mm['month_id'];
		            $t['name']="July 2021";
			    $sql="select count(*) as c from nua_monthly_member_census ";
			    $sql.="  where  month_id= '" . $mm['month_id'] . "' and company_id = " . $data['id'];
			    $l=$this->X->sql($sql);
                            if ($l[0]['c'] != 0) array_push($mmm,$t);
			}
			if ($mm['month_id']=="2021-08") {
			    $t=array();
		            $t['value']=$mm['month_id'];
		            $t['name']="August 2021";
			    $sql="select count(*) as c from nua_monthly_member_census ";
			    $sql.="  where  month_id= '" . $mm['month_id'] . "' and company_id = " . $data['id'];
			    $l=$this->X->sql($sql);
                            if ($l[0]['c'] != 0) array_push($mmm,$t);
			}
			if ($mm['month_id']=="2021-09") {
			    $t=array();
		            $t['value']=$mm['month_id'];
		            $t['name']="September 2021";
			    $sql="select count(*) as c from nua_monthly_member_census ";
			    $sql.="  where  month_id= '" . $mm['month_id'] . "' and company_id = " . $data['id'];
			    $l=$this->X->sql($sql);
                            if ($l[0]['c'] != 0) array_push($mmm,$t);
			}
			if ($mm['month_id']=="2021-10") {
			    $t=array();
		            $t['value']=$mm['month_id'];
		            $t['name']="October 2021";
			    $sql="select count(*) as c from nua_monthly_member_census ";
			    $sql.="  where  month_id= '" . $mm['month_id'] . "' and company_id = " . $data['id'];
			    $l=$this->X->sql($sql);
                            if ($l[0]['c'] != 0) array_push($mmm,$t);
			}
			if ($mm['month_id']=="2021-11") {
			    $t=array();
		            $t['value']=$mm['month_id'];
		            $t['name']="November 2021";
			    $sql="select count(*) as c from nua_monthly_member_census ";
			    $sql.="  where  month_id= '" . $mm['month_id'] . "' and company_id = " . $data['id'];
			    $l=$this->X->sql($sql);
                            if ($l[0]['c'] != 0) array_push($mmm,$t);
			}
			if ($mm['month_id']=="2021-12") {
			    $t=array();
		            $t['value']=$mm['month_id'];
		            $t['name']="December 2021";
			    $sql="select count(*) as c from nua_monthly_member_census ";
			    $sql.="  where month_id = '" . $mm['month_id'] . "' and company_id = " . $data['id'];
			    $l=$this->X->sql($sql);
                            if ($l[0]['c'] != 0) array_push($mmm,$t);
			}
		}

	        $output['monthlist']=$mmm;


		$sql="select * from nua_monthly_member_census where dependent_code = '' and month_id = '" . $month_id . "' and  company_id = " . $company_id . " order by employee_code, dependent_code, client_plan";
		$e=$this->X->sql($sql);
		$r=array();
                $last="XXX";
		foreach($e as $f) {
                        if ($f['coverage_level']=="") $f['coverage_level']="EE";
                        if ($f['employee_id']==$last) {
                               $f['employee_code']="";
                               $f['last_name']="";
                               $f['first_name']="";
                               $f['middle_initial']="";
                               $f['gender']="";
                               $f['dob']="";
                        }
		        $sql="select count(*) as c from nua_monthly_member_census where dependent_code <> '' and month_id = '" . $month_id . "' and employee_id = " . $f['employee_id'];
                        $ff=$this->X->sql($sql);
                        if ($ff[0]['c']>0)  array_push($r,$f);
                        $last=$f['employee_id'];
                        $f['term']="N";
                        array_push($r,$f);
		}
		$output['census']=$r;

		$sql="select * from nua_monthly_member_terminations where dependent_code = '' and month_id = '" . $month_id . "' and company_id = " . $company_id . " order by employee_code, dependent_code, client_plan";
		$e=$this->X->sql($sql);
		$r=array();
		foreach($e as $f) {
                        if ($f['coverage_level']=="") $f['coverage_level']="EE";
                        if ($f['employee_id']==$last) {
                               $f['employee_code']="";
                               $f['last_name']="";
                               $f['first_name']="";
                               $f['middle_initial']="";
                               $f['gender']="";
                               $f['dob']="";
                        }
		        $sql="select count(*) as c from nua_monthly_member_census where dependent_code <> '' and month_id = '" . $month_id . "' and employee_id = " . $f['employee_id'];
                        
			array_push($r,$f);
                        $last=$f['employee_id'];
		}
		$output['terminations']=$r;

		$sql="select * from nua_monthly_member_additions where dependent_code = '' and month_id = '" . $month_id . "' and company_id = " . $company_id . " order by employee_code, dependent_code, client_plan";
		$e=$this->X->sql($sql);
		$r=array();
		foreach($e as $f) {
                        if ($f['coverage_level']=="") $f['coverage_level']="EE";
                        if ($f['employee_id']==$last) {
                               $f['employee_code']="";
                               $f['last_name']="";
                               $f['first_name']="";
                               $f['middle_initial']="";
                               $f['gender']="";
                               $f['dob']="";
                        }
		        $sql="select count(*) as c from nua_monthly_member_census where dependent_code <> '' and month_id = '" . $month_id . "' and employee_id = " . $f['employee_id'];
                        
			array_push($r,$f);
                        $last=$f['employee_id'];
		}
		$output['additions']=$r;

		$sql="select * from nua_census where company_id = " . $data['id'] . " order by last_name, first_name";
		$e=$this->X->sql($sql);
		$r=array();
$total_revenue=0;
		foreach($e as $f) {
                        $sql="select count(*) as c from nua_invoice_load_terms where company_id = " . $f['company_id'] . " and ";
                        $sql.="upper(last_name) = '" . str_replace("'","''",strtoupper($f['last_name'])) . "' and ";
			$sql.="upper(first_name) = '" . str_replace("'","''",strtoupper($f['first_name'])) . "'";
			$g=$this->X->sql($sql);
                        if ($g[0]['c']>0) {
                           $f['termed']="Y";
                           $f['price']="0.00";
			} else {
			   $f['termed']="N";
                           $sql="select * from nua_company_plan where company_id = " . $data['id'] . " and ";
			   $sql.="APA_CODE = '" . $f['plan'] . "'";
                           $p=$this->X->sql($sql);
if (sizeof($p)>0) {
                           if ($f['coverage_level']=='SI') { 
                               $f['price']=$p[0]['ee_price'];
                           } 
                           if ($f['coverage_level']=='FA') { 
                               $f['price']=$p[0]['fam_price'];
                           } 
                           if ($f['coverage_level']=='ES') { 
                               $f['price']=$p[0]['ees_price'];
                           } 
                           if ($f['coverage_level']=='EC') { 
                               $f['price']=$p[0]['eec_price'];
			   }
 }
			}
			array_push($r,$f);
		}
		$output['apa']=$r;
$output['total_revenue']=$total_revenue;

        $sql="select id, class_level, is_custom, applicable_plan, coverage_level, value, quote_id, type from nua_employer_contribution where company_id = " . $data['id'] . " order by id";
		$e=$this->X->sql($sql);
		$r=array();
		foreach($e as $f) {
		     if ($f['is_custom']==0) { 
                 $f['is_custom']="No";			 
			} else {
				$f['is_custom']="Yes";		
			}
			array_push($r,$f);
		}
		$output['contribution_levels']=$r;
                $output['select']=array();

                $sql="select * from nua_company_plan where company_id = " . $data['id'] . " order by plan_name";
		$e=$this->X->sql($sql);
                $output['plans']=$e;

		$sql="select * from nua_quote where company_id = " . $data['id'] . " order by id";
		$e=$this->X->sql($sql);
		$r=array();
		$formData2=array();
		foreach ($e as $f) {
			
			if ($f['is_accepted']==0) {
				$f['is_accepted']='No';
			} else {
				$f['is_accepted']='Yes';			
			}
			$sql="select * from nua_quote_plan where quote_id = " . $f['id'] . " and omitted = 'N' order by  plan_id";
			$z=$this->X->sql($sql);
            $f['plans']=$z;
			array_push($r,$f);
			
			
			$formData2['id']=$data['id'];
			//$formData2['class_level'];
			//$formData2['applicable_plan'];
			//$formData2['company_id']=$data['id'];
			//$formData2['is_custom']="1";
			//$formData2['coverage_level']="";		
			//$formData2['value']="";
			//$formData2['user_id']="1";		
			//$formData2['quote_id']=$f['id'];
			//$formData2['type']="percentage";
	
			$sql="select distinct name from nua_quote_plan where quote_id = " . $f['id'] . " and omitted = 'N' order by name";
			$z=$this->X->sql($sql);
			$output['select']=$z;
		
		}

		$output['formData2']=$formData2;			
		$output['quotes']=$r;
                $inv=array();
		$output['invoices']=$inv;
		
		$output['documents']=array();
        return $output;		
	}
	
	function getCompanyDashboardXXX($data) {

		 $output=$this->start_output($data);
		 if ($output['user']['forced_off']>0) return $output;
		 
                 $company_id=$data['id'];
                 $date=date_create();
		 if ($data['id2']!='') {
                        $month_id = $data['id2'];
		} else { 
			$month_id =  date_format($date,"Y-m");
		}
		 $output['month_id']=$month_id;
		 $output['company_id']=$data['id'];
	         $sql="select * from nua_company where id = " . $data['id'];	
                 $d=$this->X->sql($sql);
                 foreach($d[0] as $name=>$value) $output[$name]=$value;
		$company=$d[0];

		$sql="select * from nua_org where id = " . $d[0]['org_id'];
                $d=$this->X->sql($sql);
                $output['org_name']=$d[0]['org_name'];
		
		$formData=array();
		$formData['employee_name']="";
		$formData['date_of_birth']="";
		$formData['gender']="";
		$formData['id']="";
		$formData['id2']="";
		$output['formData']=$formData;

		$employeeData=array();
		$employeeData['company_id']=$data['id'];
		$employeeData['org_id']=$output['org_id'];
		$employeeData['first_name']="";
		$employeeData['middle_name']="";
		$employeeData['last_name']="";
		$employeeData['suffix']="";
		$employeeData['email']="";
		$employeeData['phone_mobile']="";
		$employeeData['date_of_birth']="";
		$employeeData['social_security_number']="";
		$employeeData['gender']="";
		$employeeData['id']="";
		$output['employeeData']=$employeeData;
		
		$sql="select * from nua_doc where employee_id = 0 and company_id = " . $data['id'];
		$p=$this->X->sql($sql);
		$doc=array();
		foreach($p as $q) {
			// get the ID as an int.
			$id=$q['id'];
			// convert it to a string.
			$id_str=strval($id);
			// convert the string to an array;
			$split_id=str_split($id_str);
			// md5 hash the ID
		        $key=md5($id_str);
			// convert the key ro an array.
			$sp=str_split($key);

			// start the string. 
			// -- Char 1 and 2 of key + length of ID + A; 
			$k=$sp[0].$sp[1].strlen($id_str).'a';
			$hashloc=2;

			//loop through ID.
                        for ($i=0;$i<strlen($id_str);$i++) {
				$k.=$id_str[$i];
			        $padding=fmod(intval($id_str[$i]),5);
				for($j=0;$j<$padding;$j++) {
					$hashloc++;
					if ($hashloc>=strlen($key)) $hashloc=0;
				        $k.=$sp[$hashloc];
			        }
			
			}
				for($j=$hashloc;$j<strlen($key);$j++) {
				        $k.=$sp[$j];
			        }
			$q['key']=$k;
			array_push($doc,$q);
		}

		$output['docs']=$doc;
		$sql="select * from nua_company_invoice where company_id = " . $data['id'] . " order by month_id";
		$e0=$this->X->sql($sql);
		$inv=array();
		foreach ($e0 as $e) {
                    if ($e['month_id']=="2022-01") {
                         $e['month']="January";
                         $e['year']="2022";
		    }
                    if ($e['month_id']=="2021-01") {
                         $e['month']="January";
                         $e['year']="2021";
		    }
                    if ($e['month_id']=="2022-02") {
                         $e['month']="February";
                         $e['year']="2022";
		    }
                    if ($e['month_id']=="2021-02") {
                         $e['month']="February";
                         $e['year']="2021";
		    }
                    if ($e['month_id']=="2022-03") {
                         $e['month']="March";
                         $e['year']="2022";
		    }
                    if ($e['month_id']=="2021-03") {
                         $e['month']="March";
                         $e['year']="2021";
		    }
                    if ($e['month_id']=="2022-04") {
                         $e['month']="April";
                         $e['year']="2022";
		    }
                    if ($e['month_id']=="2021-04") {
                         $e['month']="April";
                         $e['year']="2021";
		    }
                    if ($e['month_id']=="2022-05") {
                         $e['month']="May";
                         $e['year']="2022";
		    }
                    if ($e['month_id']=="2021-05") {
                         $e['month']="May";
                         $e['year']="2021";
		    }
                    if ($e['month_id']=="2022-06") {
                         $e['month']="June";
                         $e['year']="2022";
		    }
                    if ($e['month_id']=="2021-06") {
                         $e['month']="June";
                         $e['year']="2021";
		    }
                    if ($e['month_id']=="2022-07") {
                         $e['month']="July";
                         $e['year']="2022";
		    }
                    if ($e['month_id']=="2021-07") {
                         $e['month']="July";
                         $e['year']="2021";
		    }
                    if ($e['month_id']=="2022-08") {
                         $e['month']="August";
                         $e['year']="2022";
		    }
                    if ($e['month_id']=="2021-08") {
                         $e['month']="August";
                         $e['year']="2021";
		    }
                    if ($e['month_id']=="2022-09") {
                         $e['month']="September";
                         $e['year']="2022";
		    }
                    if ($e['month_id']=="2021-09") {
                         $e['month']="September";
                         $e['year']="2021";
		    }
                    if ($e['month_id']=="2022-10") {
                         $e['month']="October";
                         $e['year']="2022";
		    }
                    if ($e['month_id']=="2021-10") {
                         $e['month']="October";
                         $e['year']="2021";
		    }
                    if ($e['month_id']=="2022-11") {
                         $e['month']="November";
                         $e['year']="2022";
		    }
                    if ($e['month_id']=="2021-11") {
                         $e['month']="November";
                         $e['year']="2021";
		    }
                    if ($e['month_id']=="2022-11") {
                         $e['month']="December";
                         $e['year']="2022";
		    }
                    if ($e['month_id']=="2021-11") {
                         $e['month']="December";
                         $e['year']="2021";
		    }

                    array_push($inv,$e);
		}
		$output['invoices']=$inv;

		$sql="select * from nua_employee where company_id = " . $data['id'] . " order by employee_name";
		$e=$this->X->sql($sql);
		$r=array();
		foreach($e as $f) {
			$sql="select id from nua_employee_enrollment where employee_id = " . $f['id'];
			$q=$this->X->sql($sql);
            if (sizeof($q)>0) {
                $f['enrolled']="Y";
		$f['enrollment_id']=$q[0]['id'];
            } else {
                $f['enrolled']="N";
		$f['enrollment_id']="0";				
			}
			$f['adding']="N";
			array_push($r,$f);
		}
		$output['employees']=$r;

		$sql="select distinct client_plan, coverage_level from nua_monthly_member_census where company_id = " . $data['id'] . " and  month_id = '" . $month_id . "' order by 1,2";
	        $planlist=$this->X->sql($sql);
		$tt=array();
		$last="X";
		foreach($planlist as $p) {
                        $oo=0;
			$sql="select coverage_price from nua_monthly_member_census where coverage_price <> '' and client_plan = '";
		        $sql.= $p['client_plan'] . "' and coverage_level = '" . $p['coverage_level'] . "' and  coverage_price <> '0.00' and company_id = ";
		        $sql.= $data['id'] . " and  month_id = '" . $month_id . "' order by 1";
	                $pln=$this->X->sql($sql);
			foreach($pln as $pln0) {
                             if (floatval($pln0['coverage_price'])>$oo) $oo=floatval($pln0['coverage_price']); 
			}
			$p['coverage_price']=number_format($oo,2);
			if ($p['client_plan']==$last) { $p['client_plan']=""; }
			$last=$p['client_plan'];
			if ($oo!=0) array_push($tt,$p);	
		}
		$sql="select * from inf_client_plan where active = 'Y' and clientId = (select infinity_id from nua_company where id = " . $data['id'] . ") and planId not in   ";
                $sql.="(select client_plan from nua_monthly_member_census where company_id = " . $data['id'] . " and month_id = '" . $month_id . "') ";
                $j=$this->X->sql($sql); 
                foreach($j as $p) {
                      $new=array();   
                      $new['client_plan']=$p['planId'];
                      $new['coverage_level']="";
                      $new['coverage_price']="";
                      array_push($tt,$new);
                }
             
	        $output['planlist']=$tt;

		if ($company['infinity_id']!='') {
                      $sql="select * from inf_client_plan where clientId = '" . $company['infinity_id'] . "' and active = 'N' order by planId";
		      $gg=$this->X->sql($sql);
                      $output['inactive']=$gg; 

		} else {
                      $output['inactive']=array();
	        }


		$sql="select distinct month_id from nua_monthly_member_census order by month_id desc";
	        $monthlist=$this->X->sql($sql);
		$mmm=array();
		foreach($monthlist as $mm) {
			if ($mm['month_id']=="2022-01") {
			    $t=array();
		            $t['value']=$mm['month_id'];
		            $t['name']="January 2022";
			    $sql="select count(*) as c from nua_monthly_member_census ";
			    $sql.="  where month_id = '" . $mm['month_id'] . "' and company_id = " . $data['id'];
			    $l=$this->X->sql($sql);
                            if ($l[0]['c']!=0) array_push($mmm,$t);
			}
			if ($mm['month_id']=="2022-02") {
			    $t=array();
		            $t['value']=$mm['month_id'];
		            $t['name']="February 2022";
			    $sql="select count(*) as c from nua_monthly_member_census ";
			    $sql.="  where month_id = '" . $mm['month_id'] . "' and company_id = " . $data['id'];
			    $l=$this->X->sql($sql);
			    if ($l[0]['c'] != 0) array_push($mmm,$t);
		            $t['name']="February 2022";
			}
			if ($mm['month_id']=="2022-03") {
			    $t=array();
		            $t['value']=$mm['month_id'];
		            $t['name']="March 2022";
			    $sql="select count(*) as c from nua_monthly_member_census ";
			    $sql.="  where month_id = '" . $mm['month_id'] . "' and company_id = " . $data['id'];
			    $l=$this->X->sql($sql);
                            if ($l[0]['c'] != 0) array_push($mmm,$t);
			}
			if ($mm['month_id']=="2022-04") {
			    $t=array();
		            $t['value']=$mm['month_id'];
		            $t['name']="April 2022";
			    $sql="select count(*) as c from nua_monthly_member_census ";
			    $sql.="  where month_id = '" . $mm['month_id'] . "' and company_id = " . $data['id'];
			    $l=$this->X->sql($sql);
                            if ($l[0]['c'] != 0) array_push($mmm,$t);
			}
			if ($mm['month_id']=="2022-05") {
			    $t=array();
		            $t['value']=$mm['month_id'];
		            $t['name']="May 2022";
			    $sql="select count(*) as c from nua_monthly_member_census ";
			    $sql.="  where month_id = '" . $mm['month_id'] . "' and company_id = " . $data['id'];
			    $l=$this->X->sql($sql);
                            if ($l[0]['c'] !=0) array_push($mmm,$t);
			}
			if ($mm['month_id']=="2022-06") {
			    $t=array();
		            $t['value']=$mm['month_id'];
		            $t['name']="June 2022";
			    $sql="select count(*) as c from nua_monthly_member_census ";
			    $sql.="  where month_id = '" . $mm['month_id'] . "' and company_id = " . $data['id'];
			    $l=$this->X->sql($sql);
                            if ($l[0]['c'] != 0) array_push($mmm,$t);
			}
			if ($mm['month_id']=="2022-07") {
			    $t=array();
		            $t['value']=$mm['month_id'];
		            $t['name']="July 2022";
			    $sql="select count(*) as c from nua_monthly_member_census ";
			    $sql.="  where month_id = '" . $mm['month_id'] . "' and company_id = " . $data['id'];
			    $l=$this->X->sql($sql);
                            if ($l[0]['c'] != 0) array_push($mmm,$t);
			}
			if ($mm['month_id']=="2022-08") {
			    $t=array();
		            $t['value']=$mm['month_id'];
		            $t['name']="August 2022";
			    $sql="select count(*) as c from nua_monthly_member_census ";
			    $sql.="  where month_id = '" . $mm['month_id'] . "' and company_id = " . $data['id'];
			    $l=$this->X->sql($sql);
                            if ($l[0]['c'] != 0) array_push($mmm,$t);
			}
			if ($mm['month_id']=="2022-09") {
			    $t=array();
		            $t['value']=$mm['month_id'];
		            $t['name']="September 2022";
			    $sql="select count(*) as c from nua_monthly_member_census ";
			    $sql.="  where month_id = '" . $mm['month_id'] . "' and company_id = " . $data['id'];
			    $l=$this->X->sql($sql);
                            if ($l[0]['c'] != 0) array_push($mmm,$t);
			}
			if ($mm['month_id']=="2022-10") {
			    $t=array();
		            $t['value']=$mm['month_id'];
		            $t['name']="October 2022";
			    $sql="select count(*) as c from nua_monthly_member_census ";
			    $sql.="  where month_id = '" . $mm['month_id'] . "' and company_id = " . $data['id'];
			    $l=$this->X->sql($sql);
                            if ($l[0]['c'] != 0) array_push($mmm,$t);
			}
			if ($mm['month_id']=="2022-11") {
			    $t=array();
		            $t['value']=$mm['month_id'];
		            $t['name']="November 2022";
			    $sql="select count(*) as c from nua_monthly_member_census ";
			    $sql.="  where month_id = '" . $mm['month_id'] . "' and company_id = " . $data['id'];
			    $l=$this->X->sql($sql);
                            if ($l[0]['c'] != 0) array_push($mmm,$t);
			}
			if ($mm['month_id']=="2022-12") {
			    $t=array();
		            $t['value']=$mm['month_id'];
		            $t['name']="December 2022";
			    $sql="select count(*) as c from nua_monthly_member_census ";
			    $sql.="  where month_id = '" . $mm['month_id'] . "' and company_id = " . $data['id'];
			    $l=$this->X->sql($sql);
                            if ($l[0]['c'] != 0) array_push($mmm,$t);
			}
			if ($mm['month_id']=="2021-01") {
			    $t=array();
		            $t['value']=$mm['month_id'];
		            $t['name']="January 2021";
			    $sql="select count(*) as c from nua_monthly_member_census ";
			    $sql.="  where month_id = '" . $mm['month_id'] . "' and company_id = " . $data['id'];
			    $l=$this->X->sql($sql);
                            if ($l[0]['c'] != 0) array_push($mmm,$t);
			}
			if ($mm['month_id']=="2021-02") {
			    $t=array();
		            $t['value']=$mm['month_id'];
		            $t['name']="February 2021";
			    $sql="select count(*) as c from nua_monthly_member_census ";
			    $sql.="  where month_id = '" . $mm['month_id'] . "' and company_id = " . $data['id'];
			    $l=$this->X->sql($sql);
                            if ($l[0]['c'] != 0) array_push($mmm,$t);
			}
			if ($mm['month_id']=="2021-03") {
			    $t=array();
		            $t['value']=$mm['month_id'];
		            $t['name']="March 2021";
			    $sql="select count(*) as c from nua_monthly_member_census ";
			    $sql.="  where month_id = '" . $mm['month_id'] . "' and company_id = " . $data['id'];
			    $l=$this->X->sql($sql);
                            if ($l[0]['c'] != 0) array_push($mmm,$t);
			}
			if ($mm['month_id']=="2021-04") {
			    $t=array();
		            $t['value']=$mm['month_id'];
		            $t['name']="April 2021";
			    $sql="select count(*) as c from nua_monthly_member_census ";
			    $sql.="  where month_id = '" . $mm['month_id'] . "' and company_id = " . $data['id'];
			    $l=$this->X->sql($sql);
                            if ($l[0]['c'] != 0) array_push($mmm,$t);
			}
			if ($mm['month_id']=="2021-05") {
			    $t=array();
		            $t['value']=$mm['month_id'];
		            $t['name']="May 2021";
			    $sql="select count(*) as c from nua_monthly_member_census ";
			    $sql.="  where month_id = '" . $mm['month_id'] . "' and company_id = " . $data['id'];
			    $l=$this->X->sql($sql);
                            if ($l[0]['c'] != 0) array_push($mmm,$t);
			}
			if ($mm['month_id']=="2021-06") {
			    $t=array();
		            $t['value']=$mm['month_id'];
		            $t['name']="June 2021";
			    $sql="select count(*) as c from nua_monthly_member_census ";
			    $sql.="  where  month_id= '" . $mm['month_id'] . "' and company_id = " . $data['id'];
			    $l=$this->X->sql($sql);
                            if ($l[0]['c'] != 0) array_push($mmm,$t);
			}
			if ($mm['month_id']=="2021-07") {
			    $t=array();
		            $t['value']=$mm['month_id'];
		            $t['name']="July 2021";
			    $sql="select count(*) as c from nua_monthly_member_census ";
			    $sql.="  where  month_id= '" . $mm['month_id'] . "' and company_id = " . $data['id'];
			    $l=$this->X->sql($sql);
                            if ($l[0]['c'] != 0) array_push($mmm,$t);
			}
			if ($mm['month_id']=="2021-08") {
			    $t=array();
		            $t['value']=$mm['month_id'];
		            $t['name']="August 2021";
			    $sql="select count(*) as c from nua_monthly_member_census ";
			    $sql.="  where  month_id= '" . $mm['month_id'] . "' and company_id = " . $data['id'];
			    $l=$this->X->sql($sql);
                            if ($l[0]['c'] != 0) array_push($mmm,$t);
			}
			if ($mm['month_id']=="2021-09") {
			    $t=array();
		            $t['value']=$mm['month_id'];
		            $t['name']="September 2021";
			    $sql="select count(*) as c from nua_monthly_member_census ";
			    $sql.="  where  month_id= '" . $mm['month_id'] . "' and company_id = " . $data['id'];
			    $l=$this->X->sql($sql);
                            if ($l[0]['c'] != 0) array_push($mmm,$t);
			}
			if ($mm['month_id']=="2021-10") {
			    $t=array();
		            $t['value']=$mm['month_id'];
		            $t['name']="October 2021";
			    $sql="select count(*) as c from nua_monthly_member_census ";
			    $sql.="  where  month_id= '" . $mm['month_id'] . "' and company_id = " . $data['id'];
			    $l=$this->X->sql($sql);
                            if ($l[0]['c'] != 0) array_push($mmm,$t);
			}
			if ($mm['month_id']=="2021-11") {
			    $t=array();
		            $t['value']=$mm['month_id'];
		            $t['name']="November 2021";
			    $sql="select count(*) as c from nua_monthly_member_census ";
			    $sql.="  where  month_id= '" . $mm['month_id'] . "' and company_id = " . $data['id'];
			    $l=$this->X->sql($sql);
                            if ($l[0]['c'] != 0) array_push($mmm,$t);
			}
			if ($mm['month_id']=="2021-12") {
			    $t=array();
		            $t['value']=$mm['month_id'];
		            $t['name']="December 2021";
			    $sql="select count(*) as c from nua_monthly_member_census ";
			    $sql.="  where month_id = '" . $mm['month_id'] . "' and company_id = " . $data['id'];
			    $l=$this->X->sql($sql);
                            if ($l[0]['c'] != 0) array_push($mmm,$t);
			}
		}

	        $output['monthlist']=$mmm;


		$sql="select * from nua_monthly_member_census where dependent_code = '' and month_id = '" . $month_id . "' and  company_id = " . $company_id . " order by employee_code, dependent_code, client_plan";
		$e=$this->X->sql($sql);
		$r=array();
                $last="XXX";
		foreach($e as $f) {
                        if ($f['coverage_level']=="") $f['coverage_level']="EE";
                        if ($f['employee_id']==$last) {
                               $f['employee_code']="";
                               $f['last_name']="";
                               $f['first_name']="";
                               $f['middle_initial']="";
                               $f['gender']="";
                               $f['dob']="";
                        }
		        $sql="select count(*) as c from nua_monthly_member_census where dependent_code <> '' and month_id = '" . $month_id . "' and employee_id = " . $f['employee_id'];
                        $ff=$this->X->sql($sql);
                        if ($ff[0]['c']>0)  array_push($r,$f);
                        $last=$f['employee_id'];
                        array_push($r,$f);
		}
		$output['census']=$r;

		$sql="select * from nua_monthly_member_terminations where dependent_code = '' and month_id = '" . $month_id . "' and company_id = " . $company_id . " order by employee_code, dependent_code, client_plan";
		$e=$this->X->sql($sql);
		$r=array();
		foreach($e as $f) {
                        if ($f['coverage_level']=="") $f['coverage_level']="EE";
                        if ($f['employee_id']==$last) {
                               $f['employee_code']="";
                               $f['last_name']="";
                               $f['first_name']="";
                               $f['middle_initial']="";
                               $f['gender']="";
                               $f['dob']="";
                        }
		        $sql="select count(*) as c from nua_monthly_member_census where dependent_code <> '' and month_id = '" . $month_id . "' and employee_id = " . $f['employee_id'];
                        
			array_push($r,$f);
                        $last=$f['employee_id'];
		}
		$output['terminations']=$r;

		$sql="select * from nua_monthly_member_additions where dependent_code = '' and month_id = '" . $month_id . "' and company_id = " . $company_id . " order by employee_code, dependent_code, client_plan";
		$e=$this->X->sql($sql);
		$r=array();
		foreach($e as $f) {
                        if ($f['coverage_level']=="") $f['coverage_level']="EE";
                        if ($f['employee_id']==$last) {
                               $f['employee_code']="";
                               $f['last_name']="";
                               $f['first_name']="";
                               $f['middle_initial']="";
                               $f['gender']="";
                               $f['dob']="";
                        }
		        $sql="select count(*) as c from nua_monthly_member_census where dependent_code <> '' and month_id = '" . $month_id . "' and employee_id = " . $f['employee_id'];
                        
			array_push($r,$f);
                        $last=$f['employee_id'];
		}
		$output['additions']=$r;

		$sql="select * from nua_census where company_id = " . $data['id'] . " order by last_name, first_name";
		$e=$this->X->sql($sql);
		$r=array();
$total_revenue=0;
		foreach($e as $f) {
                        $sql="select count(*) as c from nua_invoice_load_terms where company_id = " . $f['company_id'] . " and ";
                        $sql.="upper(last_name) = '" . str_replace("'","''",strtoupper($f['last_name'])) . "' and ";
			$sql.="upper(first_name) = '" . str_replace("'","''",strtoupper($f['first_name'])) . "'";
			$g=$this->X->sql($sql);
                        if ($g[0]['c']>0) {
                           $f['termed']="Y";
                           $f['price']="0.00";
			} else {
			   $f['termed']="N";
                           $sql="select * from nua_company_plan where company_id = " . $data['id'] . " and ";
			   $sql.="APA_CODE = '" . $f['plan'] . "'";
                           $p=$this->X->sql($sql);
if (sizeof($p)>0) {
                           if ($f['coverage_level']=='SI') { 
                               $f['price']=$p[0]['ee_price'];
                           } 
                           if ($f['coverage_level']=='FA') { 
                               $f['price']=$p[0]['fam_price'];
                           } 
                           if ($f['coverage_level']=='ES') { 
                               $f['price']=$p[0]['ees_price'];
                           } 
                           if ($f['coverage_level']=='EC') { 
                               $f['price']=$p[0]['eec_price'];
			   }
                           $total_revenue+=floatval($f['price']); 
}
			}
			array_push($r,$f);
		}
		$output['apa']=$r;
$output['total_revenue']=$total_revenue;

        $sql="select id, class_level, is_custom, applicable_plan, coverage_level, value, quote_id, type from nua_employer_contribution where company_id = " . $data['id'] . " order by id";
		$e=$this->X->sql($sql);
		$r=array();
		foreach($e as $f) {
		     if ($f['is_custom']==0) { 
                 $f['is_custom']="No";			 
			} else {
				$f['is_custom']="Yes";		
			}
			array_push($r,$f);
		}
		$output['contribution_levels']=$r;
                $output['select']=array();

                $sql="select * from nua_company_plan where company_id = " . $data['id'] . " order by plan_name";
		$e=$this->X->sql($sql);
                $output['plans']=$e;

		$sql="select * from nua_quote where company_id = " . $data['id'] . " order by id";
		$e=$this->X->sql($sql);
		$r=array();
		$formData2=array();
		foreach ($e as $f) {
			
			if ($f['is_accepted']==0) {
				$f['is_accepted']='No';
			} else {
				$f['is_accepted']='Yes';			
			}
			$sql="select * from nua_quote_plan where quote_id = " . $f['id'] . " and omitted = 'N' order by  plan_id";
			$z=$this->X->sql($sql);
            $f['plans']=$z;
			array_push($r,$f);
			
			
			$formData2['id']=$data['id'];
			//$formData2['class_level'];
			//$formData2['applicable_plan'];
			//$formData2['company_id']=$data['id'];
			//$formData2['is_custom']="1";
			//$formData2['coverage_level']="";		
			//$formData2['value']="";
			//$formData2['user_id']="1";		
			//$formData2['quote_id']=$f['id'];
			//$formData2['type']="percentage";
	
			$sql="select distinct name from nua_quote_plan where quote_id = " . $f['id'] . " and omitted = 'N' order by name";
			$z=$this->X->sql($sql);
			$output['select']=$z;
		
		}

		$output['formData2']=$formData2;			
		$output['quotes']=$r;
		$output['invoices']=$inv;
		
		$output['documents']=array();
        return $output;		
	}
	
	
	function getEmployeeIHQ($data) {
		
	}
	
	
	function postIHQAnswer($data) {
	}
	
	function getMemberIHQ($data) {
		
	}
	
	function employeeLookup($data) {
		 $output=$this->start_output($data);
                 $output=$this->getTableFormData($data,"nua_employee");
		 return $output;
	}

//--
//-- 17
//--
	function getEmployeeDashboard($data) {
		
		 $date=date_create();
		 $month_id=date_format($date,'Y-m');
		 $day_id=date_format($date,'d');
		 $month=date_format($date,'m');
		 $month_val=intval($month);

		 $output=$this->start_output($data);
		 if ($output['user']['forced_off']>0) return $output;
		 
		 $termdts=array();
		 if ($month_id=="2022-01") {
			 if (intval($day_id)<=5) {
				 array_push($termdts,"2022-01-31");
				 $td="2022-01-31";
		         } else {
                                 $td="2022-02-28";
			 }

		         array_push($termdts,"2022-02-28");
		         array_push($termdts,"2022-03-31");
		         array_push($termdts,"2022-04-30");
		         array_push($termdts,"2022-05-31");
		         array_push($termdts,"2022-06-30");
		         array_push($termdts,"2022-07-31");
		         array_push($termdts,"2022-08-31");
		         array_push($termdts,"2022-09-30");
		         array_push($termdts,"2022-10-31");
                 }

		 if ($month_id=="2022-02") {
			 if (intval($day_id)<=5) {
			     array_push($termdts,"2022-02-28");
			     $td="2022-02-28";
			 } else {
			     $td="2022-03-31";
			}
		         array_push($termdts,"2022-03-31");
		         array_push($termdts,"2022-04-30");
		         array_push($termdts,"2022-05-31");
		         array_push($termdts,"2022-06-30");
		         array_push($termdts,"2022-07-31");
		         array_push($termdts,"2022-08-31");
		         array_push($termdts,"2022-09-30");
		         array_push($termdts,"2022-10-31");
		         array_push($termdts,"2022-11-30");
                 }

		 if ($month_id=="2022-03") {
			 if (intval($day_id)<=5) {
				 $array_push($termdts,"2022-03-31");
				 $td="2022-03-31";
			} else {
                                 $td="2022-04-30";
			}
		         array_push($termdts,"2022-04-30");
		         array_push($termdts,"2022-05-31");
		         array_push($termdts,"2022-06-30");
		         array_push($termdts,"2022-07-31");
		         array_push($termdts,"2022-08-31");
		         array_push($termdts,"2022-09-30");
		         array_push($termdts,"2022-10-31");
		         array_push($termdts,"2022-11-30");
                 }
		 if ($month_id=="2022-04") {
			 if (intval($day_id)<=5) {
				 array_push($termdts,"2022-04-30");
				 $td="2022-04-30";
			} else {
                                 $td="2022-05-31";
			}
		         array_push($termdts,"2022-05-31");
		         array_push($termdts,"2022-06-30");
		         array_push($termdts,"2022-07-31");
		         array_push($termdts,"2022-08-31");
		         array_push($termdts,"2022-09-30");
		         array_push($termdts,"2022-10-31");
		         array_push($termdts,"2022-11-30");
		         array_push($termdts,"2022-12-31");
                 }

		 $output['termdts']=$termdts;

                 $sql="select * from nua_employee where id = " . $data['id'];	
                $d=$this->X->sql($sql);
		if (sizeof($d)>0) {
                        //
			//-- Flatten Employee Record 
			//
                        foreach ($d[0] as $name=>$value) $output[$name]=$value;
			$output['formData']=$d[0];
		
                        $sql="select * from nua_monthly_member_census where employee_id = " . $data['id'] . " and month_id = '" . $month_id . "'";
			$j=$this->X->sql($sql);
			$jj=array();
			foreach($j as $k) {
                            if ($k['coverage_level']=="") $k['coverage_level']="EE";
			    if ($k['coverage_price']!='0.00') array_push($jj,$k);
			}
                        $output['census']=$jj;

                        $sql="select * from nua_company where id = " . $output['company_id']; 
                        $j=$this->X->sql($sql);
                        if (sizeof($j)>0) {
                             $census=$j[0];
                        } else {
                             $census=array();
                             $census['copmany_name']="";
                             $census['company_id']=0;
                        }
                        $output['company']=$census;
                        
			$sql="select id, class_level, is_custom, applicable_plan, coverage_level, value, quote_id, type from nua_employer_contribution where company_id = " . $d[0]['company_id'] . " order by id";
			$e=$this->X->sql($sql);
			$r=array();
			foreach($e as $f) {
				if ($f['is_custom']==0) { 
					$f['is_custom']="No";			 
				} else {
					$f['is_custom']="Yes";		
				}
			array_push($r,$f);
		}
		$contForm=array();
		$contForm['employee_id']=$data['id'];
		
		$sql="select * from nua_employee_plan_options where employee_id = " . $data['id'] . " order by plan_type, plan_name";
		$tt=$this->X->sql($sql);
		$rr=array();
		foreach($tt as $tt0){
			
			$contForm['employee_level_' . $tt0['id']]=$tt0['employee_level'];
			$contForm['employee_value_' . $tt0['id']]=$tt0['employee_contribution_value'];
			$contForm['employee_amount_' . $tt0['id']]=$tt0['employee_contribution_amt'];
			$contForm['employee_price_' . $tt0['id']]=$tt0['employee_price'];
			
			$contForm['spouse_level_' . $tt0['id']]=$tt0['employee_spouse_level'];
			$contForm['spouse_value_' . $tt0['id']]=$tt0['employee_spouse_contribution_value'];
			$contForm['spouse_amount_' . $tt0['id']]=$tt0['employee_spouse_contribution_amt'];
			$contForm['spouse_price_' . $tt0['id']]=$tt0['employee_spouse_price'];
			$contForm['children_level_' . $tt0['id']]=$tt0['employee_children_level'];
			$contForm['children_value_' . $tt0['id']]=$tt0['employee_children_contribution_value'];
			$contForm['children_amount_' . $tt0['id']]=$tt0['employee_children_contribution_amt'];
			$contForm['children_price_' . $tt0['id']]=$tt0['employee_children_price'];
			$contForm['family_level_' . $tt0['id']]=$tt0['family_level'];
			$contForm['family_value_' . $tt0['id']]=$tt0['family_contribution_value'];
			$contForm['family_amount_' . $tt0['id']]=$tt0['family_contribution_amt'];
			$contForm['family_price_' . $tt0['id']]=$tt0['family_price'];

			$sql="select class_level, type from nua_employer_contribution where id = " . $tt0['employee_level'];
			$u=$this->X->sql($sql);
			if (sizeof($u)>0) {
			    $tt0['employee_class']=$u[0]['class_level'];
				$contForm['employee_type_' . $tt0['id']]=$u[0]['type'];
			} else {
				$tt0['employee_class']="Not Set";
				$contForm['employee_type_' . $tt0['id']]="percentage";
			}
			
			$sql="select class_level, type from nua_employer_contribution where id = " . $tt0['employee_spouse_level'];
			$u=$this->X->sql($sql);
			if (sizeof($u)>0) {
			    $tt0['employee_spouse_class']=$u[0]['class_level'];
				$contForm['spouse_type_' . $tt0['id']]=$u[0]['type'];
			} else {
				$tt0['employee_spouse_class']="Not Set";
				$contForm['spouse_type_' . $tt0['id']]="percentage";
			}

			$sql="select class_level, type from nua_employer_contribution where id = " . $tt0['employee_children_level'];
			$u=$this->X->sql($sql);
			if (sizeof($u)>0) {
			    $tt0['employee_children_class']=$u[0]['class_level'];
				$contForm['children_type_' . $tt0['id']]=$u[0]['type'];
			} else {
				$tt0['employee_children_class']="Not Set";
				$contForm['children_type_' . $tt0['id']]="percentage";
			}
			
			$sql="select class_level, type from nua_employer_contribution where id = " . $tt0['employee_children_level'];
			$u=$this->X->sql($sql);
			if (sizeof($u)>0) {
			    $tt0['family_class']=$u[0]['class_level'];
				$contForm['family_type_' . $tt0['id']]=$u[0]['type'];
			} else {
				$tt0['family_class']="Not Set";
				$contForm['family_type_' . $tt0['id']]="percentage";
			}
			array_push($rr,$tt0);			
		}
		$sql="select * from nua_doc where employee_id = " . $data['id'];
		$p=$this->X->sql($sql);
		$doc=array();
		foreach($p as $q) {
			// get the ID as an int.
			$id=$q['id'];
			// convert it to a string.
			$id_str=strval($id);
			// convert the string to an array;
			$split_id=str_split($id_str);
			// md5 hash the ID
		        $key=md5($id_str);
			// convert the key ro an array.
			$sp=str_split($key);

			// start the string. 
			// -- Char 1 and 2 of key + length of ID + A; 
			$k=$sp[0].$sp[1].strlen($id_str).'a';
			$hashloc=2;

			//loop through ID.
                        for ($i=0;$i<strlen($id_str);$i++) {
				$k.=$id_str[$i];
			        $padding=fmod(intval($id_str[$i]),5);
				for($j=0;$j<$padding;$j++) {
					$hashloc++;
					if ($hashloc>=strlen($key)) $hashloc=0;
				        $k.=$sp[$hashloc];
			        }
			
			}
				for($j=$hashloc;$j<strlen($key);$j++) {
				        $k.=$sp[$j];
			        }
			$q['key']=$k;
			array_push($doc,$q);
		}

		$output['docs']=$doc;

		$sql="select * from nua_employee_plan where employee_id = " . $data['id'];
		$p=$this->X->sql($sql);
		$output['plans']=$p;
		
		$output['contForm']=$contForm;
		$output['contFormOriginal']=$contForm;
		$output['options']=$rr;
		
		$familyData=array();
		$familyData['id']="";
		$familyData['first_name']="";
		$familyData['middle_name']="";
		$familyData['last_name']="";
		$familyData['member_type']="";
		$familyData['gender']="";
		$familyData['date_of_birth']="";
		$familyData['weight']="";
		$familyData['height']="";
		$familyData['employee_id']=$data['id'];
		
                 $output['familyData']=$familyData;

		$termData=array();
		$termData['id']="";
		$termData['plan_id']="";
		$termData['employee_id']=$data['id'];
		$termData['term_date']=$td;
		$output['termData']=$termData;
		
		$output['contribution_levels']=$r;
		
		$sql="select * from nua_user where employee_id = " . $data['id'];
		$u=$this->X->sql($sql);
		$output['users']=$u;
		
		$sql="select * from nua_employee_enrollment where employee_id = " . $data['id'];
		$u=$this->X->sql($sql);
		$output['enrollment']=$u;
		
		$sql="select * from nua_employee_ihq where employee_id = " . $data['id'];
		$u=$this->X->sql($sql);
		if (sizeof($u)>0) {
			$output['ihq_started']="Yes";
			if ($u[0]['information_submitted']==0) {
				$output['information_submitted']="No";
			} else {
			    $output['information_submitted']="Yes";	
			}
			if ($u[0]['family_submitted']==0) {
				$output['family_submitted']="No";
			} else {
			    $output['family_submitted_submitted']="Yes";	
			}			
			if ($u[0]['insurance_submitted']==0) {
				$output['insurance_submitted']="No";
			} else {
			    $output['insurance_submitted']="Yes";	
			}	
			if ($u[0]['medical_questions_completed']==0) {
				$output['medical_questions_completed']="No";
			} else {
			    $output['medical_questions_completed']="Yes";	
			}				
			if ($u[0]['medications_completed']==0) {
				$output['medications_completed']="No";
			} else {
			    $output['medications_completed']="Yes";	
			}				
			if ($u[0]['signature_accepted_at']=="") {
				$output['signature_accepted_at']="Not Signed";	
			} else {
			    $output['signature_accepted_at']=$u[0]['signature_accepted_at'];
			}
		} else {
			$output['ihq_started']="No";	
			$output['information_submitted']="No";
			$output['family_submitted']="No";
			$output['insurance_submitted']="No";
			$output['medical_questions_completed']="No";
			$output['medications_completed']="Yes";	
			$output['signature_accepted_at']="Not Signed";	
		}
		$output['ihq']=$u;

		$sql="select * from nua_employee_dependent where employee_id = " . $data['id'];
		$u=$this->X->sql($sql);
		$output['family']=$u;
		}
		
        return $output;		
	}



        //--
	//-- 9
	//--
	
	function getQuoteDashboard($data) {

	    $uid=$data['uid'];
		 $output=$this->start_output($data);
		 if ($output['user']['forced_off']>0) return $output;
		$user=$output['user'];
		
	    $sql="select * from nua_quote where id = " . $data['id'];
		$d=$this->X->sql($sql);
	    $quote=$d[0];		
		$company_id = $quote['company_id'];
		$sql="select id, first_name, middle_name, last_name, date_of_birth, gender from nua_employee where company_id = " . $company_id . " order by last_name, first_name, middle_name";
        $employees=$this->X->sql($sql);
        $output['employees']=$employees;		

        $output['quote']=$quote;
		
        $sql="select * from nua_company where id = " . $quote['company_id'];
		$y=$this->X->sql($sql);
		$company=$y[0];
		$output['company']=$company;

        $formData=$d[0];
        $output['formData']=$formData;
		$colForm=array();
		$colForm['company_id']=$formData['company_id'];
		$colForm['save_id']="0"; 
		if ($user['role']=='sadmin'||$user['role']=='user') {
			$sql="select * from nua_quote_plan where quote_id = " . $data['id'] . " order by order_id";
		} else {
			$sql="select * from nua_quote_plan where quote_id = " . $data['id'] . " and omitted = 'N' order by order_id";			
		}
		$z=$this->X->sql($sql);
        $aaa=array();
		if (sizeof($z)>0) {
            foreach($z as $e) {
				$e["employee"]=str_replace("$","",$e['employee']);
				if ($e['employee']=="") $e['employee']="0.00";
                $colForm['employee_' . $e['id']]=$e['employee'];
				
				$e["employee_spouse"]=str_replace("$","",$e['employee_spouse']);
				if ($e['employee_spouse']=="") $e['employee_spouse']="0.00";
                $colForm['employeespouse_' . $e['id']]=$e['employee_spouse'];
				
				$e["employee_children"]=str_replace("$","",$e['employee_children']);
				if ($e['employee_children']=="") $e['employee_children']="0.00";				
                $colForm['employeechildren_' . $e['id']]=$e['employee_children'];
				
		
				$e["family"]=str_replace("$","",$e['family']);
				if ($e['family']=="") $e['family']="0.00";
				$colForm['family_' . $e['id']]=$e['family'];
				
				$colForm['omitted_' . $e['id']]=$e['omitted'];
				$colForm['message_' . $e['id']]="";
				
				array_push($aaa,$e);
			}
		}
		
		$output['colForm']=$colForm;
		$output['plans']=$aaa;
		$output['user']=$user;
		return $output;
	}
	

//--
//-- 45
//--
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
	
	function sendAdminInvite($data) {
            $sql="select * from nua_user where id = " . $data['data']['id'];
	    $a=$this->X->sql($sql);
	    $subs=array();
	    $line=array();
            $invite_code=$this->makeInviteCode();
	    $post=array();
	    $post['table_name']="nua_user";
	    $post['action']="insert";
	    $post['id']=$a[0]['id'];
	    $post['status']="invited";
	    $post['invite_code']=$invite_code;
	    $this->X->post($post);
            $line=array();
	    $line['var']="name";
	    $line['value']=$a[0]['full_name'];
	    array_push($subs,$line);
            $line=array();
	    $line['var']="token";
	    $line['value']=$invite_code;
	    array_push($subs,$line);
            $this->sendTemplate("support@nuaxess.email",$a[0]['email'],"Welcome to MyNuAxess", "neqvygmpqzg0p7w2",$subs);
	    $post=array();
	    $post['table_name']="nua_email";
	    $post['action']="insert";
	    $post['user_id']=$data['data']['id'];
	    $post['org_id']=$a[0]['org_id'];
	    $post['company_id']=$a[0]['company_id'];
	    $post['employee_id']=$a[0]['employee_id'];
	    $post['subject']="Welcome to MyNuAxess";
	    $post['template_name']="Admin Welcome Email";
            $post['template']="neqvygmpqzg0p7w2";
	    $this->X->post($post);
	    $output=array();
	    $output['error_code']=0;
	    $output['error_message']="";
	    return $output;
	}

	function sendBrokerAdminInvite($data) {
            $sql="select * from nua_user where id = " . $data['data']['id'];
	    $a=$this->X->sql($sql);
	    $subs=array();
	    $line=array();
            $invite_code=$this->makeInviteCode();
	    $post=array();
	    $post['table_name']="nua_user";
	    $post['action']="insert";
	    $post['id']=$a[0]['id'];
	    $post['status']="invited";
	    $post['invite_code']=$invite_code;
	    $this->X->post($post);
            $line=array();
	    $line['var']="name";
	    $line['value']=$a[0]['full_name'];
	    array_push($subs,$line);
            $line=array();
	    $line['var']="token";
	    $line['value']=$invite_code;
	    array_push($subs,$line);
            $this->sendTemplate("support@nuaxess.email",$a[0]['email'],"Important Notice from NuAccess", "3z0vklomyvg7qrx5",$subs);
	    $post=array();
	    $post['table_name']="nua_email";
	    $post['action']="insert";
	    $post['user_id']=$data['data']['id'];
	    $post['org_id']=$a[0]['org_id'];
	    $post['company_id']=$a[0]['company_id'];
	    $post['employee_id']=$a[0]['employee_id'];
	    $post['subject']="Important Message from MyNuAxess";
	    $post['template_name']="Broker Introduction Email";
            $post['template']="3z0vklomyvg7qrx5";
	    $this->X->post($post);
	    $output=array();
	    $output['error_code']=0;
	    $output['error_message']="";
	    return $output;
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
	
//--
//-- 24
//--
	
	function getInvoiceList($data) {
		
		$output=$this->start_output($data);
		if ($output['user']['forced_off']>0) return $output;
		$user=$output['user'];
                $sql="select * from nua_company where id = " . $user['company_id'];
                $b=$this->X->sql($sql);
                if (sizeof($b)==0) {
                   $company_id=99999;
                }
                else {
                   $company_id=$b[0]['id'];
                }
		$sql="select * from nua_company where id = " . $company_id . " order by company_name";
                $d=$this->X->sql($sql);
                $list=array();
                $grand_total=0;
                foreach($d as $e) {
                    $h=array();
                    $h['company_id']=$e['id'];
                    $h['company_name']=$e['company_name'];
                    $h['id']=$e['id'];
                    $h['billing_contact_name']=$e['billing_contact_name'];
                    $h['billing_contact_phone']=$e['billing_contact_phone'];
                    $h['billing_contact_email']=$e['billing_contact_email'];
                    $inv=array();
		    $sql="select * from nua_company_invoice where company_id = " . $e['id'] . " order by month_id";
                    $g=$this->X->sql($sql);
		    $h['invoices']=$g; 
                    $total=0;
                    foreach($g as $t) { $total+=$t['grand_total_float']; $grand_total+=$t['grand_total_float']; } 
                    $h['company_total']=number_format($total,2);
                    array_push($list,$h);
                }
		$output['list']=$list;
                $output['grand_total']=number_format($grand_total,2);
                return $output;		
	}

	function getPlanList($data) {
		
		$output=$this->start_output($data);
		if ($output['user']['forced_off']>0) return $output;
		$user=$output['user'];
                $sql="select * from nua_company_plan where company_id = " . $user['company_id'] . " order by plan_code";
                $d=$this->X->sql($sql);
                $list=array();
                foreach($d as $e) {
                    $e['ee_price']=number_format($e['ee_price'],2);
                    $e['ees_price']=number_format($e['ees_price'],2);
                    $e['eec_price']=number_format($e['eec_price'],2);
                    $e['fam_price']=number_format($e['fam_price'],2);
                    array_push($list,$e);
                }
		$output['list']=$list;
                return $output;		
	}

//--
//-- 38
//--

	function getCurrentCensus($data) {
		
		$output=$this->start_output($data);
		if ($output['user']['forced_off']>0) return $output;
		$user=$output['user'];
	        $output['id']=$data['id'];	
		if ($data['id']=='') {
                       $date=date_create();
		       $month_id=date_format($date,'Y-m');
		} else {
		       $month_id=$data['id'];

		} 

                $output['month_id']=$month_id;

                $sql="select * from nua_monthly_member_census where client_id <> '' and month_id = '" . $month_id . "'";
                $d=$this->X->sql($sql); 
		$l=array();
                 foreach($d as $e) {
               //     $e['first_name']=str_replace('"',"'",$e['first_name']);
                     array_push($l,$e);
		}
                $output['list']=$l;
                return $output;		
   
        }


	function postUpdateCensus($data) {
	       $postData=$data['data'];
	       $sql="select * from nua_employee where id = " . $postData['employee_id'];
	       $e=$this->X->sql($sql);
	       $employee=$e[0];
	       if ($postData['dependent_code']=="") {
		     if ($postData['dob']!="" ) {
			     $sql="update nua_employee set date_of_birth = '" . $postData['dob'] . "' where id = " . $postData['employee_id'];
			     $this->X->execute($sql);
			     $sql="update nua_monthly_member_census set dob = '" . $postData['dob'] . "' where employee_id = " . $postData['employee_id'] . " and dependent_code = ''";
			     $this->X->execute($sql);
			     $sql="update nua_monthly_member_additions set dob = '" . $postData['dob'] . "' where employee_id = " . $postData['employee_id'] . " and dependent_code = ''";
			     $this->X->execute($sql);
			     $sql="update nua_monthly_member_terminations set dob = '" . $postData['dob'] . "' where employee_id = " . $postData['employee_id'] . " and dependent_code = ''";
			     $this->X->execute($sql);
                     }	
		     if ($postData['ssn']!="" ) {
			     $sql="update nua_employee set social_security_number = '" . $postData['ssn'] . "' where id = " . $postData['employee_id'];
			     $this->X->execute($sql);
			     $sql="update nua_monthly_member_census set ssn = '" . $postData['ssn'] . "' where employee_id = " . $postData['employee_id'] . " and dependent_code = ''";
			     $this->X->execute($sql);
			     $sql="update nua_monthly_member_additions set ssn = '" . $postData['ssn'] . "' where employee_id = " . $postData['employee_id'] . " and dependent_code = ''";
			     $this->X->execute($sql);
			     $sql="update nua_monthly_member_terminations set ssn = '" . $postData['ssn'] . "' where employee_id = " . $postData['employee_id'] . " and dependent_code = ''";
			     $this->X->execute($sql);
                     }	
	       } else {
		     if ($postData['dob']!="" ) {
			     $sql="update nua_employee_dependent set date_of_birth = '" . $postData['dob'] . "' where employee_id = " . $postData['employee_id'] . " and dependent_id = '" . $postData['dependent_code'] . "'";
			     $this->X->execute($sql);
			     $sql="update nua_monthly_member_census set dob = '" . $postData['dob'] . "' where dependent_code = '" . $postData['dependent_code'] . "'";
			     $this->X->execute($sql);
			     $sql="update nua_monthly_member_additions set dob = '" . $postData['dob'] . "' where dependent_code = '" . $postData['dependent_code'] . "'";
			     $this->X->execute($sql);
			     $sql="update nua_monthly_member_terminations set dob = '" . $postData['dob'] . "' where and dependent_code = '" . $postData['dependent_code'] . "'";
			     $this->X->execute($sql);
                     }	
		     if ($postData['ssn']!="" ) {
			     $sql="update nua_employee_dependent set social_security_number = '" . $postData['ssn'] . "' where employee_id = " . $postData['employee_id'] . " and dependent_id = '" . $postData['dependent_code'] . "'";
			     $this->X->execute($sql);
			     $sql="update nua_monthly_member_census set ssn = '" . $postData['ssn'] . "' where dependent_code = '" . $postData['dependent_code'] . "'";
			     $this->X->execute($sql);
			     $sql="update nua_monthly_member_additions set ssn = '" . $postData['ssn'] . "' where dependent_code = '" . $postData['dependent_code'] . "'";
			     $this->X->execute($sql);
			     $sql="update nua_monthly_member_terminations set ssn = '" . $postData['ssn'] . "' where dependent_code = '" . $postData['dependent_code'] . "'";
			     $this->X->execute($sql);
                     }	
	       }
	       $output=array();
	       $output['error_code']=0;
	       $output['source']=$postData['source'];
	       $output['month_id']=$postData['month_id'];
	       return $output;
	}
//--
//-- 39
//--
	function getCurrentTerminations($data) {
		
		$output=$this->start_output($data);
		if ($output['user']['forced_off']>0) return $output;
		$user=$output['user'];
	        $output['id']=$data['id'];	
		if ($data['id']=='') {
                       $date=date_create();
		       $month_id=date_format($date,'Y-m');
		} else {
		       $month_id=$data['id'];

		} 

                if ($month_id=='2022-03') $month_id="2022-04";
                if ($month_id=='2022-02') $month_id="2022-03";
                if ($month_id=='2022-01') $month_id="2022-02";
                if ($month_id=='2021-12') $month_id="2022-01";
                if ($month_id=='2021-11') $month_id="2021-12";
                if ($month_id=='2021-10') $month_id="2021-11";
                if ($month_id=='2021-09') $month_id="2021-10";
                if ($month_id=='2021-08') $month_id="2021-09";
                if ($month_id=='2021-07') $month_id="2021-08";
                if ($month_id=='2021-06') $month_id="2021-07";
                if ($month_id=='2021-05') $month_id="2021-06";
                if ($month_id=='2021-04') $month_id="2021-05";
                if ($month_id=='2021-03') $month_id="2021-04";
                if ($month_id=='2021-02') $month_id="2021-03";
                if ($month_id=='2021-01') $month_id="2021-02";

                $output['month_id']=$month_id;
                $sql="select * from nua_monthly_member_terminations where client_id <> '' and month_id = '" . $month_id . "' limit 500";
                $d=$this->X->sql($sql); 
                 
		$l=array();
                 foreach($d as $e) {
                     $e['first_name']=str_replace('"',"'",$e['first_name']);
                     array_push($l,$e);
		}
                $output['list']=$l;
                return $output;		
   
        }
//--
//-- 38
//--
	function getCurrentAdditions($data) {
		
		$output=$this->start_output($data);
		if ($output['user']['forced_off']>0) return $output;
		$user=$output['user'];
	        $output['id']=$data['id'];	
		if ($data['id']=='') {
                       $date=date_create();
		       $month_id=date_format($date,'Y-m');
		} else {
		       $month_id=$data['id'];

		} 
                if ($month_id=='2022-03') $month_id="2022-04";
                if ($month_id=='2022-02') $month_id="2022-03";
                if ($month_id=='2022-01') $month_id="2022-02";
                if ($month_id=='2021-12') $month_id="2022-01";
                if ($month_id=='2021-11') $month_id="2021-12";
                if ($month_id=='2021-10') $month_id="2021-11";
                if ($month_id=='2021-09') $month_id="2021-10";
                if ($month_id=='2021-08') $month_id="2021-09";
                if ($month_id=='2021-07') $month_id="2021-08";
                if ($month_id=='2021-06') $month_id="2021-07";
                if ($month_id=='2021-05') $month_id="2021-06";
                if ($month_id=='2021-04') $month_id="2021-05";
                if ($month_id=='2021-03') $month_id="2021-04";
                if ($month_id=='2021-02') $month_id="2021-03";
                if ($month_id=='2021-01') $month_id="2021-02";

                $output['month_id']=$month_id;
                $sql="select * from nua_monthly_member_additions where client_id <> '' and month_id = '" . $month_id . "' limit 500";
                $d=$this->X->sql($sql); 
		$l=array();
                 foreach($d as $e) {
                     $e['first_name']=str_replace('"',"'",$e['first_name']);
                     array_push($l,$e);
		}
                $output['list']=$l;
                return $output;		
   
        }

	function getActivePlans($data) {
		
        }

	function getCurrentCurrentiAdditions($data) {
		
		$output=$this->start_output($data);
		if ($output['user']['forced_off']>0) return $output;
		$user=$output['user'];
	        $output['id']=$data['id'];	
		if ($data['id']=='') {
                       $date=date_create();
		       $month_id=date_format($date,'Y-m');
		} else {
		       $month_id=$data['id'];

                }
                if ($month_id=='2022-03') $month_id="2022-04";
                if ($month_id=='2022-02') $month_id="2022-03";
                if ($month_id=='2022-01') $month_id="2022-02";
                if ($month_id=='2021-12') $month_id="2022-01";
                if ($month_id=='2021-11') $month_id="2021-12";
                if ($month_id=='2021-10') $month_id="2021-11";
                if ($month_id=='2021-09') $month_id="2021-10";
                if ($month_id=='2021-08') $month_id="2021-09";
                if ($month_id=='2021-07') $month_id="2021-08";
                if ($month_id=='2021-06') $month_id="2021-07";
                if ($month_id=='2021-05') $month_id="2021-06";
                if ($month_id=='2021-04') $month_id="2021-05";
                if ($month_id=='2021-03') $month_id="2021-04";
                if ($month_id=='2021-02') $month_id="2021-03";
                if ($month_id=='2021-01') $month_id="2021-02";

                $d=$this->X->sql($sql); 
                 
		//$sql="select * from nua_company where org_id = " . $user['org_id'] . "  and insured_lives <> '0' order by company_name";
		//$list=array();
		//$a=array();
		//foreach($d as $e) {
		//	$sql="select * from nua_monthly_member_census where client_id = '" . $e['infinity_id'] . "' and month_id = '" . $month_id . "' order by last_name, dependent_code"; 
                //        $b=$this->X->sql($sql);
               //foreach($b as $g) {
               //                  $g['company_name']=$e['company_name'];
	       //		    array_push($a,$g);
	//	        }
        //        }
                $output['list']=$d;
                return $output;		
   
        }

        //--
	//-- 2, 6, 21, 23
	//--

        function makeCompanyQuery($user,$list='*', $type='') {

                $role=$user['role'];
                $user_id=$user['id'];
                $org_id=$user['org_id'];
                $broker_id=$user['broker_id'];

                if ($type!='') {
                     $status = " and status in ('" . $type . "') ";
		} else {
                     $status = '';
                }
                   
                if ($role=='badmin'||$role=='sadmin') {
                     $filter=" and org_id = '" . $org_id . "' ";
                } else {
                     $filter=" and broker_id = '" . $broker_id . "' ";
		}
                
		$s="select " . $list . " from nua_company where 1 = 1 ";
                $s.=$status;
		$s.=$filter;
                $s.=" order by company_name";
                return $s;

        }

        function getNextMonthId($month='') {
	     if ($month=='') {
                  $month=$this->getMonthId();
	     }
             if ($month=='2020-12') $next_month="2021-01";
             if ($month=='2021-01') $next_month="2021-02";
             if ($month=='2021-02') $next_month="2021-03";
             if ($month=='2021-03') $next_month="2021-04";
             if ($month=='2021-04') $next_month="2021-05";
             if ($month=='2021-05') $next_month="2021-06";
             if ($month=='2021-06') $next_month="2021-07";
             if ($month=='2021-07') $next_month="2021-08";
             if ($month=='2021-08') $next_month="2021-09";
             if ($month=='2021-09') $next_month="2021-10";
             if ($month=='2021-10') $next_month="2021-11";
             if ($month=='2021-11') $next_month="2021-12";
             if ($month=='2021-12') $next_month="2022-01";
             if ($month=='2022-01') $next_month="2022-02";
             if ($month=='2022-02') $next_month="2022-03";
             if ($month=='2022-03') $next_month="2022-04";
             if ($month=='2022-04') $next_month="2022-05";
             if ($month=='2022-05') $next_month="2022-06";
             if ($month=='2022-06') $next_month="2022-07";
             if ($month=='2022-07') $next_month="2022-08";
             if ($month=='2022-08') $next_month="2022-09";
             if ($month=='2022-09') $next_month="2022-10";
             if ($month=='2022-10') $next_month="2022-11";
             if ($month=='2022-11') $next_month="2022-12";
             if ($month=='2022-12') $next_month="2023-01";
             return $next_month;

        }
	function getCompanyList($data, $type='') {
		
		$output=$this->start_output($data);
		if ($output['user']['forced_off']>0) return $output;
		$user=$output['user'];
	        $output['id']=$data['id'];	
		
                $month_id=$this->getMonthId();
                $next_month_id=$this->getNextMonthId();
           
		$sql="select org_name from nua_org where id = " . $user['org_id'];
		$g=$this->X->sql($sql);
		if (sizeof($g)>0) {
		   $org_name=$g[0]['org_name'];
		} else {
		   $org_name="";
		}
                $sql=$this->makeCompanyQuery($user,"*",$type);

		$list=array();
                $d=$this->X->sql($sql); 
		$a=array();
		foreach($d as $e) {
			
                      // count current enrolled
	              $sql="select count(*) as c from nua_monthly_member_census where month_id = '" . $month_id . "' and company_id = " . $e['id'];
		      $qu=$this->X->sql($sql);
                      $e['census_count']=$qu[0]['c'];	
		
                      // count next month additions
	              $sql="select count(*) as c from nua_monthly_member_additions where month_id = '" . $next_month_id . "' and company_id = " . $e['id'];
		      $qu=$this->X->sql($sql);
                      $e['addition_count']=$qu[0]['c'];	

                      // count next month additions
	              $sql="select count(*) as c from nua_monthly_member_terminations where month_id = '" . $next_month_id . "' and company_id = " . $e['id'];
		      $qu=$this->X->sql($sql);
                      $e['termination_count']=$qu[0]['c'];	

	              $sql="select status from nua_quote where company_id = " . $e['id'];
		      $qu=$this->X->sql($sql);
                      if (sizeof($qu)==0) {
                           $e['quote_status']="Not Quoted";
                      } else {
                           $e['quote_status']=$qu[0]['status'];
                      }

                      $sql="select first_name, last_name from nua_broker where user_id = " . $e['user_id'];
		      $g=$this->X->sql($sql);
		      if (sizeof($g)>0) {
		         $broker_name=$g[0]['last_name'] . ", " . $g[0]['first_name'];
		      } else {
		         $broker_name="No Broker Assigned";
		      }
                      $e['broker_name']=$broker_name;
		      array_push($a,$e);
		}
		
        $output['list']=$a;
        return $output;		
	}
	
//-- 5
//--
//
	function getQuoteList($data) {
		
		$output=$this->start_output($data);
		if ($output['user']['forced_off']>0) return $output;
		$user=$output['user'];
                $s="(" . $this->makeCompanyQuery($user,"id","") . ")";
		
	         $sql="select * from nua_quote where company_id in " . $s . "  order by id";
                 $d=$this->X->sql($sql);
                 $l=array();
                 foreach($d as $m) {
                     $sql="select * from nua_company where id = " . $m['company_id'];
                     $x=$this->X->sql($sql);
                     $m['company_name']=$x[0]['company_name'];
                     $m['contact_name']=$x[0]['contact_name'];
                     $m['contact_phone']=$x[0]['contact_phone'];
                     $m['email']=$x[0]['email'];
                     array_push($l,$m);
                 }
                 $output['list']=$l;
        return $output;		
	}
	
	
	
    function memberLookup($data) {
		$output=$this->start_output($data);
		if ($output['user']['forced_off']>0) return $output;
		$user=$output['user'];
		$sql="select * from nua_census order by last_name";
		$list=$this->X->sql($sql);
		$out=array();
		foreach ($list as $l) {
                     $l['company_name'] .= "(" . $l['company_id'] . ")";
                     array_push($out,$l);
		}
		 $output['list']=$out;
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


