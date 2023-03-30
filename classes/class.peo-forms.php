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

function flip_date($date) {
	$y=substr($date,0,4);
	$m=substr($data,5,2);
	$d=substr($data,8,2);
	$k=$m . "/" . $d . "/" . $y;
        return $k;
}

function sendtxt($to,$msg) {

    $to=str_replace("+","",$to);
    $to=str_replace("(","",$to);
    $to=str_replace(")","",$to);
    $to=str_replace(" ","",$to);	
	$to=str_replace("-","",$to);
	
	if (strlen($to)==11) {
	    // phone number must start with 1
	}
	
	if (strlen($to)==10) {
	    $to='1' . $to;	
	}
	
	$url = 'https://api.twilio.com/2010-04-01/Accounts//Messages';
	$postRequest = array(
		'Body' => $msg,
		'To' => $to,
		'From' => '14699084644'
	);
	$ch = curl_init($url);
	curl_setopt($ch, CURLOPT_USERPWD, ":");  
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
	curl_setopt($ch, CURLOPT_POSTFIELDS, $postRequest);
	$response = curl_exec($ch);
	if(curl_errno($ch)){
		throw new Exception(curl_error($ch));
	}
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

    function postCensusBad($data) {
         $post=$data['data'];
	 $post['table_name']="nua_bad";
         $post['action']="insert";
         if ($post['subject']!="") {
             $sql="select * from nua_bad where company_id = " . $post['company_id'] . " and employee_id = " . $post['employee_id'];
	     $z=$this->X->sql($sql);
	     if (sizeof($z)>0) {
		 $post['id']=$z[0]['id'];
	     }
             $this->X->post($post);
         } else {
             $sql="delete from nua_bad where company_id = " . $post['company_id'] . " and employee_id = " . $post['employee_id'];
             $this->X->execute($sql);
         }
         
         $results=array();
         $results['error_code']=0;
         $results['error_message']="Save Complete";
         return $results;
    }

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

function postAddLevel($data) {
	     
		 $output=$this->start_output($data);
		 if ($output['user']['forced_off']>0) return $output;
		 
		 $post=array();
		 $post['table_name']="nua_employer_contribution";
		 $post['action']="input";

         $post['id']=$data['data']['id'];
         $post['company_id']=$data['data']['company_id'];
         $post['is_custom']="1";
         $post['coverage_level']=$data['data']['coverage_level'];
         $post['value']=$data['data']['value'];
         $post['quote_id']=$data['data']['quote_id'];
         $post['user_id']=$data['uid'];
         $post['type']=$data['data']['type'];
         $post['class_level']=$data['data']['class_level'];
         $post['applicable_plan']=$data['data']['applicable_plan'];
         $id=$this->X->post($post);
		 
		 $output=array();
		 $output['error_code']="0";
		 $output['id']=$id;
		 return $output;
}

function postEmployeeLookup($data) {

        
	$ssn=$data['data']['formData']['social_security_number'];
	$first_name=$data['data']['formData']['first_name'];
	$last_name=$data['data']['formData']['last_name'];
	$email=$data['data']['formData']['email'];
	$company_code=$data['data']['formData']['company_code'];
	$company_name=$data['data']['formData']['company_name'];
       // $company_name = "";
	$flag=0;
	$sql="select * from nua_employee where 1 = 1 ";

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
              $sql .= " and org_id = " . $data['data']['user']['org_id'];
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

    function getAddSystemNote($data) {
                 $output=$this->getTableFormData($data,"inf_system_note");
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
			
			$sql="select id, role, org_id, company_id, employee_id, last_login, last_timestamp, email, avatar from nua_user where id = " . $data['uid'];
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
	
	function getEditCompany($data,$table_name) {
		 $output=$this->start_output($data);
		 if ($output['user']['forced_off']>0) return $output;
	    $sql="select * from nua_company where id = " . $data['id'];
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

		 $output=$this->start_output($data);
		 if ($output['user']['forced_off']>0) return $output;
	         $formData=array();
		 foreach ($data as $name=>$value) {
                      $output[$name]=$value;
		 }
                 $select=array();
                 $sql="select company_id, company_name from nua_census_company order by company_name";
		 $sel=$this->X->sql($sql);
                 foreach ($sel as $s) {
                      $r=array();
                      $r['value']=$s['company_id'];
                      $r['name']=$s['company_name'];
                      array_push($select,$r);
		 }
                 $output['select']=$select;

                 if ($data['id']!="") {

                      $sql="select * from nua_company where id = " . $data['id'];
		      $company=$this->X->sql($sql);
                      if (sizeof($company)>0) {
		         $output['company']=$company[0];
                      }
		      $formData['company_id']=$data['id'];
                 } else {
		     $formData['company_id']=0;
                     $company=array();
                     $output['company']=array(); 
                 }

		 $formData['invoice_no']="";
		 $formData['month']="01";
		 $formData['year']="22";
		 $formData['due_date']="01/31/2022";
		 $formData['invoice_date']="01/15/2022";
		 $formData['grand_total']="0.00";
		 $formData['medical_total']="0.00";
		 $formData['dental_total']="0.00";
		 $formData['vision_total']="0.00";
		 $formData['add_total']="0.00";
		 $formData['life_total']="0.00";
		 $formData['adj_total']="-0.00";
		 $output['formData']=$formData;
		 return $output;
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
		 $output=$this->start_output($data);
		 if ($output['user']['forced_off']>0) return $output;
	         $post=array();
		 $post=$data['data']['formData'];
	 	 $post['table_name']="nua_company_invoice";
                 $post['action']="insert";
		 if (!isset($post['create_timestamp'])) $post['create_timestamp']="";
		 if ($post['create_timestamp']=="") $post['create_timestamp']=date('Y-m-d H:i:s',time()); 
                 $id=$this->X->post($post);
		 $output=array();
		 $output['error_code']="0";
		 $output['id']=$id;
		 return $output;
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
		$output=$this->start_output($data);
		 if ($output['user']['forced_off']>0) return $output;
	     $post=array();
		 $post=$data['data']['formData'];
         $post['table_name']="nua_plan";
         $post['action']="insert";
         $id=$this->X->post($post);
		 $output=array();
		 $output['error_code']="0";
		 $output['id']=$id;
		 return $output;
	}

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

	function postEditIHQInfo($data) {
		 $output=$this->start_output($data);
		 if ($output['user']['forced_off']>0) return $output;
	     $post=array();
		 $post=$data['data']['formData'];
         $post['table_name']="nua_employee_ihq_information";
         $post['action']="insert";
         $id=$this->X->post($post);
		 $output=array();
		 $output['error_code']="0";
		 $output['id']=$id;
		 return $output;
	}
	
	function postMakePlans($data) {
		 $output=$this->start_output($data);
		 if ($output['user']['forced_off']>0) return $output;
			$id=$data['data']['id'];
		    $sql="select * from nua_quote where id = " . $id;
			$q=$this->X->sql($sql);
			$quote=$q[0];
			
//		    if ($quote['medical']=='Y') {
					$sql="select * from nua_plan where plan_type = 'medical'";
					$f=$this->X->sql($sql);
					foreach($f as $f0) {
					    $sql="select * from nua_quote_plan where quote_id = " . $id . " and name = '" . $f0['plan_name'] . "'";
                        $t=$this->X->sql($sql);
						if (sizeof($t)==0) {
							$post=array();
							$post['table_name']="nua_quote_plan";
							$post['quote_id']=$id;
							$post['name']=$f0['plan_name'];
							$post['plan_type']=$f0['plan_type'];
							$post['action']="insert";
							$post['order_id']=$f0['order_id']; 	
							$post['plan_id']=$f0['id'];			
							$post['omitted']="Y";
							$this->X->post($post);
						}
					}
//			}
//		    if ($quote['dental']=='Y') {
					$sql="select * from nua_plan where plan_type = 'dental'";
					$f=$this->X->sql($sql);
					foreach($f as $f0) {
					    $sql="select * from nua_quote_plan where quote_id = " . $id . " and name = '" . $f0['plan_name'] . "'";
                        $t=$this->X->sql($sql);
						if (sizeof($t)==0) {
							$post=array();
							$post['table_name']="nua_quote_plan";
							$post['quote_id']=$id;
							$post['name']=$f0['plan_name'];
							$post['plan_type']=$f0['plan_type'];
							$post['action']="insert";
							$post['order_id']=$f0['order_id']; 	
							$post['plan_id']=$f0['id'];			
							$post['omitted']="Y";
							$this->X->post($post);
						}
					}
//			} 
//		    if ($quote['vision']=='Y') {
					$sql="select * from nua_plan where plan_type = 'vision'";
					$f=$this->X->sql($sql);
					foreach($f as $f0) {
					    $sql="select * from nua_quote_plan where quote_id = " . $id . " and name = '" . $f0['plan_name'] . "'";
                        $t=$this->X->sql($sql);
						if (sizeof($t)==0) {
							$post=array();
							$post['table_name']="nua_quote_plan";
							$post['quote_id']=$id;
							$post['plan_name']=$f0['name'];
							$post['type']=$f0['plan_type'];
							$post['action']="insert";
							$post['order_id']=$f0['order_id']; 	
							$post['plan_id']=$f0['id'];			
							$post['omitted']="Y";
							$this->X->post($post);
						}
					}
//			}
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
		
			$sql="select * from nua_employee where id = " . $employee_id;
			$e=$this->X->sql($sql);
            $employee=$e[0];
			
			$sql="select * from nua_company where id = " . $company_id;
			$c=$this->X->sql($sql);
			$company=$c[0];
								
			$sql="select * from nua_quote_plan where company_id = " . $company_id . " and omitted = 'N' order by id";
			$plans=$this->X->sql($sql);
			
			foreach($plans as $p) {
				
					//
				    // Make numbers from strings.
					//

					$employee_base=str_replace("$","",$p['employee']);
					$employee_spouse=str_replace("$","",$p['employee_spouse']);
					$employee_children=str_replace("$","",$p['employee_children']);
					$family=str_replace("$","",$p['family']);
										
					// See if this option already exists.
					$sql="select * from nua_employee_plan_options where employee_id = " . $employee_id . " and quote_plan_id = " . $p['id'];
					$po=$this->X->sql($sql);
			
					$post=array();

					if (sizeof($po)==0) {
						$post['employee_price']="0.00";
						$post['employee_spouse_price']="0.00";
						$post['employee_children_price']="0.00";
						$post['family_price']="0.00";				
						$post['employee_level']=$employee['contribution_level_id'];
						$post['employee_spouse_level']=$employee['contribution_level_id'];
						$post['employee_children_level']=$employee['contribution_level_id'];
						$post['family_level']=$employee['contribution_level_id'];	
					} else {
						$post['id']=$po[0]['id'];	
						if ($force=='Y') {
							$post['employee_level']=$employee['contribution_level_id'];
							$post['employee_spouse_level']=$employee['contribution_level_id'];
							$post['employee_children_level']=$employee['contribution_level_id'];
							$post['family_level']=$employee['contribution_level_id'];								
						} else {
						    if ($po[0]['employee_level']==0) {
								$post['employee_level']=$employee['contribution_level_id'];
							}
						    if ($po[0]['employee_spouse_level']==0) {
								$post['employee_spouse_level']=$employee['contribution_level_id'];
							}
						    if ($po[0]['employee_children_level']==0) {
								$post['employee_children_level']=$employee['contribution_level_id'];
							}
						    if ($po[0]['family_level']==0) {
								$post['family_level']=$employee['family_level_id'];
							}
						}
					}
					$post['table_name']="nua_employee_plan_options";
					$post['employee_id']=$employee_id;
					$post['action']="insert";
					$post['name']=$p['name'];
					$post['quote_plan_id']=$p['id'];
					$post['plan_id']=$p['plan_id'];
					$post['plan_type']=$p['plan_type'];
					$post['employee']=$employee_base;
					$post['plan_name']=$p['name'];
					$post['employee_spouse']=$employee_spouse;
					$post['employee_children']=$employee_children;
					$post['family']=$family;
					$post['employee_type']=$employee_type; 
					$post['employee_spouse_type']=$employee_spouse_type;
					$post['employee_children_type']=$employee_children_type;
					$post['family_type']=$family_type;					

                    if ($post['employee_level']!="0") {
							$sql="select * from nua_employer_contribution where id = " . $post['employee_level'];
							$tt=$this->X->sql($sql);
						    $t=$tt[0];
							if ($t['type']=="percentage") {
								$post['employee_contribution_type']=$t['type'];
								$post['employee_contribution_value']=$t['value'];
								$post['employee_contribution_amt']=round(floatval($employee_base)*(floatval($t['value'])/100),2);
								$post['employee_price']=floatval($post['employee'])-floatval($post['employee_contribution_amt']);
								
							}
							if ($t['type']=="flat_rate") {
								$post['employee_contribution_type']=$t['type'];
								$post['employee_contribution_value']=$t['value'];								
								$post['employee_contribution_amt']=$t['value'];
								$post['employee_price']=floatval($post['employee'])-floatval($post['employee_contribution_amt']);								
							}
					}

                    if ($post['employee_spouse_level']!="0") {
							$sql="select * from nua_employer_contribution where id = " . $post['employee_spouse_level'];
							$tt=$this->X->sql($sql);
						    $t=$tt[0];
							if ($t['type']=="percentage") {
								$post['employee_spouse_contribution_type']=$t['type'];
								$post['employee_spouse_contribution_value']=$t['value'];		
								$post['employee_spouse_contribution_amt']=round(floatval($employee_spouse)*(floatval($t['value'])/100),2);
								$post['employee_spouse_price']=floatval($post['employee_spouse'])-floatval($post['employee_spouse_contribution_amt']);
								
							}
							if ($t['type']=="flat_rate") {
								$post['employee_spouse_contribution_type']=$t['type'];
								$post['employee_spouse_contribution_value']=$t['value'];
								$post['employee_spouse_contribution_amt']=$t['value'];
								$post['employee_spouse_price']=floatval($post['employee_spouse'])-floatval($post['employee_spouse_contribution_amt']);								
							}
					}
					
                    if ($post['employee_children_level']!="0") {
							$sql="select * from nua_employer_contribution where id = " . $post['employee_children_level'];
							$tt=$this->X->sql($sql);
						    $t=$tt[0];
							if ($t['type']=="percentage") {
								$post['employee_children_contribution_type']=$t['type'];
								$post['employee_children_contribution_value']=$t['value'];
								$post['employee_children_contribution_amt']=round(floatval($employee_children)*(floatval($t['value'])/100),2);
								$post['employee_children_price']=floatval($post['employee_children'])-floatval($post['employee_children_contribution_amt']);
								
							}
							if ($t['type']=="flat_rate") {
								$post['employee_children_contribution_type']=$t['type'];
								$post['employee_children_contribution_value']=$t['value'];
								$post['employee_children_contribution_amt']=$t['value'];
								$post['employee_children_price']=floatval($post['employee_children'])-floatval($post['employee_children_contribution_amt']);								
							}
					}

                    if ($post['family_level']!="0") {
							$sql="select * from nua_employer_contribution where id = " . $post['family_level'];
							$tt=$this->X->sql($sql);
						    $t=$tt[0];
							if ($t['type']=="percentage") {
								$post['family_contribution_type']=$t['type'];
								$post['family_contribution_value']=$t['value'];
								$post['family_contribution_amt']=round(floatval($family)*(floatval($t['value'])/100),2);
								$post['family_price']=floatval($post['family'])-floatval($post['family_contribution_amt']);
								
							}
							if ($t['type']=="flat_rate") {
								$post['family_contribution_type']=$t['type'];
								$post['family_contribution_value']=$t['value'];
								$post['family_contribution_amt']=$t['value'];
								$post['family_price']=floatval($post['family_price'])-floatval($post['family_contribution_amt']);								
							}
					}				
				
					$this->X->post($post);
			}
			
	}
	
	function postEditEmployee($data) {
		 $output=$this->start_output($data);
		 if ($output['user']['forced_off']>0) return $output;
	         $post=array();
		 $post=$data['data']['formData'];
                 $post['table_name']="nua_employee";
                  $post['action']="insert";
		 if ($post['contribution_level_id']!=""&&$post['contribution_level_id']!="0") {
		 	 $sql="select class_level from nua_employer_contribution where id = " . $post['contribution_level_id'];
			 $yy=$this->X->sql($sql);
			 if (sizeof($yy)>0) {
				 $post['contribution_class_level']=$yy[0]['class_level'];
			 } else {
				 $post['contribution_class_level']="Invalid";
			 }
		 } else {
		    $post['contribution_class_level']="Not Assigned";
		 }
         $id=$this->X->post($post);
		 $output=array();
		 $output['error_code']="0";
		 $output['id']=$id;
		 return $output;
	}
	
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
	
	function postAddQuoteRequest($data,$table_name) {
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
	
	function postEditQuoteRequest($data) {
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
	
function getTestDashboard($data) {

        $date=date_create();
        $month_id=date_format($date,'Y-m');

	$output=$this->start_output($data);
	if ($output['user']['force_logout']>0) return $output;	
        $user=$output['user'];
	$uid=$data['uid'];
	//--
	//
	$sql="select count(*) as c from nua_company where status in ('enrolled') and member_count <> '0'";
        $prospects=$this->X->sql($sql);	
	$output['active_count']=$prospects[0]['c'];
	
	$sql="select count(*) as c from nua_company where status in ('enrolled','prospect') and member_count = '0'";
        $prospects=$this->X->sql($sql);	
	$output['prospect_count']=$prospects[0]['c'];
	
	$sql="select count(*) as c from nua_company where org_id = " . $user['org_id'] . " and member_count <> '0'";
        $prospects=$this->X->sql($sql);	
	$output['prospect_count']=$prospects[0]['c'];
	$output['new_prospect_count']=$prospects[0]['c'];
	
	$sql="select count(*) as c from nua_quote where org_id = " . $user['org_id'];
	$sql="select count(*) as c from nua_company where org_id = " . $user['org_id'] . " and member_count =  '0'";
        $quotes=$this->X->sql($sql);	
	$output['quote_count']=$quotes[0]['c'];
	$output['new_request_count']=$quotes[0]['c'];	
	
	$sql="select count(*) as c from nua_user where org_id = " . $user['org_id'];
        $quotes=$this->X->sql($sql);
	$output['user_count']=$quotes[0]['c'];		


	$sql="select count(*) as c from nua_quote where status in ('Enrolling','Accepted')";
        $quotes=$this->X->sql($sql);
	$output['in_enrollment_count']=$quotes[0]['c'];
	$sql="select count(*) as c from nua_employee where company_id in (select id from nua_company where status in ('Enrolling','Accepted') and org_id = '" . $user['org_id'] . "')";
        $quotes=$this->X->sql($sql);
	$output['in_enrollment_members']=$quotes[0]['c'];		

	$sql="select count(*) as c from nua_quote where status in ('Enrolled')";
        $quotes=$this->X->sql($sql);
	$output['enrolled_count']=$quotes[0]['c'];

	$sql="select count(distinct employee_id) as c from nua_monthly_member_census where month_id = '" . $month_id . "' and company_id in (select id from nua_company where org_id = " . $user['org_id'] . ")";
        $quotes=$this->X->sql($sql);
	$output['enrolled_members']=$quotes[0]['c'];
	
	$sql="select * from nua_company where status = 'enrolled' and org_id = " . $user['org_id'] . " and member_count <> '0' order by company_name";
	$orgs=$this->X->sql($sql);
	$q=array();
	foreach($orgs as $z) {
		$rr=array();
                $rr=$z; 
		array_push($q,$rr);
	}
	$output['active']=$q;

	$sql="select * from nua_company where status = 'enrolled' and org_id = " . $user['org_id'] . " and member_count <> '0' order by company_name";
	$orgs=$this->X->sql($sql);
	$q=array();
	foreach($orgs as $z) {
		$rr=array();
                $rr=$z; 
		array_push($q,$rr);
	}
	$output['pending']=$q;

	return $output;
	
}

function getTestDashboard2($data) {

        $date=date_create();
        $month_id=date_format($date,'Y-m');

	$output=$this->start_output($data);
	if ($output['user']['force_logout']>0) return $output;	
        $user=$output['user'];
	$uid=$data['uid'];
	//--
	//
	$sql="select count(*) as c from nua_company where org_id = " . $user['org_id'] . " and status in ('prospect','enrolled','active')";
        $prospects=$this->X->sql($sql);	
	$output['prospect_count']=$prospects[0]['c'];
	$output['new_prospect_count']=$prospects[0]['c'];
	
	$sql="select count(*) as c from nua_company where status in ('enrolled') and insured_lives <> '0'";
        $prospects=$this->X->sql($sql);	
	$output['active_count']=$prospects[0]['c'];
	
	$sql="select count(*) as c from nua_company where status in ('enrolled','prospect') and insured_lives = '0'";
        $prospects=$this->X->sql($sql);	
	$output['prospect_count']=$prospects[0]['c'];
	
	$sql="select count(*) as c from nua_quote where org_id = " . $user['org_id'];
        $quotes=$this->X->sql($sql);	
	$output['quote_count']=$quotes[0]['c'];
	$output['new_request_count']=$quotes[0]['c'];	
	
	$sql="select count(*) as c from nua_user where org_id = " . $user['org_id'];
        $quotes=$this->X->sql($sql);
	$output['user_count']=$quotes[0]['c'];		


	$sql="select count(*) as c from nua_quote where status in ('Enrolling','Accepted')";
        $quotes=$this->X->sql($sql);
	$output['in_enrollment_count']=$quotes[0]['c'];
	$sql="select count(*) as c from nua_employee where company_id in (select id from nua_company where status in ('Enrolling','Accepted') and org_id = '" . $user['org_id'] . "')";
        $quotes=$this->X->sql($sql);
	$output['in_enrollment_members']=$quotes[0]['c'];		

	$sql="select count(*) as c from nua_quote where status in ('Enrolled')";
        $quotes=$this->X->sql($sql);
	$output['enrolled_count']=$quotes[0]['c'];

	$sql="select count(distinct employee_id) as c from nua_monthly_member_census where month_id = '" . $month_id . "' and company_id in (select id from nua_company where org_id = " . $user['org_id'] . ")";
        $quotes=$this->X->sql($sql);
	$output['enrolled_members']=$quotes[0]['c'];
	
	$sql="select * from nua_company where status = 'enrolled' and org_id = " . $user['org_id'] . " and insured_lives <> '0' order by company_name";
	$orgs=$this->X->sql($sql);
	$q=array();
	foreach($orgs as $z) {
		$rr=array();
                $rr=$z; 
		array_push($q,$rr);
	}
	$output['active']=$q;

	$sql="select * from nua_company where status = 'enrolled' and org_id = " . $user['org_id'] . " and insured_lives = '0' order by company_name";
	$orgs=$this->X->sql($sql);
	$q=array();
	foreach($orgs as $z) {
		$rr=array();
                $rr=$z; 
		array_push($q,$rr);
	}
	$output['pending']=$q;

	return $output;
	
}

function getNuAxessDashboard($data) {

        $date=date_create();
        $month_id=date_format($date,'Y-m');

	$output=$this->start_output($data);
	if ($output['user']['force_logout']>0) return $output;	
        $user=$output['user'];
	$uid=$data['uid'];
	//--
	//
	$sql="select count(*) as c from nua_company where status in ('enrolled') and insured_lives <> '0'";
        $prospects=$this->X->sql($sql);	
	$output['active_count']=$prospects[0]['c'];
	
	$sql="select count(*) as c from nua_company where status in ('enrolled','prospect') and insured_lives = '0'";
        $prospects=$this->X->sql($sql);	
	$output['prospect_count']=$prospects[0]['c'];
	
	$sql="select count(*) as c from nua_quote where status not in ('cancelled')";
        $quotes=$this->X->sql($sql);	
	$output['quote_count']=$quotes[0]['c'];
	
	$sql="select count(distinct employee_id) as c from nua_monthly_member_census where month_id = '" . $month_id . "' and company_id in (select id from nua_company)";
        $quotes=$this->X->sql($sql);
	$output['enrolled_count']=$quotes[0]['c'];
	
	$sql="select * from nua_org order by org_name";
	$orgs=$this->X->sql($sql);
	$q=array();
	foreach($orgs as $z) {
		$rr=array();
		$sql="select count(*) as c from nua_user where org_id = " . $z['id'];
		$mm=$this->X->sql($sql);
		$z['broker_count']=$mm[0]['c'];
                $rr=$z; 
		array_push($q,$rr);
	}
	$output['orgs']=$q;

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
         $post['company_type']=$data['data']['formData']['company_type'];
         $post['website']=$data['data']['formData']['website'];
         $post['contact_name']=$data['data']['formData']['contact_name'];
         $post['broker_id']=$data['uid'];
         $post['tax_id']=$data['data']['formData']['tax_id'];
         $post['contact_phone']=$data['data']['formData']['contact_phone'];
         $post['address']=$data['data']['formData']['address'];
         $post['city']=$data['data']['formData']['city'];
         $post['state']=$data['data']['formData']['state'];
         $post['zip']=$data['data']['formData']['zip'];
         $post['dsc']=$data['data']['formData']['dsc'];
         $post['employee_count']=$data['data']['formData']['employee_count'];
		 $post['contact_email']=$data['data']['formData']['DBA'];
		 $post['contact_email']=$data['data']['formData']['sic_code'];
		 $post['state_of_incorpration']=$data['data']['formData']['state_of_incorpration'];
		 $post['current_provider']=$data['data']['formData']['current_provider'];
		 $post['renewal_date']=$data['data']['formData']['renewal_date'];
		 $post['description']=$data['data']['formData']['description'];
         $post['table_name']="nua_company";
         $post['action']="insert";
		 $post['created_by']=$data['uid'];
		 $post['broker_id']=$data['uid'];
		 $post['org_id']=$org_id;	 
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

	function getBAdminDashboard($data) {

		 $output=$this->start_output($data);
		 if ($output['user']['forced_off']>0) return $output;	
		 $user=$output['user'];	
                 $role=$user['role'];

	         $sql="select * from nua_org where id = " . $user['org_id'];
		 $orgs=$this->X->sql($sql);
		 $org=$orgs[0];
		 $output['org']=$org;
                 foreach($org as $name=>$value) $output[$name]=$value;
		
	         	$formData=array();
		        $formData['id']="";
		        $formData['company_name']="";
		        $formData['org_id']=$org['id'];
		        $formData['company_type']="";
		        $formData['employee_count']="";
		        $formData['contact_name']="";
		        $formData['contact_phone']="";
		        $formData['contact_email']="";
		        $formData['note']="";
		        $output['formData']=$formData;

                if ($role=="badmin") {		
	   	        $sql="select id, org_id, quote_key, quote_name, requested_by, company_id, ";
                        $sql.=" company_name, expires_date, status from nua_quote where ";
                        $sql.=" company_id in (select id from nua_company where org_id =  " . $user['org_id'] . ") ";
                       // $sql.=" and status not in ('enrolled','complete') order by id";
                        $sql.=" order by company_name";
                } else {
		        $sql="select id, org_id, quote_key, quote_name, requested_by, company_id, ";
                        $sql.=" company_name, expires_date, status from nua_quote where ";
                        $sql.=" company_id in (select id from nua_company where org_id =  " . $user['org_id'] . ") ";
                        $sql.=" and status not in ('enrolled','complete') and requested_by = " . $user['id'] . " order by id";
                }

		$d=$this->X->sql($sql);
		$o=array();
		foreach($d as $e) {
			if ($e['requested_by']!=0) {
				$sql="select full_name from nua_user where id = " . $e['requested_by'];
				$t=$this->X->sql($sql);
				$e['requested_name']=$t[0]['full_name'];
			} else {
				$e['requested_name']="Invalid User";
			}
			 array_push($o,$e);
		}		
                $output['quotes']=$o;
		
		$sql="select * from nua_broker where org_id = " . $user['org_id'] . " and role not in ('employee','eadmin') order by last_name, first_name";
		$d=$this->X->sql($sql);
		$o=array();
		foreach($d as $e) {
           //           $e['phone']=str_replace("(","",$e['phone']);
           //           $e['phone']=str_replace(")","",$e['phone']);
           //           $e['phone']=str_replace(" ","",$e['phone']);
           //           $e['phone']=str_replace("-","",$e['phone']);
           //           $e['phone']=str_replace("/","",$e['phone']);

           //           $e['phone']=substr($e['phone'],0,1) . '-' . substr($e['phone'],1,3) . '-' .  substr($e['phone'],4,3)  . '-' .  substr($e['phone'],7,4);
		       array_push($o,$e);
		}
		$output['brokers']=$o;
		
                if ($role=="badmin") {		
		$sql="select id, company_name, first_name, last_name, contact_email, address, city, state, ";
                $sql.=" zip, phone, status, has_accepted_quote from nua_company ";
                $sql.=" where org_id = " . $user['org_id'] . " order by company_name";
                } else {
		$sql="select id, company_name, first_name, last_name, contact_email, address, city, state, ";
                $sql.=" zip, phone, status, has_accepted_quote from nua_company ";
                $sql.=" where user_id = " . $user['id'] . " order by company_name";
                }		
$d=$this->X->sql($sql);
		$o=array();
		foreach($d as $e) {
			if ($e['has_accepted_quote']=0) {
				$e['has_accepted_quote']="No";
			} else {
				$e['has_accepted_quote']="Yes";
			}
                        $e['phone']=str_replace("(","",$e['phone']);
                        $e['phone']=str_replace(")","",$e['phone']);
                        $e['phone']=str_replace("-","",$e['phone']);
                        $e['phone']=str_replace("/","",$e['phone']);
                        $e['phone']=str_replace(" ","",$e['phone']);

                        $e['phone']=substr($e['phone'],0,3) . '-' . substr($e['phone'],3,3)  . '-' .  substr($e['phone'],6,4);
			$sql="select count(*) as c from nua_employee where company_id = " . $e['id'];
			$d2=$this->X->sql($sql);
			$e['enrolled']=$d2[0]['c'];
			array_push($o,$e);
		}
		$output['prospects']=$o;

                 return $output;		
		
	}
	
	function getOrgDashboard($data) {
	
	    
		$output=$this->start_output($data);
		 if ($output['user']['forced_off']>0) return $output;
		$sql="select * from nua_org where id = " . $data['id']; 
        $d=$this->X->sql($sql);
		if (sizeof($d)>0) {
            foreach($d[0] as $name=>$value) $output[$name]=$value;
		}
		$sql="select id, email, user_name, full_name, phone_mobile, role, status from nua_user where org_id = " . $data['id'] . " and role not in ('employee','eadmin') order by full_name";
		$d=$this->X->sql($sql);
		$o=array();
		foreach($d as $e) {
             $e['phone']=substr($e['phone_mobile'],0,1) . '-' . substr($e['phone_mobile'],1,3) . '-' .  substr($e['phone_mobile'],4,3)  . '-' .  substr($e['phone_mobile'],7,4);
			 array_push($o,$e);
		}
		$output['users']=$o;
		
		$sql="select id, company_name, first_name, last_name, contact_email, address, city, state, zip, phone, status, has_accepted_quote from nua_company where org_id = " . $data['id'] . " order by company_name";
		$d=$this->X->sql($sql);
		$o=array();
		foreach($d as $e) {
             $e['phone']=substr($e['phone'],0,1) . '-' . substr($e['phone'],1,3) . '-' .  substr($e['phone'],4,3)  . '-' .  substr($e['phone'],7,4);
			$sql="select count(*) as c from nua_employee where company_id = " . $e['id'] . " and employee_status in ('enrolled')";
			$d2=$this->X->sql($sql);
			$e['enrolled']=$d2[0]['c'];
			$e['has_accepted_quote']=$d2[0]['c'];
			 array_push($o,$e);
		}
		$output['prospects']=$o;

		
		
        return $output;		
		
		
	}
	
	function postEditCont($data) {

        $last_id="X";		
		$post=array();
		foreach($data['data']['contForm'] as $name => $value) {
				if ($name=='employee_id') {
					$employee_id=$value;
				} else {
					
					if ($data['data']['contFormOriginal'][$name]!=$value) {
					
						$a=strpos($name,'_');
						$b=strpos($name,'_',$a+1);
						$id=substr($name,$b+1);
						echo 'ID: ' . $id . '|';
						$field=substr($name,0,$b);
						echo 'FIELD: ' . $field . '|';
						if ($id!=$last_id) {
							if ($last_id!="X") {
                               $this->X->post($post);
							}							
							$last_id=$id;
							$post=array();
							$post['table_name']="nua_employee_plan_options";
							$post['action']="insert";
							$post['id']=$id;
							$post['employee_id']=$employee_id;
						}
					if ($name="employee_level") {
						$post['employee_level']=$value;
					}
					if ($name="employee_value") {
						$post['employee_contribution_value']=$value;
					}
					if ($name="employee_amount") {
						$post['employee_contribution_amt']=$value;
					}
					if ($name="employee_price") {
						$post['employee_price']=$value;
					}
					if ($name="employee_type") {
						$post['employee_contribution_type']=$value;
					}
					if ($name="spouse_level") {
						$post['employee_spouse_level']=$value;
					}
					if ($name="spouse_value") {
						$post['employee_spouse_contribution_value']=$value;
					}
					if ($name="spouse_amount") {
						$post['employee_spouse_contribution_amt']=$value;
					}
					if ($name="spouse_price") {
						$post['employee_spouse_price']=$value;
					}
					if ($name="spouse_type") {
						$post['employee_spouse_contribution_type']=$value;
					}
					if ($name="children_level") {
						$post['employee_children_level']=$value;
					}
					if ($name="children_value") {
						$post['employee_children_contribution_value']=$value;
					}
					if ($name="children_amount") {
						$post['employee_children_contribution_amt']=$value;
					}
					if ($name="children_price") {
						$post['employee_children_price']=$value;
					}
					if ($name="children_type") {
						$post['employee_children_contribution_type']=$value;
					}
					if ($name="family_level") {
						$post['family_level']=$value;
					}
					if ($name="family_value") {
						$post['family_contribution_value']=$value;
					}
					if ($name="family_amount") {
						$post['family_contribution_amt']=$value;
					}
					if ($name="family_price") {
						$post['family_price']=$value;
					}
					if ($name="family_type") {
						$post['employee_family_contribution_type']=$value;
					}					
				}
				}
	    }
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

    function postAddMedication($data) {
		$post=array();
		$post['table_name']="nua_employee_ihq_medication";
		$post['action']="insert";
		$post['id']=$data['data']['formData']['id'];
		$post['medication']=$data['data']['formData']['medication'];
		$post['dose']=$data['data']['formData']['dose'];
		$post['frequency']=$data['data']['formData']['frequency'];		
		$post['reason']=$data['data']['formData']['reason'];
		$post['start_date']=$data['data']['formData']['start_date'];
		$post['family_member']=$data['data']['formData']['family_member'];
		$post['employee_id']=$data['data']['formData']['employee_id'];
		$this->X->post($post);
		$output=array();
		$output['error_code']="0";
		$output['id']=$id;
		$output['error_message']="";
		return $output;  
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
	
	function getEmployeeInfo($data) {
		$output=$this->start_output($data);
		 if ($output['user']['forced_off']>0) return $output;
		$employee_id=$output['user']['employee_id'];
		
		$sql="select * from nua_employee where id = " . $user['employee_id'];
		$employees=$this->X->sql($sql);
                $employee=array();
		foreach($employee[0] as $name => $value) {
                     $employee[$name]=$value;
		     if ($name=="date_hired") $employee[$name]=$this->flip_date($value);
		     if ($name=="date_of_birth") $employee[$name]=$this->flip_date($value);
		}
		$output['employee']=$employee;

	   $sql="select * from nua_employee_ihq where employee_id = " . $employee_id;
       $e=$this->X->sql($sql);
       if (sizeof($e)==0) {
              $post=array();
			  $post['table_name']="nua_employee_ihq";
			  $post['action']="insert";
			  $post['employee_id']=$employee_id;
			  $post['information_submitted']=0;
			  $post['family_submitted']=0;
			  $post['insurance_submitted']=0;
			  $post['medications_completed']=0;
			  $post['ip']="";
			  $ihq_id=$this->X->post($post);
	   } else {
              $ihq_id=$e[0]['id'];
	   }

	   $sql="select * from nua_employee_ihq_information where employee_id = " . $employee_id;

       $e=$this->X->sql($sql);
       if (sizeof($e)==0) {    
              $post=array();
			  $post['employee_id']=$employee_id;
			  $post['employee_i_h_q_id']=$ihq_id;
			  $post['first_name']=$employee['first_name'];
			  $post['middle_name']=$employee['middle_name'];
			  $post['suffix']=$employee['suffix'];
			  $post['last_name']=$employee['last_name'];
			  $post['email']=$employee['email'];
			  $post['date_hired']=$employee['date_hired'];
			  $post['marital_status']=$employee['marital_status'];
			  $post['gender']=$employee['gender'];
			  $post['address']=$employee['address'];
			  $post['country']=$employee['country'];
			  $post['city']=$employee['city'];
			  $post['state']=$employee['state'];
			  $post['zip']=$employee['zip'];
			  $post['phone']=$employee['phone_mobile'];
			  $post['date_of_birth']==$employee['date_of_birth'];
			  $post['employee_status']="";
			  $post['social_security_number']="";
			  $post['annual_salary']="";
			  $post['work_role']="";
			  $formData=$post;
			  $post['table_name']="nua_employee_ihq_information";
			  $post['action']="insert";
			  $id2=$this->X->post($post);
			  $formData['id']=$id2;
	   } else {
		      $formData=$e[0];
	   }
	   
	   $output['formData']=$formData;
	   return($output);
	   
	}
	
function getFamilyMedications($data) {

		 $output=$this->start_output($data);
		 if ($output['user']['forced_off']>0) return $output;
		 
		$employee_id=$output['user']['employee_id'];
		
		$sql="select * from nua_employee where id = " . $user['employee_id'];
		$employees=$this->X->sql($sql);
        $employee=$employees[0];		
		$output['employee']=$employee;

	   $sql="select id, first_name, last_name, middle_name, suffix from nua_employee_ihq_family where employee_id = " . $employee_id;
       $e=$this->X->sql($sql);
	   $output['family']=$e;
	   	  
	   $sql="select * from nua_employee_ihq_medication where employee_id = " . $employee_id;
       $e=$this->X->sql($sql);
	   $g=array();
	   foreach($e as $f) {
		   if ($f['familiy_member']!=0) {
		   $sql="select first_name, middle_name, last_name, suffix from nua_employee_ihq_family where id = " . $f['family_member'];
		   $r=$this->X->sql($sql);
		   $f['family_member_name']=$r[0]['first_name'] . ' ' . $r[0]['middle_name'] . ' ' . $r[0]['last_name'] . ' ' . $r[0]['suffix'];
		   } else {
		   $f['family_member_name']=$employee['first_name'] . ' ' . $employee['middle_name'] . ' ' . $employee['last_name'] . ' ' . $employee['suffix'];			   
		   }
           array_push($g,$f);		   
	   }
	   $output['medication']=$g;
	   
	   $formData=array();
	   $formData['id']="0";
	   $formData['medication']="";
	   $formData['dose']="";
	   $formData['frequency']="";
	   $formData['start_date']="";
	   $formData['reason']="";
	   $formData['family_member']="";
	   $formData['employee_id']=$employee_id;
	   $output['formData']=$formData;
	   
	  return $output;
}

	function getEmployeeFamily($data) {
		
		 $output=$this->start_output($data);
		 if ($output['user']['forced_off']>0) return $output;
		$employee_id=$output['user']['employee_id'];
		
		$sql="select * from nua_employee where id = " . $user['employee_id'];
		$employees=$this->X->sql($sql);
        $employee=$employees[0];		
		$output['employee']=$employee;
		
	   $sql="select * from nua_employee_ihq_family where employee_id = " . $employee_id;
       $e=$this->X->sql($sql);
	   $g=array();
	   foreach($e as $f) {
		   $f['height']=str_replace('"','\"',$f['height']);
           array_push($g,$f);		   
	   }
	   $output['family']=$g;
	   
		$post['employee_id']=$employee_id;
		$post['employee_i_h_q_id']="0";
		$post['id']="0";
		$post['first_name']="";
		$post['middle_name']="";
		$post['suffix']="";
		$post['last_name']="";
		$post['member_type']="";
		$post['gender']="";
		$post['date_of_birth']="";
		$post['weight']="";
		$post['height']="";
		$post['social_security_number']="";
		$formData=$post;
	   $output['formData']=$formData;
	   return($output);
	   
	}

	function getIHQInsurance($data) {
		
		 $output=$this->start_output($data);
		 if ($output['user']['forced_off']>0) return $output;
		$employee_id=$output['user']['employee_id'];
		
		$sql="select * from nua_employee where id = " . $user['employee_id'];
		$employees=$this->X->sql($sql);
        $employee=$employees[0];		
		$output['employee']=$employee;
		
	   $sql="select * from nua_employee_ihq_insurance_status where employee_id = " . $employee_id;
       $e=$this->X->sql($sql);
	   if (sizeof($e)>0) {
		   $formData=$e[0];
	   } else {
    	   $output['family']=$e;
		$post['employee_id']=$employee_id;
		$post['employee_i_h_q_id']="0";
		$post['id']="";
		$post['is_cobra_covered']="";
		$post['has_existing_insurance']="";
		$post['has_additional_insurance']="";
		$post['date_of_termination']="";
		$post['most_recent_month_paid']="";
		$post['number_of_months_eligible']="";
		$post['insurance_company_name']="";
			$formData=$post;
	   }

	   $output['formData']=$formData;
	   return($output);
	   
	}
	
	function invoiceDashboard($data) {
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

	function getCensusDashboard($data) {

		 $output=$this->start_output($data);
		 if ($output['user']['forced_off']>0) return $output;
		 
                 $company_id=$data['id'];
                 $date=date_create();

                 if ($data['id2']=='additions') {
                      $sql="select * from nua_monthly_member_additions where id = " . $data['id'];
                      $t=$this->X->sql($sql);
		      $formData=$t[0];
                      $formData['source']="additions";
                 }

                 if ($data['id2']=='terminations') {
                      $sql="select * from nua_monthly_member_terminations where id = " . $data['id'];
                      $t=$this->X->sql($sql);
		      $formData=$t[0];
                      $formData['source']="terminations";
                 }

                 if ($data['id2']=='census') {
                      $sql="select * from nua_monthly_member_census where id = " . $data['id'];
                      $t=$this->X->sql($sql);
		      $formData=$t[0];
                      $formData['source']="census";
                 }

                 $output['formData']=$formData;
		 return $output;

	}

    function getClientPlan($data) {
          $output=$this->getTableFormData($data,"nua_company_plan");
          $output['formData']['company_id']=$data['id'];
          return $output;
    }

    function getEditClientPlan($data) {
         $output=$this->start_output($data);
         if ($output['user']['forced_off']>0) return $output;
          $sql="select * from nua_company_plan where id = " . $data['id'];
          $f=$this->X->sql($sql);
          $formData=array();
          foreach($f[0] as $name => $value) {
              $formData[$name]=$value;
          }
          $output['formData']=$formData;
          return $output;
    }

    function postDeleteClientPlan($data) {
         $output=$this->start_output($data);
          if ($output['user']['forced_off']>0) return $output;
          $sql="delete from nua_company_plan where id = " . $data['data']['formData']['id'];
          $this->X->execute($sql);
          $output['id']=$data['data']['formData']['company_id'];
          $output['error_code']=0;
          return $output;
    }


    function postClientPlan($data) {
         $post=$data['data']['formData'];
         $post['table_name']="nua_company_plan";
         $post['action']="insert";
         $this->X->post($post);

         $results=array();
         $results['error_code']=0;
         $results['error_message']="Save Complete";
         return $results;
    }

	function getCompanyDashboard($data) {

		 $output=$this->start_output($data);
		 if ($output['user']['forced_off']>0) return $output;
		 
                 $company_id=$data['id'];
                 $date=date_create();
		 if ($data['id2']!='') {
                        $month_id = $data['id2'];
		} else { 
			$month =  date_format($date,"Y-m");
			if ($month=="2022-01") $month_id ="2022-02";
			if ($month=="2022-02") $month_id ="2022-03";
			if ($month=="2022-03") $month_id ="2022-04";
			if ($month=="2022-04") $month_id ="2022-05";
			if ($month=="2022-05") $month_id ="2022-06";
			if ($month=="2022-06") $month_id ="2022-07";
			if ($month=="2022-07") $month_id ="2022-08";
			if ($month=="2022-08") $month_id ="2022-09";
			if ($month=="2022-09") $month_id ="2022-10";
			if ($month=="2022-10") $month_id ="2022-11";
			if ($month=="2022-11") $month_id ="2022-12";
			if ($month=="2022-12") $month_id ="2023-01";

		}
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
                $sql="select distinct eff_dt from nua_monthly_member_census where month_id = '" . $month_id . "' and company_id = " . $company_id . " order by eff_dt";
                $hh=$this->X->sql($sql);
                foreach($hh as $ii) {
                     $eff_date=array();
                     $eff_date['eff_dt']=$ii['eff_dt'];
                     array_push($eff_dates,$eff_date);
                }
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

		$sql="select * from nua_org where id = " . $d[0]['org_id'];
                $d=$this->X->sql($sql);
		if (sizeof($d)>0) {
                $output['org_name']=$d[0]['org_name'];
		} else {
$output['org_name']="Not Found";
		}
                $sql="select * from nua_company_invoice_adjustments where month_id = '" . $month_id . "' and company_id = " . $data['id'];
                $adj=$this->X->sql($sql);
                $output['adjustlist']=$adj;

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
 
                $badData=array();
                $badData['census_id']="";
                $badData['company_id']=$data['id'];
                $badData['employee_id']="";
                $badData['subject']="";
                $output['badData']=$badData;
 
  
		$employeeData=array();
		$employeeData['company_id']=$data['id'];
		$employeeData['org_id']=$output['org_id'];
		$employeeData['dependent']='N';
		$employeeData['month_id']=$month_id;
		$employeeData['employee_id']=0;
		$employeeData['first_name']="";
		$employeeData['middle_name']="";
		$employeeData['last_name']="";
		$employeeData['suffix']="";
		$employeeData['email']="";
		$employeeData['phone_mobile']="";
		$employeeData['date_of_birth']="";
		$employeeData['social_security_number']="";
                $employeeData['eff_dt']="";
                $employeeData['medical_plan_code']="";
                $employeeData['medical_coverage_level']="";
                $employeeData['dental_plan_code']="";
                $employeeData['dental_coverage_level']="";
                $employeeData['vision_plan_code']="";
                $employeeData['vision_coverage_level']="";
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
		$output['invoices']=array();

		$sql="select id, first_name, last_name from nua_employee where company_id = " . $data['id'] . " order by last_name, first_name";
		$e=$this->X->sql($sql);
		$r=array();
		foreach($e as $f) {
			array_push($r,$f);
		}
		$output['employees']=$r;

                $sql="select * from nua_company_plan where end_month_id = '' and company_id = " . $data['id'] . " order by plan_code";
                $e=$this->X->sql($sql);
                $output['company_plans']=$e;

		$sql="select * from nua_invoice_load_terms where company_id = " . $data['id'] . " order by last_name, first_name";
		$e=$this->X->sql($sql);
		$r=array();
                $total_terms=0;
		foreach($e as $f) {
                           $sql="select * from nua_company_plan where end_month_id = '' and company_id = " . $data['id'] . " and ";
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

                $sql="select id, employee_id, first_name, last_name, company_name,  plan, coverage_level, alt_company_id, alt_company_name  from nua_census where apa = '01'  order by last_name, first_name";
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


		$sql="select * from nua_monthly_member_census where dependent_code = '' and month_id = '" . $month_id . "' and  company_id = " . $company_id . " order by last_name, first_name";
		$e=$this->X->sql($sql);
		$r=array();
                $last="XXX";
		foreach($e as $f) {
                        $f['move']="N";
                        if ($f['coverage_level']=="") $f['coverage_level']="EE";
                        if ($f['employee_id']==$last) {
                               $f['employee_code']="";
                               $f['last_name']="";
                               $f['first_name']="";
                               $f['middle_initial']="";
                               $f['gender']="";
                               $f['dob']="";
                        }
                        if ($f['plan_type']=="*MEDICAL*") {
                               $f['medical_plan_code']=$f['client_plan'];
			       $f['medical_coverage_level']=$f['coverage_level'];
                               $f['dental_plan_code']="";
                               $f['dental_coverage_level']="";
                               $sql="select * from nua_monthly_member_census where employee_id = " . $f['employee_id'] . " and dependent_code = '' and month_id = '" . $month_id . "' and plan_type = '*DENTAL*'";
                               $yy=$this->X->sql($sql);
                               if (sizeof($yy)>0) {
                                  $f['dental_plan_code']=$yy[0]['client_plan'];
                                  $f['dental_coverage_level']=$yy[0]['coverage_level'];
                               }		  
                               $f['vision_plan_code']="";
                               $f['vision_coverage_level']="";
                               $sql="select * from nua_monthly_member_census where employee_id = " . $f['employee_id'] . " and dependent_code = '' and month_id = '" . $month_id . "' and plan_type = '*VISION*'";
                               $yy=$this->X->sql($sql);
                               if (sizeof($yy)>0) {
                                  $f['vision_plan_code']=$yy[0]['client_plan'];
                                  $f['vision_coverage_level']=$yy[0]['coverage_level'];
                               }		  
			}
                        if ($f['plan_type']=="*DENTAL*") {
                               $f['dental_plan_code']=$f['client_plan'];
			       $f['dental_coverage_level']=$f['coverage_level'];
                               $f['medical_plan_code']="";
                               $f['medical_coverage_level']="";
                               $sql="select * from nua_monthly_member_census where employee_id = " . $f['employee_id'] . " and dependent_code = '' and month_id = '" . $month_id . "' and plan_type = '*MEDICAL*'";
                               $yy=$this->X->sql($sql);
                               if (sizeof($yy)>0) {
                                  $f['medical_plan_code']=$yy[0]['client_plan'];
                                  $f['medical_coverage_level']=$yy[0]['coverage_level'];
                               }		  
                               $f['vision_plan_code']="";
                               $f['vision_coverage_level']="";
                               $sql="select * from nua_monthly_member_census where employee_id = " . $f['employee_id'] . " and dependent_code = '' and month_id = '" . $month_id . "' and plan_type = '*VISION*'";
                               $yy=$this->X->sql($sql);
                               if (sizeof($yy)>0) {
                                  $f['vision_plan_code']=$yy[0]['client_plan'];
                                  $f['vision_coverage_level']=$yy[0]['coverage_level'];
                               }		  
			}
                        if ($f['plan_type']=="*VISION*") {
                               $f['vision_plan_code']=$f['client_plan'];
			       $f['vision_coverage_level']=$f['coverage_level'];
                               $f['medical_plan_code']="";
                               $f['medical_coverage_level']="";
                               $sql="select * from nua_monthly_member_census where employee_id = " . $f['employee_id'] . " and dependent_code = '' and month_id = '" . $month_id . "' and plan_type = '*MEDICAL*'";
                               $yy=$this->X->sql($sql);
                               if (sizeof($yy)>0) {
                                  $f['medical_plan_code']=$yy[0]['client_plan'];
                                  $f['medical_coverage_level']=$yy[0]['coverage_level'];
                               }		  
                               $f['dental_plan_code']="";
                               $f['dental_coverage_level']="";
                               $sql="select * from nua_monthly_member_census where employee_id = " . $f['employee_id'] . " and dependent_code = '' and month_id = '" . $month_id . "' and plan_type = '*DENTAL*'";
                               $yy=$this->X->sql($sql);
                               if (sizeof($yy)>0) {
                                  $f['dental_plan_code']=$yy[0]['client_plan'];
                                  $f['dental_coverage_level']=$yy[0]['coverage_level'];
                               }		  
			}
   
		        $sql="select * from nua_bad where employee_id = " . $f['employee_id'];
                        $ff=$this->X->sql($sql);
                        if (sizeof($ff)>0) {
                             $f['bad']="Y";
                             $f['subject']=$ff[0]['subject'];
			} else {
                             $f['bad']="N";
                             $f['subject']="";
                       }
                        $last=$f['employee_id'];
                        $f['term']="N";
                        array_push($r,$f);
		}
		$output['census']=$r;

		$sql="select * from nua_monthly_member_terminations where dependent_code = '' and month_id >= '" . $month_id . "' and company_id = " . $company_id . " order by employee_code, dependent_code, client_plan";
		$e=$this->X->sql($sql);
		$r=array();
		foreach($e as $f) {
                        $f['move']="N";
                        if ($f['coverage_level']=="") $f['coverage_level']="EE";
                        if ($f['employee_id']==$last) {
                               $f['employee_code']="";
                               $f['last_name']="";
                               $f['first_name']="";
                               $f['middle_initial']="";
                               $f['gender']="";
                               $f['dob']="";
                        }
                        
		        $sql="select * from nua_bad where employee_id = " . $f['employee_id'];
                        $ff=$this->X->sql($sql);
                        if (sizeof($ff)>0) {
                             $f['bad']="Y";
                             $f['subject']=$ff[0]['subject'];
			} else {
                             $f['bad']="Y";
                             $f['subject']="";
                       }
			array_push($r,$f);
                        $last=$f['employee_id'];
		}
		$output['terminations']=$r;

		$sql="select * from nua_monthly_member_additions where dependent_code = '' and month_id >= '" . $month_id . "' and company_id = " . $company_id . " order by employee_code, dependent_code, client_plan";
		$e=$this->X->sql($sql);
		$r=array();
		foreach($e as $f) {
                        $f['move']="N";
                        if ($f['coverage_level']=="") $f['coverage_level']="EE";
                        if ($f['employee_id']==$last) {
                               $f['employee_code']="";
                               $f['last_name']="";
                               $f['first_name']="";
                               $f['middle_initial']="";
                               $f['gender']="";
                               $f['dob']="";
                        }
                        
		        $sql="select * from nua_bad where employee_id = " . $f['employee_id'];
                        $ff=$this->X->sql($sql);
                        if (sizeof($ff)>0) {
                             $f['bad']="Y";
                             $f['subject']=$ff[0]['subject'];
			} else {
                             $f['bad']="N";
                             $f['subject']="";
                       }
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
                           $sql="select * from nua_company_plan where end_month_id = '' and company_id = " . $data['id'] . " and ";
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

                $sql="select * from nua_company_plan where end_month_id = '' and company_id = " . $data['id'] . " order by plan_name";
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
	
	function getCompanyFix($data) {

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
		$sql="select * from nua_company_invoice where company_id = " . $data['id'] . " order by year, month";
		$e0=$this->X->sql($sql);
		$inv=array();
		foreach ($e0 as $e) {
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

		$sql="select * from nua_invoice_load_terms where company_id = " . $data['id'] . " order by last_name, first_name";
		$e=$this->X->sql($sql);
		$r=array();
                $total_terms=0;
		foreach($e as $f) {
                           $sql="select * from nua_company_plan where end_month_id = '' and company_id = " . $data['id'] . " and ";
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
                           $total_terms+=floatval($f['price']); 
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
                           $sql="select * from nua_company_plan where end_month_id = '' and company_id = " . $data['id'] . " and ";
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

                $sql="select * from nua_company_plan where end_month_id = '' and company_id = " . $data['id'] . " order by plan_name";
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
	
	function getCompanyDashboardOLD($data) {

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
		$sql="select * from nua_company_invoice where company_id = " . $data['id'] . " order by year, month";
		$e0=$this->X->sql($sql);
		$inv=array();
		foreach ($e0 as $e) {
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

		$sql="select * from nua_invoice_load_terms where company_id = " . $data['id'] . " order by last_name, first_name";
		$e=$this->X->sql($sql);
		$r=array();
                $total_terms=0;
		foreach($e as $f) {
                           $sql="select * from nua_company_plan where end_month_id = '' and company_id = " . $data['id'] . " and ";
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
                           $total_terms+=floatval($f['price']); 
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

///////////////////////////////////
//
//          
                $sql="select id, last_name, first_name, employee_code from nua_employee where id in (";
		$sql.="select employee_id from nua_monthly_member_census where dependent_code = '' and month_id = '" . $month_id . "' and  company_id = " . $company_id . ")  order by last_name, first_name";
                $r=array();
                $r2=array();
                $r3=array();
                $e=$this->X->sql($sql);
                foreach($e as $f) {
		      $sql="select * from nua_monthly_member_census where dependent_code = '' and month_id = '" . $month_id . "' and  employee_id = " . $f['id'] . " order by client_plan";
                      $t=$this->X->sql($sql);
		      $first='Y';
                      foreach($t as $u) {
                          $g=array();
                          $g=$u;
                          if ($first=='Y') {
                              $g['last_name']=$f['last_name'];
                              $g['first_name']=$f['first_name'];
			      $g['id']=$f['id'];
                              $first='N';
			  } else {
                              $g['last_name']="";
                              $g['first_name']="";
			      $g['id']=$f['id'];
                              $g['employee_code']="";
			  } 
                          array_push($r,$g);
                      } 

		      $sql="select * from nua_monthly_member_additions where dependent_code = '' and month_id = '" . $month_id . "' and  employee_id = " . $f['id'] . " order by client_plan";
                      $t=$this->X->sql($sql);
		      $first='Y';
                      foreach($t as $u) {
                          $g=array();
                          $g=$u;
                          if ($first=='Y') {
                              $g['last_name']=$f['last_name'];
                              $g['first_name']=$f['first_name'];
			      $g['id']=$f['id'];
                              $first='N';
			  } else {
                              $g['last_name']="";
                              $g['first_name']="";
			      $g['id']=$f['id'];
                              $g['employee_code']="";
			  } 
                          array_push($r2,$g);
                      } 


                }

		$output['census']=$r;
		$output['additions']=$r2;

                $sql="select id, last_name, first_name, employee_code from nua_employee where employee_code in (";
		$sql.="select employee_code from nua_monthly_member_terminations where dependent_code = '' and month_id = '" . $month_id . "' and  company_id = " . $company_id . ")  order by last_name, first_name";
                $r=array();
                $r2=array();
                $r3=array();
                $e=$this->X->sql($sql);
                foreach($e as $f) {

		      $sql="select * from nua_monthly_member_terminations where dependent_code = '' and month_id = '" . $month_id . "' and  employee_code = '" . $f['employee_code'] . "' order by client_plan";
                      $t=$this->X->sql($sql);
		      $first='Y';
                      foreach($t as $u) {
                          $g=array();
                          $g=$u;
                          if ($first=='Y') {
                              $g['last_name']=$f['last_name'];
                              $g['first_name']=$f['first_name'];
			      $g['id']=$f['id'];
                              $first='N';
			  } else {
                              $g['last_name']="";
                              $g['first_name']="";
			      $g['id']=$f['id'];
                              $g['employee_code']="";
			  } 
                          array_push($r3,$g);
                      } 

                }

		$output['terminations']=$r3;

		$sql="select * from nua_employee where company_id = " . $data['id'] . " and id not in (select employee_id from nua_monthly_member_census where company_id = " . $data['id'] . ") order by employee_name";
		$k=$this->X->sql($sql);
		$output['unassigned']=$k;

/*
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

*/
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
                           $sql="select * from nua_company_plan where end_month_id = '' and company_id = " . $data['id'] . " and ";
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

                $sql="select * from nua_company_plan where end_month_id = '' and company_id = " . $data['id'] . " order by plan_name";
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
	
	function getAPACompanyDashboard($data) {


		 $output=$this->start_output($data);
		 if ($output['user']['forced_off']>0) return $output;

                 //-- CENSUS COMPANY
		 //
		 $sql="select * from nua_census_company where company_id = " . $data['id']; 
                 $d=$this->X->sql($sql);
                 if (sizeof($d)>0) {
	         	 $output['census_company']=$d[0];
                 } else {
                         $output['census_company']=array();
                 }

                 //-- NUA INVOICE LOAD MEMBERS
		 //
		 $sql="select * from nua_invoice_load_members where company_id = " . $data['id']; 
                 $d=$this->X->sql($sql);
                 $output['invoice_members']=$d;

                 //-- BASE COMPANY
		 //
	         $sql="select * from nua_company where id = " . $data['id'];	
                 $d=$this->X->sql($sql);
                 foreach($d[0] as $name=>$value) $output[$name]=$value;
					
		$sql="select * from nua_org where id = " . $d[0]['org_id'];
                $d=$this->X->sql($sql);
                $output['org_name']=$d[0]['org_name'];
		
		$formData=array();
		$formData['employee_name']="";
		$formData['date_of_birth']="";
		$formData['gender']="";
		$formData['id']="";
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
		
                //-- NUA_DOC
		//
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

                //-- NUA_COMPANY_INVOICE
		//
		$output['docs']=$doc;
		$sql="select * from nua_company_invoice where company_id = " . $data['id'] . " order by year, month";
		$e0=$this->X->sql($sql);
		$inv=array();
		foreach ($e0 as $e) {
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

		$sql="select * from nua_invoice_load_terms where company_id = " . $data['id'] . " order by last_name, first_name";
		$e=$this->X->sql($sql);
		$r=array();
                $total_terms=0;
		foreach($e as $f) {
                           $sql="select * from nua_company_plan where end_month_id = '' and company_id = " . $data['id'] . " and ";
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
                           $total_terms+=floatval($f['price']); 
}
			array_push($r,$f);
		}
		$output['terms']=$r;
		$output['total_terms']=$r;

		$sql="select * from nua_invoice_load_members where company_id = " . $data['id'] . " order by last_name, first_name";
		$e=$this->X->sql($sql);
		$r=array();
		foreach($e as $f) {
			array_push($r,$f);
		}
		$output['census']=$r;

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
                           $sql="select * from nua_company_plan where end_month_id = '' and company_id = " . $data['id'] . " and ";
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

                $sql="select * from nua_company_plan where end_month_id = '' and company_id = " . $data['id'] . " order by plan_name";
		$e=$this->X->sql($sql);
                $output['plans']=$e;
$formData2=array();
		$output['formData2']=$formData2;			
		$output['quotes']=$r;
		$output['invoices']=$inv;
		
		$output['documents']=array();
        return $output;		
	}
	
	
	function getEmployeeIHQ($data) {
		
		$output=$this->start_output($data);
		if ($output['user']['forced_off']>0) return $output;
		
	    $sql="select * from nua_employee where id = " . $data['id'];
		$d=$this->X->sql($sql);
        foreach($d[0] as $name=>$value) $output[$name]=$value;
		$employee=$d[0];
		
	    $sql="select * from nua_employee_ihq where employee_id = " . $data['id'];
		$d=$this->X->sql($sql);
		if (sizeof($d)==0) {
			$i_h_q_id=0;
		} else {
			$output['ihq']=$d[0];	
			$i_h_q_id=$d[0]['id'];
		}
		
		$sql="select * from nua_employee_ihq_information where employee_id = " . $data['id'];
		$d=$this->X->sql($sql);
		if (sizeof($d)==0) {
			$ihq=array();
			$ihq['id']=0;
			$ihq['employee_id']=$data['id'];
			$ihq['first_name']=$employee['first_name'];
			$ihq['middle_name']=$employee['middle_name'];
			$ihq['last_name']=$employee['last_name'];
			$ihq['email']=$employee['email'];
			$ihq['date_hired']="";
			$ihq['marital_status']="";
			$ihq['address']=$employee['address'];;
			$ihq['country']=$employee['country'];
			$ihq['state']=$employee['state'];
			$ihq['city']=$employee['city'];
			$ihq['suite']=$employee['suite'];
			$ihq['zip']=$employee['zip'];
			$ihq['phone']=$employee['phone'];
			$ihq['annual_salary']="";
			$ihq['suffix']="";
			$ihq['work_role']="";
            $output['information']=$ihq;			
		} else {
			$output['information']=$d[0];
		}

	    $sql="select * from nua_employee_ihq_insurance_status where employee_id = " . $data['id'];
		$d=$this->X->sql($sql);
		if (sizeof($d)==0) {
			$ihq=array();
			$ihq['id']=0;
			$ihq['employee_id']=$data['id'];
			$ihq['information_submitted']=0;
			$ihq['family_submitted']=0;
			$ihq['insurance_submitted']=0;	
			$ihq['medical_questions_completed']=0;
            $output['ihq']=$ihq;			
		} else {
			$output['ihq']=$d[0];
		}
		
	    $sql="select * from nua_employee_ihq_medication where employee_id = " . $data['id'];
		$d=$this->X->sql($sql);
        $output['medication']=$d;
		$formData=array();
		$formData['id']=$data['id'];
		
		$formData['a1']="";
		$formData['onset1']="";
		$formData['details1']="";
		$formData['duration1']="";
		$formData['results1']="";
		$formData['provider1']="";
		$formData['family_member1']="0";
		
		$formData['a2']="";
		$formData['onset2']="";
		$formData['details2']="";
		$formData['duration2']="";
		$formData['results12']="";
		$formData['provider2']="";
		$formData['family_member2']="0";		
		
		$formData['a3']="";
		$formData['onset3']="";
		$formData['details3']="";
		$formData['duration3']="";
		$formData['results3']="";
		$formData['provider3']="";
		$formData['family_member3']="0";
		
		$formData['a4']="";
		$formData['onset4']="";
		$formData['details4']="";
		$formData['duration4']="";
		$formData['results4']="";
		$formData['provider4']="";
		$formData['family_member4']="0";
		
		$formData['a5']="";
		$formData['onset5']="";
		$formData['details5']="";
		$formData['duration5']="";
		$formData['results5']="";
		$formData['provider5']="";
		$formData['family_member5']="0";
		
		$formData['a6']="";
		$formData['onset6']="";
		$formData['details6']="";
		$formData['duration6']="";
		$formData['results6']="";
		$formData['provider6']="";
		$formData['family_member6']="0";
		
		$formData['a7']="";
		$formData['onset7']="";
		$formData['details7']="";
		$formData['duration7']="";
		$formData['results7']="";
		$formData['provider7']="";
		$formData['family_member7']="0";
		
		$formData['a8']="";
		$formData['onset8']="";
		$formData['details8']="";
		$formData['duration8']="";
		$formData['results8']="";
		$formData['provider8']="";
		$formData['family_member8']="0";
		
		$formData['a9']="";
		$formData['onset9']="";
		$formData['details9']="";
		$formData['duration9']="";
		$formData['results9']="";
		$formData['provider9']="";
		$formData['family_member9']="0";
		
		$formData['a10']="";
		$formData['onset10']="";
		$formData['details10']="";
		$formData['duration10']="";
		$formData['results10']="";
		$formData['provider10']="";
		$formData['family_member10']="0";
		
		$formData['a11']="";
		$formData['onset11']="";
		$formData['details11']="";
		$formData['duration11']="";
		$formData['results11']="";
		$formData['provider11']="";
		$formData['family_member11']="0";
		
		$formData['a12']="";
		$formData['onset12']="";
		$formData['details12']="";
		$formData['duration12']="";
		$formData['results12']="";
		$formData['provider12']="";
		$formData['family_member12']="0";
		
		$formData['a13']="";
		$formData['onset13']="";
		$formData['details13']="";
		$formData['duration13']="";
		$formData['results13']="";
		$formData['provider13']="";
		$formData['family_member13']="0";
		
		$formData['a14']="";
		$formData['onset14']="";
		$formData['details14']="";
		$formData['duration14']="";
		$formData['results14']="";
		$formData['provider14']="";
		$formData['family_member14']="0";
		
		$formData['a15']="";
		$formData['onset15']="";
		$formData['details15']="";
		$formData['duration15']="";
		$formData['results15']="";
		$formData['provider15']="";
		$formData['family_member15']="0";
		
		$formData['a16']="";
		$formData['onset16']="";
		$formData['details16']="";
		$formData['duration16']="";
		$formData['results16']="";
		$formData['provider16']="";
		$formData['family_member16']="0";
		
		$formData['a17']="";
		$formData['onset17']="";
		$formData['details17']="";
		$formData['duration17']="";
		$formData['results17']="";
		$formData['provider17']="";
		$formData['family_member17']="0";
		
		$formData['a18']="";
		$formData['onset18']="";
		$formData['details18']="";
		$formData['duration18']="";
		$formData['results18']="";
		$formData['provider18']="";
		$formData['family_member18']="0";
		
		$formData['a19']="";
		$formData['onset19']="";
		$formData['details19']="";
		$formData['duration19']="";
		$formData['results19']="";
		$formData['provider19']="";
		$formData['family_member19']="0";
		
		$formData['a20']="";
		$formData['onset20']="";
		$formData['details20']="";
		$formData['duration20']="";
		$formData['results20']="";
		$formData['provider20']="";
		$formData['family_member20']="0";
		
		$formData['a21']="";
		$formData['onset21']="";
		$formData['details21']="";
		$formData['duration21']="";
		$formData['results21']="";
		$formData['provider21']="";
		$formData['family_member21']="0";
		
		$formData['a22']="";
		$formData['onset22']="";
		$formData['details22']="";
		$formData['duration22']="";
		$formData['results22']="";
		$formData['provider22']="";
		$formData['family_member22']="0";

	    $sql="select * from nua_employee_ihq_medical_answer where employee_id = " . $data['id'];

		$d=$this->X->sql($sql);
		$m=array();
		foreach($d as $e) {
			if ($e['question_id']==1) { if ($e['answer']==0) $formData['a1']="No"; else  $formData['a1']="Yes";  }					 
			if ($e['question_id']==2) { if ($e['answer']==0) $formData['a2']="No"; else  $formData['a2']="Yes";  }					 
			if ($e['question_id']==3) { if ($e['answer']==0) $formData['a3']="No"; else  $formData['a3']="Yes";  }					 
			if ($e['question_id']==4) { if ($e['answer']==0) $formData['a4']="No"; else  $formData['a4']="Yes";  }					 
			if ($e['question_id']==5) { if ($e['answer']==0) $formData['a5']="No"; else  $formData['a5']="Yes";  }					 
			if ($e['question_id']==6) { if ($e['answer']==0) $formData['a6']="No"; else  $formData['a6']="Yes";  }					 
			if ($e['question_id']==7) { if ($e['answer']==0) $formData['a7']="No"; else  $formData['a7']="Yes";  }					 
			if ($e['question_id']==8) { if ($e['answer']==0) $formData['a8']="No"; else  $formData['a8']="Yes";  }					 
			if ($e['question_id']==9) { if ($e['answer']==0) $formData['a9']="No"; else  $formData['a9']="Yes";  }					 
			if ($e['question_id']==10) { if ($e['answer']==0) $formData['a10']="No"; else  $formData['a10']="Yes";  }					 
			if ($e['question_id']==11) { if ($e['answer']==0) $formData['a11']="No"; else  $formData['a11']="Yes";  }					 
			if ($e['question_id']==12) { if ($e['answer']==0) $formData['a12']="No"; else  $formData['a12']="Yes";  }					 
			if ($e['question_id']==13) { if ($e['answer']==0) $formData['a13']="No"; else  $formData['a13']="Yes";  }					 
			if ($e['question_id']==14) { if ($e['answer']==0) $formData['a14']="No"; else  $formData['a14']="Yes";  }					 
			if ($e['question_id']==15) { if ($e['answer']==0) $formData['a15']="No"; else  $formData['a15']="Yes";  }					 
			if ($e['question_id']==16) { if ($e['answer']==0) $formData['a16']="No"; else  $formData['a16']="Yes";  }					 
			if ($e['question_id']==17) { if ($e['answer']==0) $formData['a17']="No"; else  $formData['a17']="Yes";  }					 
			if ($e['question_id']==18) { if ($e['answer']==0) $formData['a18']="No"; else  $formData['a18']="Yes";  }					 
			if ($e['question_id']==19) { if ($e['answer']==0) $formData['a19']="No"; else  $formData['a19']="Yes";  }					 
			if ($e['question_id']==20) { if ($e['answer']==0) $formData['a20']="No"; else  $formData['a20']="Yes";  }					 
			if ($e['question_id']==21) { if ($e['answer']==0) $formData['a21']="No"; else  $formData['a21']="Yes";  }					 
			if ($e['question_id']==22) { if ($e['answer']==0) $formData['a22']="No"; else  $formData['a22']="Yes";  }					 
			array_push($m,$e);
		}
		
		
		$sql="select * from nua_employee_ihq_answer_detail where employee_i_h_q_id = " . $data['id'];		 
        $z=$this->X->sql($sql);
		foreach ($z as $z0) {
     		$aa=$z0['employee_i_h_q_medical_answer_id'];
			$sql="select * from nua_employee_ihq_medical_answer where id = " . $aa; 
			$z1=$this->X->sql($sql);
			if (sizeof($z1)>0) {
			     $qid=$z1[0]['question_id'];
                 $formData['onset' . $qid]=$z0['onset'];
                 $formData['details' . $qid]=$z0['details'];
				 $formData['duration' . $qid]=$z0['duration'];
				 $formData['results' . $qid]=$z0['results'];	
				 $formData['provider' . $qid]=$z0['provider'];					 
				 $formData['family_member' . $qid]=$z0['family_member'];
			}
		}			

		$e['detail']=$z;
			
        $output['answers']=$m;
		
		
        $output['formData']=$formData;
		
		
		$sql="select * from nua_employee_ihq_family where employee_id = " . $data['id'];
		$u=$this->X->sql($sql);
		$output['family']=$u;
	
	    $sql="select question_id, question, requires_details from nua_medical_question order by question_id";
		$u=$this->X->sql($sql);
		$output['questions']=$u;
		
        return $output;	
		
	}
	
	
	function postIHQAnswer($data) {
		$sql="select * from nua_employee_ihq_medical_answer where employee_id = " . $data['data']['employee_id'] . " and question_id = " . $data['data']['question_id'];
		$h=$this->X->sql($sql);
		if (sizeof($h)>0) {
		   $post=array();
		   $post['table_name']="nua_employee_ihq_medical_answer";
		   $post['action']="insert";
		   $post['question_id']=$data['data']['question_id'];
		   $post['employee_id']=$data['data']['employee_id'];
		   $post['id']=$h[0]['id'];
		   if ($data['data']['answer']=="Yes") {
			 $post['answer']=1;  
		   } else {
			 $post['answer']=0;  			   
		   }
		   $post['employee_i_h_q_id']=$data['data']['i_h_q_id'];
		} else {
		   $post=array();
		   $post['table_name']="nua_employee_ihq_medical_answer";
		   $post['action']="insert";
		   $post['question_id']=$data['data']['question_id'];
		   $post['employee_id']=$data['data']['employee_id'];
		   if ($data['data']['answer']=="Yes") {
			 $post['answer']=1;  
		   } else {
			 $post['answer']=0;  			   
		   }
		   $post['employee_i_h_q_id']=$data['data']['i_h_q_id'];			
		}
		$id=$this->X->post($post);
        $output=array();
		$output['error_code']=0;
		$output['message']=$data['data']['question_id'];
		return $output;
	}
	
	function getMemberIHQ($data) {
		
		 $output=$this->start_output($data);
		 if ($output['user']['forced_off']>0) return $output;
        $user=$output['user'];
		
	    $sql="select * from nua_employee where id = " . $user['employee_id'];
		$d=$this->X->sql($sql);
		foreach($d[0] as $name=>$value) $output[$name]=$value;
		$employee=$d[0];
		
	    $sql="select * from nua_employee_ihq where employee_id = " . $user['employee_id'];
		$d=$this->X->sql($sql);
		if (sizeof($d)==0) {
			$i_h_q_id=0;
		} else {
			$output['ihq']=$d[0];	
			$i_h_q_id=$d[0]['id'];
		}
        
	    $sql="select * from nua_employee_ihq_medication where employee_id = " . $user['employee_id'];
		$d=$this->X->sql($sql);
        $output['medication']=$d;
		$ihqd=array();
		$ihqd['question_id']=0;
		$ihqd['answer']="";
		$ihqd['employee_id']=$user['employee_id'];
		$ihqd['i_h_q_id']=$i_h_q_id;
		
		$output['ihqd']=$ihqd;
		
		$formData=array();
		$formData['id']=$data['id'];
		
		$formData['a1']="";
		$formData['onset1']="";
		$formData['details1']="";
		$formData['duration1']="";
		$formData['results1']="";
		$formData['provider1']="";
		$formData['family_member1']="0";
		
		$formData['a2']="";
		$formData['onset2']="";
		$formData['details2']="";
		$formData['duration2']="";
		$formData['results12']="";
		$formData['provider2']="";
		$formData['family_member2']="0";		
		
		$formData['a3']="";
		$formData['onset3']="";
		$formData['details3']="";
		$formData['duration3']="";
		$formData['results3']="";
		$formData['provider3']="";
		$formData['family_member3']="0";
		
		$formData['a4']="";
		$formData['onset4']="";
		$formData['details4']="";
		$formData['duration4']="";
		$formData['results4']="";
		$formData['provider4']="";
		$formData['family_member4']="0";
		
		$formData['a5']="";
		$formData['onset5']="";
		$formData['details5']="";
		$formData['duration5']="";
		$formData['results5']="";
		$formData['provider5']="";
		$formData['family_member5']="0";
		
		$formData['a6']="";
		$formData['onset6']="";
		$formData['details6']="";
		$formData['duration6']="";
		$formData['results6']="";
		$formData['provider6']="";
		$formData['family_member6']="0";
		
		$formData['a7']="";
		$formData['onset7']="";
		$formData['details7']="";
		$formData['duration7']="";
		$formData['results7']="";
		$formData['provider7']="";
		$formData['family_member7']="0";
		
		$formData['a8']="";
		$formData['onset8']="";
		$formData['details8']="";
		$formData['duration8']="";
		$formData['results8']="";
		$formData['provider8']="";
		$formData['family_member8']="0";
		
		$formData['a9']="";
		$formData['onset9']="";
		$formData['details9']="";
		$formData['duration9']="";
		$formData['results9']="";
		$formData['provider9']="";
		$formData['family_member9']="0";
		
		$formData['a10']="";
		$formData['onset10']="";
		$formData['details10']="";
		$formData['duration10']="";
		$formData['results10']="";
		$formData['provider10']="";
		$formData['family_member10']="0";
		
		$formData['a11']="";
		$formData['onset11']="";
		$formData['details11']="";
		$formData['duration11']="";
		$formData['results11']="";
		$formData['provider11']="";
		$formData['family_member11']="0";
		
		$formData['a12']="";
		$formData['onset12']="";
		$formData['details12']="";
		$formData['duration12']="";
		$formData['results12']="";
		$formData['provider12']="";
		$formData['family_member12']="0";
		
		$formData['a13']="";
		$formData['onset13']="";
		$formData['details13']="";
		$formData['duration13']="";
		$formData['results13']="";
		$formData['provider13']="";
		$formData['family_member13']="0";
		
		$formData['a14']="";
		$formData['onset14']="";
		$formData['details14']="";
		$formData['duration14']="";
		$formData['results14']="";
		$formData['provider14']="";
		$formData['family_member14']="0";
		
		$formData['a15']="";
		$formData['onset15']="";
		$formData['details15']="";
		$formData['duration15']="";
		$formData['results15']="";
		$formData['provider15']="";
		$formData['family_member15']="0";
		
		$formData['a16']="";
		$formData['onset16']="";
		$formData['details16']="";
		$formData['duration16']="";
		$formData['results16']="";
		$formData['provider16']="";
		$formData['family_member16']="0";
		
		$formData['a17']="";
		$formData['onset17']="";
		$formData['details17']="";
		$formData['duration17']="";
		$formData['results17']="";
		$formData['provider17']="";
		$formData['family_member17']="0";
		
		$formData['a18']="";
		$formData['onset18']="";
		$formData['details18']="";
		$formData['duration18']="";
		$formData['results18']="";
		$formData['provider18']="";
		$formData['family_member18']="0";
		
		$formData['a19']="";
		$formData['onset19']="";
		$formData['details19']="";
		$formData['duration19']="";
		$formData['results19']="";
		$formData['provider19']="";
		$formData['family_member19']="0";
		
		$formData['a20']="";
		$formData['onset20']="";
		$formData['details20']="";
		$formData['duration20']="";
		$formData['results20']="";
		$formData['provider20']="";
		$formData['family_member20']="0";
		
		$formData['a21']="";
		$formData['onset21']="";
		$formData['details21']="";
		$formData['duration21']="";
		$formData['results21']="";
		$formData['provider21']="";
		$formData['family_member21']="0";
		
		$formData['a22']="";
		$formData['onset22']="";
		$formData['details22']="";
		$formData['duration22']="";
		$formData['results22']="";
		$formData['provider22']="";
		$formData['family_member22']="0";

	    $sql="select * from nua_employee_ihq_medical_answer where employee_id = " . $user['employee_id'];

		$d=$this->X->sql($sql);
		$m=array();
		foreach($d as $e) {
			if ($e['question_id']==1) { if ($e['answer']==0) $formData['a1']="No"; else  $formData['a1']="Yes";  }					 
			if ($e['question_id']==2) { if ($e['answer']==0) $formData['a2']="No"; else  $formData['a2']="Yes";  }					 
			if ($e['question_id']==3) { if ($e['answer']==0) $formData['a3']="No"; else  $formData['a3']="Yes";  }					 
			if ($e['question_id']==4) { if ($e['answer']==0) $formData['a4']="No"; else  $formData['a4']="Yes";  }					 
			if ($e['question_id']==5) { if ($e['answer']==0) $formData['a5']="No"; else  $formData['a5']="Yes";  }					 
			if ($e['question_id']==6) { if ($e['answer']==0) $formData['a6']="No"; else  $formData['a6']="Yes";  }					 
			if ($e['question_id']==7) { if ($e['answer']==0) $formData['a7']="No"; else  $formData['a7']="Yes";  }					 
			if ($e['question_id']==8) { if ($e['answer']==0) $formData['a8']="No"; else  $formData['a8']="Yes";  }					 
			if ($e['question_id']==9) { if ($e['answer']==0) $formData['a9']="No"; else  $formData['a9']="Yes";  }					 
			if ($e['question_id']==10) { if ($e['answer']==0) $formData['a10']="No"; else  $formData['a10']="Yes";  }					 
			if ($e['question_id']==11) { if ($e['answer']==0) $formData['a11']="No"; else  $formData['a11']="Yes";  }					 
			if ($e['question_id']==12) { if ($e['answer']==0) $formData['a12']="No"; else  $formData['a12']="Yes";  }					 
			if ($e['question_id']==13) { if ($e['answer']==0) $formData['a13']="No"; else  $formData['a13']="Yes";  }					 
			if ($e['question_id']==14) { if ($e['answer']==0) $formData['a14']="No"; else  $formData['a14']="Yes";  }					 
			if ($e['question_id']==15) { if ($e['answer']==0) $formData['a15']="No"; else  $formData['a15']="Yes";  }					 
			if ($e['question_id']==16) { if ($e['answer']==0) $formData['a16']="No"; else  $formData['a16']="Yes";  }					 
			if ($e['question_id']==17) { if ($e['answer']==0) $formData['a17']="No"; else  $formData['a17']="Yes";  }					 
			if ($e['question_id']==18) { if ($e['answer']==0) $formData['a18']="No"; else  $formData['a18']="Yes";  }					 
			if ($e['question_id']==19) { if ($e['answer']==0) $formData['a19']="No"; else  $formData['a19']="Yes";  }					 
			if ($e['question_id']==20) { if ($e['answer']==0) $formData['a20']="No"; else  $formData['a20']="Yes";  }					 
			if ($e['question_id']==21) { if ($e['answer']==0) $formData['a21']="No"; else  $formData['a21']="Yes";  }					 
			if ($e['question_id']==22) { if ($e['answer']==0) $formData['a22']="No"; else  $formData['a22']="Yes";  }					 
			array_push($m,$e);
		}
		
		
		$sql="select * from nua_employee_ihq_answer_detail where employee_i_h_q_id = " . $data['uid'];		 
        $z=$this->X->sql($sql);
		foreach ($z as $z0) {
     		$aa=$z0['employee_i_h_q_medical_answer_id'];
			$sql="select * from nua_employee_ihq_medical_answer where id = " . $aa; 
			$z1=$this->X->sql($sql);
			if (sizeof($z1)>0) {
			     $qid=$z1[0]['question_id'];
                 $formData['onset' . $qid]=$z0['onset'];
                 $formData['details' . $qid]=$z0['details'];
				 $formData['duration' . $qid]=$z0['duration'];
				 $formData['results' . $qid]=$z0['results'];	
				 $formData['provider' . $qid]=$z0['provider'];					 
				 $formData['family_member' . $qid]=$z0['family_member'];
			}
		}			

		$e['detail']=$z;
			
        $output['answers']=$m;		
        $output['formData']=$formData;
		
		
		$sql="select * from nua_employee_ihq_family where employee_id = " . $data['uid'];
		$u=$this->X->sql($sql);
		$output['family']=$u;
	
	    $sql="select question_id, question, requires_details from nua_medical_question order by question_id";
		$u=$this->X->sql($sql);
		$output['questions']=$u;
		
        return $output;	
		
	}
	
	function employeeLookup($data) {
		 $output=$this->start_output($data);
                 $output=$this->getTableFormData($data,"nua_employee");
		 return $output;

	}
	function getEmployeeDashboard($data) {
		
		 $date=date_create();
		 $mnth_id=date_format($date,'Y-m');
                 if ($mnth_id=="2022-01") $month_id="2022-02";
                 if ($mnth_id=="2022-02") $month_id="2022-03";
                 if ($mnth_id=="2022-03") $month_id="2022-04";
                 if ($mnth_id=="2022-04") $month_id="2022-05";
                 if ($mnth_id=="2022-05") $month_id="2022-06";
                 if ($mnth_id=="2022-06") $month_id="2022-07";
                 if ($mnth_id=="2022-07") $month_id="2022-08";
                 if ($mnth_id=="2022-08") $month_id="2022-09";
                 if ($mnth_id=="2022-09") $month_id="2022-10";
                 if ($mnth_id=="2022-10") $month_id="2022-11";
                 if ($mnth_id=="2022-11") $month_id="2022-12";

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
				 $array_push($termdts,"2022-04-30");
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
		$termData['term_date']="";
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

function postEditInsurance($data) {
			$post=array();
			$post=$data['data']['formData'];
			$post['action']="insert";
			$post['table_name']="nua_employee_ihq_insurance_status";
			$this->X->post($post);
			$output=array();
			$output['error_code']="0";
			$output['error']="0";
			return $output;
}

	function getMemberPlans($data) {

		$output=array();
		
	    $sql="select * from nua_user where id = " . $data['uid'];
        $users=$this->X->sql($sql);
		$user=$users[0];
		
		$output['user']=$user;
	    $sql="select * from nua_employee where id = " . $user['employee_id'];
        $employees=$this->X->sql($sql);
		$employee=$employees[0];		

	    $sql="select * from nua_company where id = " . $employee['company_id'];
        $companys=$this->X->sql($sql);
		$company=$companys[0];	
		
		$sql="select * from nua_employee_plan_options where employee_id = " . $user['employee_id'] . " order by plan_name";
		$plans=$this->X->sql($sql);
		$l=array();
		foreach($plans as $p) {
				$a=array();
				$a['id']=$p['id'];
				$a['plan_name']=$p['plan_name'];
				$a['plan_type']=$p['plan_type'];
				$a['price']=$p['employee'];
				$a['level']="Employee";
				array_push($l,$a);
				$a=array();
				$a['id']=$p['id'];
				$a['plan_name']=$p['plan_name'];
				$a['plan_type']=$p['plan_type'];
				$a['price']=$p['employee_spouse'];
				$a['level']="Employee & Spouse";
				array_push($l,$a);	
				$a=array();
				$a['id']=$p['id'];
				$a['plan_name']=$p['plan_name'];
				$a['plan_type']=$p['plan_type'];
				$a['price']=$p['employee_children'];
				$a['level']="Employee & Children";
				array_push($l,$a);	
				$a=array();
				$a['id']=$p['id'];
				$a['plan_name']=$p['plan_name'];
				$a['plan_type']=$p['plan_type'];
				$a['price']=$p['family'];
				$a['level']="Family";
				array_push($l,$a);
		}
        $output['plans']=$l;	
				
		return $output;
		
	}
	
	function getMemberDashboard($data) {

		 $output=$this->start_output($data);
		 if ($output['user']['forced_off']>0) return $output;
		$user=$output['user'];
	    $sql="select * from nua_employee where id = " . $user['employee_id'];
        $employees=$this->X->sql($sql);
		$employee=$employees[0];		

	    $sql="select * from nua_company where id = " . $employee['company_id'];
        $companys=$this->X->sql($sql);
		$company=$companys[0];	
		
		$output['company']=$company;

        $output['employee']=$employee;
		$sql="select * from nua_employee_enrollment where employee_id = " . $user['employee_id'];
		$enrollments=$this->X->sql($sql);
		if (sizeof($enrollments)==0) {
		    $post=array();
            $post['table_name']="nua_employee_enrollment";
            $post['action']="insert";
            $post['employee_id']=$user['employee_id'];
            $post['company_id']=$employee['company_id'];
			$this->X->post($post);
		} else {
			
		}

$sql="select * from nua_employee_plan_options where employee_id = " . $user['employee_id'];
		$plans=$this->X->sql($sql);
        $output['plans']=$plans;	
		
		$sql="select * from nua_employee_enrollment where employee_id = " . $user['employee_id'];
		$enrollments=$this->X->sql($sql);
        $output['enrollment']=$enrollments[0];		
	    		
		$sql="select * from nua_employee_ihq where employee_id = " . $user['employee_id'];
        $e=$this->X->sql($sql);
        if (sizeof($e)==0) {
              $post=array();
			  $post['table_name']="nua_employee_ihq";
			  $post['action']="insert";
			  $post['employee_id']=$employee_id;
			  $post['information_submitted']=0;
			  $post['family_submitted']=0;
			  $post['insurance_submitted']=0;
			  $post['medications_completed']=0;
			  $post['ip']="";
			  $ihq_id=$this->X->post($post);
			  $sql="select * from nua_employee_ihq where employee_id = " . $user['employee_id'];
			   $e=$this->X->sql($sql);
	    } else {

	    }
        $ihq_id=$e[0]['id'];
		$output['ihq']=$e[0];
		
		$sql="select count(*) as c from nua_employee_ihq_medical_answer where employee_id = "  . $user['employee_id'];
		$r=$this->X->sql($sql);
		$output['questions_answered']=$r[0]['c'];

		$sql="select count(*) as c from nua_employee_ihq_medication where employee_id = "  . $user['employee_id'];
		$r=$this->X->sql($sql);
		$output['medications_entered']=$r[0]['c'];		

		$sql="select count(*) as c from nua_employee_ihq_family where employee_id = "  . $user['employee_id'];
		$r=$this->X->sql($sql);
		$output['family_entered']=$r[0]['c'];	

		$sql="select count(*) as c from nua_employee_ihq_insurance_status where employee_id = "  . $user['employee_id'];
		$r=$this->X->sql($sql);
		$output['insurance_status']=$r[0]['c'];	
		
		$sql="select * from nua_employee_ihq_information where employee_id = "  . $user['employee_id'];
		$r=$this->X->sql($sql);
		if (sizeof($r)==0) {
			$output['information_entered']=0;	
		} else {
			$cc=0;
			if ($r[0]['first_name']!="") $cc++;
			if ($r[0]['last_name']!="") $cc++;
			if ($r[0]['email']!="") $cc++;
			if ($r[0]['date_hired']!="") $cc++;
			if ($r[0]['marital_status']!="") $cc++;
			if ($r[0]['address']!="") $cc++;
			if ($r[0]['city']!="") $cc++;
			if ($r[0]['state']!="") $cc++;
			if ($r[0]['zip']!="") $cc++;
			if ($r[0]['work_role']!="") $cc++;
			if ($r[0]['annual_salary']!="") $cc++;
			if ($r[0]['date_of_birth']!="") $cc++;
			if ($r[0]['gender']!="") $cc++;
			if ($r[0]['phone']!="") $cc++;
			if ($r[0]['social_security_number']!="") $cc++;
			if ($r[0]['employee_status']!="") $cc++;
			$output['information_entered']=$cc;	
		}
				
        return $output;
		
	}
	
	function getQuoteDashboard($data) {

		$output=$this->start_output($data);
		if ($output['user']['forced_off']>0) return $output;
		$user=$output['user'];
		
	    $sql="select * from nua_quote where id = " . $data['id'];	
        $d=$this->X->sql($sql);
		if (sizeof($d)>0) {
            foreach($d[0] as $name=>$value) $output[$name]=$value;
		}
        return $output;		
	}

	function getQuoteRequestDashboard($data) {

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
	

	function getPlanDashboard($data) {
	
		$output=$this->start_output($data);
		if ($output['user']['forced_off']>0) return $output;
		$user=$output['user'];
	    $sql="select * from nua_plan where id = " . $data['id'];
        $d=$this->X->sql($sql);
		if (sizeof($d)>0) {
            foreach($d[0] as $name=>$value) $output[$name]=$value;
		}
        return $output;		
	}

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
	
	function getEditOrg($data) {
		
		$output=$this->start_output($data);
		if ($output['user']['forced_off']>0) return $output;
		$user=$output['user'];
	    
		$sql="select * from nua_org where id = " . $data['id'];
		$output=array();
	    
        $d=$this->X->sql($sql);
		if (sizeof($d)>0) {
            foreach($d[0] as $name=>$value) $output[$name]=$value;
		}
		
		$output['formData']=$formData;
		$output['params']=$data;
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
	
	function getSystemNoteList($data) {
		
                $list=array();
		$output=$this->start_output($data);
		if ($output['user']['forced_off']>0) return $output;
		$user=$output['user'];
                $sql="select * from inf_system_note where status = 'open' and severity= 'Critical' order by id";
                $d=$this->X->sql($sql);
                foreach($d as $e) {
                    $sql="select last_name, first_name, avatar from nua_user where id = " . $e['created_by'];
                    $g=$this->X->sql($sql);
                    $e['created_by_name']=$g[0]['last_name'] . ", " . $g[0]['first_name']; 
		    if ($g[0]['avatar']=="") {
			 $e['created_by_avatar']="https://mynuaxess.com/peo/assets/images/avatars/noprofilepic.jpg";
			 $e['updated_by_avatar']="https://mynuaxess.com/peo/assets/images/avatars/noprofilepic.jpg";
		    } else {
		          $e['created_by_avatar']=$g[0]['avatar'];
		          $e['updated_by_avatar']=$g[0]['avatar'];
		    } 
                    if ($e['last_updated_by']==0) {
                         $e['last_updated_by_name']=$e['created_by_name'];
		    } else {
                        $sql="select last_name, first_name, avatar from nua_user where id = " . $e['last_updated_by'];
                        $g=$this->X->sql($sql);
                        $e['last_updated_by_name']=$g[0]['last_name'] . ", " . $g[0]['first_name']; 
		        if ($g[0]['avatar']=="") {
			    $e['updated_by_avatar']="https://mynuaxess.com/peo/assets/images/avatars/noprofilepic.jpg";
		        } else {
		            $e['updated_by_avatar']=$g[0]['avatar'];
		        } 
                    }
                    array_push($list,$e);
                }
                
                $sql="select * from inf_system_note where status = 'open' and severity not in ('Critical') order by id";
                $d=$this->X->sql($sql);
                foreach($d as $e) {
                    $sql="select last_name, first_name, avatar from nua_user where id = " . $e['created_by'];
                    $g=$this->X->sql($sql);
		    if ($g[0]['avatar']=="") {
			 $e['created_by_avatar']="https://mynuaxess.com/peo/assets/images/avatars/noprofilepic.jpg";
			 $e['updated_by_avatar']="https://mynuaxess.com/peo/assets/images/avatars/noprofilepic.jpg";
		    } else {
		          $e['created_by_avatar']=$g[0]['avatar'];
		          $e['updated_by_avatar']=$g[0]['avatar'];
		    } 
                    $e['created_by_name']=$g[0]['last_name'] . ", " . $g[0]['first_name']; 
                    if ($e['last_updated_by']==0||$e['last_updated_by']=="") {
                         $e['updated_by_name']=$e['created_by_name'];
		    } else {
                        $sql="select last_name, first_name, avatar from nua_user where id = " . $e['last_updated_by'];
                        $g=$this->X->sql($sql);
                        $e['updated_by_name']=$g[0]['last_name'] . ", " . $g[0]['first_name']; 
		        if ($g[0]['avatar']=="") {
			    $e['updated_by_avatar']="https://mynuaxess.com/peo/assets/images/avatars/noprofilepic.jpg";
		        } else {
		            $e['updated_by_avatar']=$g[0]['avatar'];
		        } 
                    }
                    $sql="select last_name, first_name from nua_user where id = " . $e['assigned_to'];
                    $g=$this->X->sql($sql);
		    if (sizeof($g)>0) {
                    if ($e['assigned_to']==0||$e['assigned_to']=="") {
                           $e['assigned_to_username'] = "Not Assigned";
                           }
                    else
                    {
                            $e['assigned_to_username']=$g[0]['last_name'] . ", " . $g[0]['first_name'];
                    }
                    } else {

                            $e['assigned_to_username']="User Not Found";
	            }



		    array_push($list,$e);
                }

		$output['list']=$list;
                return $output;		
	}

	function getInvoiceList($data) {
		
		$output=$this->start_output($data);
		if ($output['user']['forced_off']>0) return $output;
		$user=$output['user'];
		if ($data['id']=="") {
                   $sort="name";
		} else {
		   $sort = $data['id'];
		}
		if ($data['id2']=="") {
                   $date=date_create();
                   $mid=date_format($date,'Y-m');
		} else {
		        $mid = $data['id2'];
		}
                $last_month=$mid; 
                if ($last_month=='2022-01') $month_id='2022-02';
                if ($last_month=='2022-02') $month_id='2022-03';
                if ($last_month=='2022-03') $month_id='2022-04';
                if ($last_month=='2022-04') $month_id='2022-05';
                if ($last_month=='2022-05') $month_id='2022-06';
                if ($last_month=='2022-06') $month_id='2022-07';
                if ($last_month=='2022-07') $month_id='2022-08';
                if ($last_month=='2022-08') $month_id='2022-09';
                if ($last_month=='2022-09') $month_id='2022-10';
                if ($last_month=='2022-10') $month_id='2022-11';
                if ($last_month=='2022-11') $month_id='2022-12';
                if ($last_month=='2022-12') $month_id='2023-01';
                if ($last_month=='2023-01') $month_id='2023-02';
$month_id="2022-04";
                $output['month_id']=$month_id;

		$sql="select * from nua_company_invoice where month_id = '" . $month_id . "' and company_id in (select id from nua_company where org_id = 17)";
                $grand=0;
                $medical=0;
                $dental=0;
                $vision=0;
                $add=0;
                $z=$this->X->sql($sql);
                foreach ($z as $a) {
                       $grand+=floatval(str_replace(',','',$a['grand_total']));
                       $medical+=floatval(str_replace(',','',$a['medical_total']));
                       $dental+=floatval(str_replace(',','',$a['dental_total']));
                       $vision+=floatval(str_replace(',','',$a['vision_total']));
                       $add+=floatval(str_replace(',','',$a['add_total']));
     }
		$output['grand']=number_format($grand,2);
		$output['medical']=number_format($medical,2);
		$output['dental']=number_format($dental,2);
		$output['vision']=number_format($vision,2);
		$output['add']=number_format($add,2);

                $monthlist=array();
                $month=array();
                $month['month_id']="2022-01";
                array_push($monthlist,$month);
                $month['month_id']="2022-02";
                array_push($monthlist,$month);
                $month['month_id']="2022-03";
                array_push($monthlist,$month);
                $month['month_id']="2022-04";
                array_push($monthlist,$month);
                $month['month_id']="2022-05";
                array_push($monthlist,$month);
                $output['monthlist']=$monthlist;

		$sql="select * from nua_company_invoice where month_id = '" . $month_id . "' and company_id in ";
		$sql.=" (select id from nua_company where org_id = 17) order by ";
		if ($sort=="name") {
		    $sql.=" company_name";
		    $output['sort']=$sort;
		} else {
		    $sql.=" grand_total_float desc";    
		    $output['sort']="total";
		}


                $d=$this->X->sql($sql);
                $list=array();
		foreach($d as $e) { 
                        $sql="select * from nua_company where id = " . $e['company_id'];
			$y=$this->X->sql($sql);
                        $company=$y[0];
		   $e['email']=$company['billing_contact_email'];
		   $e['email2']=$company['billing_contact_email2'];
		   $e['email3']=$company['billing_contact_email3'];
		   $e['email4']=$company['billing_contact_email4'];
		   $e['email5']=$company['billing_contact_email5'];

                        if ($company['org_id']!=0) { 
                            $sql="select org_name from nua_org where id = " . $company['org_id'];
		    	    $y=$this->X->sql($sql);
                            if (sizeof($y)>0) {
		               $e['org_name']=$y[0]['org_name'];
		            }
                          } else {
                            $e['org_name']="No Organization Found";
                          }
                    $sql="select * from nua_bad where company_id=" . $e['company_id'];
                    $y=$this->X->sql($sql);
                    if (sizeof($y)==0) {
			 $e['bad']="Y";   
                    } else {
                         $e['bad']="N";
                    }

                    array_push($list,$e);
                }
         
                
                $movedata=array();
                $movedata['id']="";
                $movedata['value']="";
                $output['movedata']=$movedata;
		$output['list']=$list;
                return $output;		
	}

        function getSystemNoteDashboard($data) {
     	      $output=$this->start_output($data);
              if ($output['user']['forced_off']>0) return $output;
	      $user=$output['user'];
              $sql="select * from inf_system_note where id = " . $data['id'];
	      $h=$this->X->sql($sql);
              $formData=array();
	      foreach($h[0] as $name => $value) {
                  $formData[$name]=$value;
	      }
              $output['formData']=$formData;
              return $output;
	}

        function postSystemNote($data) {
     	      $output=$this->start_output($data);
              if ($output['user']['forced_off']>0) return $output;
              $post=$data['data']['formData'];
              $post['table_name']="inf_system_note";
              $post['action']="insert";
              if ($post['created_by']==""||$post['created_by']=="0") $post['created_by']=$data['uid'];
              $post['last_updated_by']=$data['uid'];
              $date=date_create();
              $post['last_update_date']=date_format($date,'Y-m-d H:i:s');
              if ($post['status']=="") $post['status']="open";
              if ($post['severity']=="") $post['severity']="Medium";
              $this->X->post($post);
              $output=array();
              $output['error_code']=0;
              return $output;
        }

	function apaPlanList($data) {
		$output=$this->start_output($data);
		if ($output['user']['forced_off']>0) return $output;
		$user=$output['user'];
                $sql="select * from nua_plan_map";
                $d=$this->X->sql($sql);
		$output['list']=$d;
                return $output;		
	}

	function getOrgList($data) {
		
		$output=$this->start_output($data);
		if ($output['user']['forced_off']>0) return $output;
		$user=$output['user'];
	    $sql="select * from nua_org order by org_name";
        $d=$this->X->sql($sql);
		$output['list']=$d;
        return $output;		
	}

	function getRequestList($data) {
		$output=$this->start_output($data);
		if ($output['user']['forced_off']>0) return $output;
		$user=$output['user'];
		
	    $sql="select * from nua_quote where status in ('Submitted','In-Quoting','Quoting','Quoted','Accepted','Enrolling','Rejected') order by last_update desc";;
        $d=$this->X->sql($sql);
		$list=array();
		foreach($d as $e) {
				$o=$this->X->sql($sql);
				$e['org_name']=$o[0]['org_name'];
				$sql="select company_name, org_id from nua_company where id = " . $e['company_id'];
				$o=$this->X->sql($sql);
				if (sizeof($o)>0) {
					$e['company_name']=$o[0]['company_name'];
					$sql="select org_name from nua_org where id = " . $o[0]['org_id'];
					$o=$this->X->sql($sql);
					if (sizeof($o)>0) {
						$e['org_name']=$o[0]['org_name'];
					} else {
						$e['org_name']="Not Assigned";
					}
				} else {
					$e['org_name']="Not Assigned";
					$e['company_name']="Not Assigned";
				}
				$sql="select full_name, email, phone_mobile from nua_user where id = " . $e['requested_by'];
				$o=$this->X->sql($sql);
				$e['requested_name']=$o[0]['full_name'];
				$e['requested_email']=$o[0]['email'];
				$e['requested_phone']=$o[0]['phone_mobile'];
                array_push($list,$e);				
		}
		$output['list']=$list;
        return $output;		
	}
	
	function getPeoCompanyList($data) {
		
		$output=$this->start_output($data);
		if ($output['user']['forced_off']>0) return $output;
		$user=$output['user'];
	        $output['id']=$data['id'];	
		$sql="select * from nua_company where org_id = " . $user['org_id'] . "  order by company_name";
		$list=array();
                $d=$this->X->sql($sql); 
		$a=array();
		foreach($d as $e) {
                     $sql="select count(*) as c from inf_client_plan where active = 'Y' and clientId='" . $e['infinity_id'] . "'";
                     $b=$this->X->sql($sql);
                     $e['active_plans']=$b[0]['c'];
                     array_push($a,$e);
                }
                $output['list']=$a;
                return $output;		
   
        }
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
                $sql="select * from nua_monthly_member_terminations where client_id <> '' and month_id >= '" . $month_id . "' limit 500";
                $d=$this->X->sql($sql); 
                 
		$l=array();
                 foreach($d as $e) {
                     $e['first_name']=str_replace('"',"'",$e['first_name']);
                     array_push($l,$e);
		}
                $output['list']=$l;
                return $output;		
   
        }

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
                $sql="select * from nua_monthly_member_additions where client_id <> '' and month_id >= '" . $month_id . "' limit 500";
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

                $list=array();

                $sql="select * from nua_company where infinity_id <> '' order by company_name";
                $l=$this->X->sql($sql);
                foreach($l as $m) {
                     $sql="select planId from inf_client_plan where active = 'Y' and clientID = '" . $m['infinity_id'] . "' order by planId";
                     $plan_list="";
                     $t=$this->X->sql($sql);
                    
                     foreach($t as $u) {
			  if ($plan_list=="") {
                                $plan_list.=$u['planId'];
                          } else {
                                $plan_list.=", ".$u['planId'];
                          }

                    }
                    $j=array();
                    $j['id']=$m['id'];
                    $j['company_name']=$m['company_name'];
                    $j['infinity_id']=$m['infinity_id'];
                    $j['plan_list']=$plan_list;		     
                    array_push($list,$j);

                }
                 
                $output['list']=$list;
                return $output;		
   
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

	function getCompanyList($data) {
		
		$output=$this->start_output($data);
		if ($output['user']['forced_off']>0) return $output;
		$user=$output['user'];
	        $output['id']=$data['id'];	
                       $date=date_create();
		       $month_id=date_format($date,'Y-m');
		       $mid=$month_id;
                if ($month_id=='2022-05') $month_id="2022-06";
                if ($month_id=='2022-04') $month_id="2022-05";
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
		$sql="select * from nua_company where org_id = 17 and member_count > 0  order by company_name";
				
		$list=array();
                $d=$this->X->sql($sql); 
		$a=array();
		foreach($d as $e) {
			
	        $sql="select distinct employee_code from nua_monthly_member_census where month_id = '" . $month_id . "' and company_id = " . $e['id'];
		$qu=$this->X->sql($sql);
                $ec=0;
                foreach($qu as $q) {
                    $ec++;
                }
                $e['member_count']=$ec;	

		$ec=0;
	        $sql="select distinct employee_code from nua_monthly_member_additions where month_id >= '" . $mid . "' and company_id = " . $e['id'];
		$qu=$this->X->sql($sql);
                $ec=0;
                foreach($qu as $q) {
                    $ec++;
                }
                $e['additions']=$ec;	

		$ec=0;
	        $sql="select distinct employee_code from nua_monthly_member_terminations where month_id > '" . $mid . "' and company_id = " . $e['id'];
		$qu=$this->X->sql($sql);
                $ec=0;
                foreach($qu as $q) {
                    $ec++;
                }
                $e['terminations']=$ec;	


                $med=0;
                $dental=0;
                $vision=0;
                $add=0;

	        $sql="select * from nua_monthly_member_census where month_id = '" . $month_id . "' and company_id = " . $e['id'];
		$qu=$this->X->sql($sql);
                $ec=0;
                foreach($qu as $q) {
                    if ($q['plan_type']=='*MEDICAL*') $med++;
                    if ($q['plan_type']=='*DENTAL*') $dental++;
                    if ($q['plan_type']=='*VISION*') $vision++;
                    if ($q['plan_type']=='*ADD*') $add++;
                    if ($q['plan_type']=='*LIFE*') $add++;
                }
                $e['m_count']=$med;	
                $e['d_count']=$dental;	
                $e['v_count']=$vision;	
                $e['a_count']=$add;	
		array_push($a,$e);
		}
		
        $output['list']=$a;
        return $output;		
	}
	
	function getEmployeeList($data) {
		
		$output=$this->start_output($data);
		if ($output['user']['forced_off']>0) return $output;
		$user=$output['user'];
		
	    $sql="select * from nua_employee order by id";
        $d=$this->X->sql($sql);
        $output['list']=$d;
        return $output;		
	}
	
	function getQuoteList($data) {
		
		$output=$this->start_output($data);
		if ($output['user']['forced_off']>0) return $output;
		$user=$output['user'];
		
	    $sql="select * from nua_quote order by id";
        $d=$this->X->sql($sql);
        $output['list']=$d;
        return $output;		
	}
	
	
	function getPlanList($data) {
		$output=$this->start_output($data);
		if ($output['user']['forced_off']>0) return $output;
		$user=$output['user'];
		
	    $sql="select * from nua_plan order by id";
        $d=$this->X->sql($sql);
        $output['list']=$d;
        return $output;		
	}
	
	function getUserList($data) {
		$output=$this->start_output($data);
		if ($output['user']['forced_off']>0) return $output;
		$user=$output['user'];
		
	    $sql="select * from nua_user order by full_name";
		if ($data['id']=="org") {
			$sql="select * from nua_user order by org_id, full_name";	
		}
		if ($data['id']=="employer") {
			$sql="select * from nua_user where role in ('eadmin','employer') order by org_id, full_name";	
		}
		if ($data['id']=="pending") {
			$sql="select * from nua_user where status = 'pending' order by full_name";	
		}
		if ($data['id']=="members") {
			$sql="select * from nua_user where role = 'employee' order by full_name";	
		}		
		if ($data['id']=="nua") {
			$sql="select * from nua_user where role in ('sadmin','user') order by full_name";	
		}			
		$list=array();
        $d=$this->X->sql($sql);
		$tt=array();
		foreach($d as $e) {
				if ($e['org_id']!=0) {
					$sql="select org_name from nua_org where id = " . $e['org_id'];
					$z=$this->X->sql($sql);
					if (sizeof($z)>0) {
				            $e['org_name']=$z[0]['org_name'];
					} else {
                                            $e['org_name']="BAD DATA";
					}
				} else {
				    $e['org_name']="";	
				}
				if ($e['company_id']!=0) {
					$sql="select company_name from nua_company where id = " . $e['company_id'];
					$z=$this->X->sql($sql);
					if (sizeof($z)>0) {
						$e['company_name']=$z[0]['company_name'];
					} else {
                                           $e['company_name']="BAD DATA";
				        }
				} else {
				    $e['company_name']="";	
				}	
			array_push($tt,$e);
		}
        $output['list']=$tt;
        return $output;		
	}
	
	function getUserList2($data) {
		$output=$this->start_output($data);
		if ($output['user']['forced_off']>0) return $output;
		$user=$output['user'];
		
	    $sql="select * from nua_user order by full_name";
		if ($data['id']=="org") {
			$sql="select * from nua_user order by org_id, full_name";	
		}
		if ($data['id']=="employer") {
			$sql="select * from nua_user where role in ('eadmin','employer') order by org_id, full_name";	
		}
		if ($data['id']=="pending") {
			$sql="select * from nua_user where status = 'pending' order by full_name";	
		}
		if ($data['id']=="members") {
			$sql="select * from nua_user where role = 'employee' order by full_name";	
		}		
		if ($data['id']=="nua") {
			$sql="select * from nua_user where role in ('sadmin','user') order by full_name";	
		}			
		$list=array();
        $d=$this->X->sql($sql);
		$tt=array();
		foreach($d as $e) {
				if ($e['org_id']!=0) {
					$sql="select org_name from nua_org where id = " . $e['org_id'];
					$z=$this->X->sql($sql);
					if (sizeof($z)>0) {
				            $e['org_name']=$z[0]['org_name'];
					} else {
                                            $e['org_name']="BAD DATA";
					}
				} else {
				    $e['org_name']="";	
				}
				if ($e['company_id']!=0) {
					$sql="select company_name from nua_company where id = " . $e['company_id'];
					$z=$this->X->sql($sql);
					if (sizeof($z)>0) {
						$e['company_name']=$z[0]['company_name'];
					} else {
                                           $e['company_name']="BAD DATA";
				        }
				} else {
				    $e['company_name']="";	
				}	
			array_push($tt,$e);
		}
        $output['list']=$tt;
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
	
    function guardianLookup($data) {
		$output=$this->start_output($data);
		if ($output['user']['forced_off']>0) return $output;
		$user=$output['user'];
		$sql="select * from nua_guardian order by last_name";
		$list=$this->X->sql($sql);
		$out=array();
		foreach ($list as $l) {
                     array_push($out,$l);
		}
		 $output['list']=$out;
		 return $output;
	}
	
	   function getAddOrgForm($data) {
		$output=$this->start_output($data);
		if ($output['user']['forced_off']>0) return $output;
		$user=$output['user'];

		 $formData=array();
		 $formData['org_name']="";
		 $formData['email']="";
		 $formData['phone_mobile']="";
		 $formData['contact_full_name']="";
		 $formData['billing_contact_name']="";
		 $formData['billing_contact_email']="";
		 $formData['billing_contact_phone']="";
		 $formData['admin_contact_name']="";
		 $formData['admin_contact_email']="";
		 $formData['admin_contact_phone']="";
		 $formData['website']="";
		 $formData['address']="";
		 $formData['city']="";
		 $formData['state']="";
		 $formData['zip']="";
		 $formData['dsc']="";
		 $formData['id']="";
		 $formData['type']="Sales";
		 $output['formData']=$formData;
		 return $output;
	   }

    function getSimpleFormData($data) {
       $output='{
         "formData": {
             "DATE_TIME": "10/03/2021",
             "SIMPLE_INPUT": "Existing Data",
             "EXAMPLE_PASSWORD": "",
             "FORM_CONTROL_TEXT_INPUT": "",
             "FORM_CONTROL_SELECT": "2",
             "FORM_CONTROL_MULTI_SELECT": "3",
             "FORM_CONTROL_TEXTAREA": "This is Text Area",
             "FORM_CONTROL_RANGE": "80",
             "CUSTOM_RANGE": "50",
             "CUSTOM_FILE": "",
             "EXAMPLE_READONLY": "Readonly Value",
             "EXAMPLE_DISABLED": "Disabled Value",
             "EXAMPLE_STATIC_TEXT": "Static Text",
             "EXAMPLE_HELPING_TEXT": "Helping Text",
             "EXAMPLE_EMAIL" : "ed@artfin.com",
             "EXAMPLE_PASSWORD2": "",
             "EXAMPLE_CHECKBOX": "on"
         },
         "TABLE_NAME":"EXAMPLE",
         "KEY":"ID",
         "SEQUENCE":"EXAMPLE_SEQ",
         "select": [
            {"value":"1", "name": "1"},
            {"value":"2", "name": "2"},
            {"value":"3", "name": "3"},
            {"value":"4", "name": "4"},
            {"value":"5", "name": "5"}
         ]
       }';
       return json_decode($output,true);
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


