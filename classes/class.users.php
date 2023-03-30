<?php

require_once('/var/www/classes/class.XRDB.php');

class USERS {

    public $X;

    function __construct() {
         $this->X=new XRDB();
    }

    function getAddUser($data) {
		$sql="select id, org_name from nua_org where status = 'active' order by org_name";
		$z=$this->X->sql($sql);
		$select=array();
		foreach($z as $z0) {
		    $a=array();
            $a['value']=$z0['id'];
            $a['name']=$z0['org_name'];			
            array_push($select,$a);
		}
       $output='{
         "formData": {
             "email": "",
             "phone_mobile": "",
			 "user_name": "",
			 "full_name": "",
             "role": "",
             "org_type": "",
             "org_id": "",
			 "new_org_name": "",
			 "new_org_email": "",
			 "new_org_phone": ""
         },
         "TABLE_NAME":"nua_user",
         "KEY":"id",
         "SEQUENCE":"",
         "select": [
            {"value":"sadmin", "name": "Super Admin"},
            {"value":"badmin", "name": "Organization Admin"},
            {"value":"eadmin", "name": "Employer Admin"},
            {"value":"broker", "name": "Broker"},
            {"value":"user", "name": "NuAxess User"},
            {"value":"employee", "name": "Employer"}
         ]
       }';
	   $arr=json_decode($output,true);
	   $arr['select']=$select;
       return $arr;
    }

    function getUserDashboard($data) {
		$sql="select id, org_name from nua_org where status = 'active' order by org_name";
		$z=$this->X->sql($sql);
		$select=array();
		foreach($z as $z0) {
		    $a=array();
            $a['value']=$z0['id'];
            $a['name']=$z0['org_name'];			
            array_push($select,$a);
		}
		$sql="select * from nua_user where id = " . $data['id'];
		$z=$this->X->sql($sql);
		$select=array();
		$formData=array();
		foreach($z as $name => $value) {
		    $formData[$name]=$value;
		}
		
       $output='{
         "formData": {
             "email": "",
             "phone_mobile": "",
			 "user_name": "",
			 "full_name": "",
             "role": "",
             "org_type": "",
             "org_id": "",
			 "new_org_name": "",
			 "new_org_email": "",
			 "new_org_phone": ""
         },
         "TABLE_NAME":"nua_user",
         "KEY":"id",
         "SEQUENCE":"",
         "select": [
            {"value":"sadmin", "name": "Super Admin"},
            {"value":"badmin", "name": "Organization Admin"},
            {"value":"eadmin", "name": "Employer Admin"},
            {"value":"broker", "name": "Broker"},
            {"value":"user", "name": "NuAxess User"},
            {"value":"employee", "name": "Employer"}
         ]
       }';
	   $org=array();
	   if ($z[0]['org_id']!=0) {
		   $sql="select * from nua_org where id = " . $z[0]['org_id'];
           $orgs=$this->X->sql($sql);
           if (sizeof($orgs)>0) {
                $org=$orgs[0];
           } else {
			    $org=array();
                $org['org_name']="";
                $org['dsc']="";				
		   }
	   }
	   
	   $sql="select * from nua_company where status = 'prospect' and broker_id = " . $data['id'];
	   $pro=$this->X->sql($sql);
	   
	   $arr=json_decode($output,true);
	   $arr['select']=$select;
	   $arr['formData']=$formData;
	   $arr['user']=$z[0];
	   $arr['org']=$org;
	   $arr['prospects']=$pro;
       return $arr;
    }
	
	function postAddUser($data,$role) {
		
		  $error_code=0;
		  $error_message="";	  
	      $email=strtolower($data['data']['formData']['email']);
		  $full_name=strtolower($data['data']['formData']['full_name']);
		  $phone_mobile=strtolower($data['data']['formData']['phone_mobile']);		  
	      $user_name=strtolower($data['data']['formData']['user_name']);
		  $org_id=strtolower($data['data']['formData']['org_id']);
		  $new_org_name=$data['data']['formData']['new_org_name'];
          $new_org_email=strtolower($data['data']['formData']['new_org_email']);
		  $new_org_phone=strtolower($data['data']['formData']['new_org_phone']);

		  
		  $sql="select count(*) as C from nua_user where email = '" . strtolower($email) . "'";
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

          if ($user_name!="") {	  
			  $sql="select count(*) as C from nua_user where user_name = '" . $user_name . "'";
		      $z=$this->X->sql($sql); 
		      if ($z[0]['C']>0) {
			     $output=array();
                 $output['error_ccde']="1";
                 $output['error_message']="Account with the User Name already exists";
                 return $output;			
		      }
          }	
		  
          if ($new_org_name!="") {
               $post=array();
			   $post['table_name']="nua_org";
			   $post['action']="insert";
			   $post['org_name']=$new_org_name;
			   $post['email']=$new_org_name;
			   $post['phone_mobile']=$new_org_phone;
			   $post['created_by']=$data['uid'];
               $org_id=$this->X->post($post);			   
          }	
		  
          $post=array();
		  $post['table_name']="nua_user";
	      $post['action']="insert";
		  $post['user_name']=$user_name;
		  $post['email']=strtolower($email);
		  $post['role']=$role;
		  if ($role=="badmin"||$role=="broker") {
			 $post['org_type']="orgnaization";  
			 $post['org_id']=$org_id;	
		  }
		  if ($role=="sadmin"||$role=="user") {
			 $post['org_type']="nuaxess";  
			 $post['org_id']=1;	
		  }		  
		  $post['phone_mobile']=$phone_mobile;
		  $post['full_name']=$full_name;
		  		  
          $id=$this->X->post($post);			  
		  $output=array();
		  $output['error_code']="0";
		  $output['id']=$id;
		  $output['error_message']="";
		  return $output;
	}
	
    function getUserList($data) {

      if ($data['id']=="inactive") {
          $sql="SELECT * FROM nua_user where status = 'inactive' order by id";
      } else {
          $sql="SELECT * FROM nua_user order by id";
      }

      $s=$this->X->sql($sql);
      $output=array();
      foreach($s as $t) {
		  if ($t['org_type']=="organization") {
			  if ($t['org_id']!=0) {
				   $sql="select org_name from nua_org where id = " . $t['org_id'];
                   $z=$this->X->sql($sql);
                   if (sizeof($z)==0) {
                       $t['org_name']="Not Found";
                   } else {
					   $t['org_name']=$z[0]['org_name'];
                   }					   
			  } else {
				   $t['org_name']="Not Assigned";
			  }
		  }
		  if ($t['org_type']=="broker") {
			  
		  }
		  if ($t['org_type']=="company") {
			  
		  }
		  if ($t['org_type']=="employee") {
			  
		  }
		  
		 if ($t['invite_date']!="") {
              $d=date_create($t['invite_date']);
              $t['invite_date']=date_format($d,"m/d/Y");
		 }
		 if ($t['last_login_date']!="") {
              $d=date_create($t['last_login_date']);
              $t['invite_date']=date_format($d,"m/d/Y");
		 }
         array_push($output,$t);
      }
       return $output;
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

    function doKey($uid,$password) {
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

    function postEnroll($data) {
        if ($data['data']['formData']['id']!="") {
		     $sql="select * from nua_user where id = " . $data['id'];
             $z=$this->X->sql($sql);
			 if (sizeof($z)>0) {
				 if ($z[0]['password_status']!=0) {
						$output=array();
						$output['error_code']="1";
						$output['error_message']="This account has already enrolled.  Contact your plan administrator.";
						return $output;					 
				 }
			     $this->makeKey($data['data']['formData']['id'],$data['data']['formData']['password']);	 
				 if (strtolower($data['data']['formData']['email'])!=$z[0]['email']) {
				      $sql="update nua_user set email = '" . strtolower($data['data']['formData']['email']) . " where id = " . $data['data']['formData']['id'];
                      $this->X->execute($sql);					  
				 }
				 $phone_mobile=$data['data']['formData']['phone_mobile'];
				 $phone_mobile=str_replace(' ','',$phone_mobile);
				 $phone_mobile=str_replace('(','',$phone_mobile);
				 $phone_mobile=str_replace(')','',$phone_mobile);	
				 $phone_mobile=str_replace('-','',$phone_mobile);
                 if (strlen($phone_mobile)==10) {
                     if ($phone_mobile!=$z[0]['phone_mobile']) {
				      $sql="update nua_user set phone_mobile = '" . strtolower($phone_mobile) . " where id = " . $data['data']['formData']['id'];
                      $this->X->execute($sql);							 
					 }
                 }					 
				 $output=array();
				 $output['error_code']="0";
				 $output['error']="0";
				 return $output;
			 }
		} else {
			
			$phone_mobile=$data['data']['formData']['phone_mobile'];
			$phone_mobile=str_replace(' ','',$phone_mobile);
			$phone_mobile=str_replace('(','',$phone_mobile);
			$phone_mobile=str_replace(')','',$phone_mobile);	
			$phone_mobile=str_replace('-','',$phone_mobile);
			
			$email=strtolower($data['data']['formData']['email']);
			
			$sql="select * from nua_user where phone_mobile = '" . $phone_mobile . "' and email = '" . strtolower($email) . "'";
            $z=$this->X->sql($sql);
			 if (sizeof($z)>0) {
				 if ($z[0]['password_status']!=0) {
						$output=array();
						$output['error_code']="1";
						$output['error_message']="This account has already enrolled.  Contact your plan administrator.";
						return $output;					 
				 }
			     $this->makeKey($z[0]['id'],$data['data']['formData']['password']);	 				 
				 $output=array();
				 $output['error_code']="0";
				 $output['error']="0";
				 return $output;
			 } else {
				 $output=array();
				 $output['error_code']="1";
				 $output['error_message']="Cannot find an account setup with this email address and phone #.  Contact your plan administrator.";
				 return $output;				 
			}
        }	
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
	
    function getLogin($data) {
		
        $o=array();
		
		//--
		//-- Test Usernames
		//--
		
        if ($data['username']=="sadmin")  {
			$o=$this->make_error(0,"");
			$o['uid']=1;
			$o['role']="sadmin";
			return $o;
		}
		
        if ($data['username']=="badmin") {
			return $this->make_error(0,"");
			$o['uid']=1;
			$o['role']="badmin";
			return $o;
		}
		
        if ($data['username']=="eadmin") return $this->make_error(0,"");
        if ($data['username']=="employee") return $this->make_error(0,"");
		if ($data['username']=="user") return $this->make_error(0,"");
		
		//--
		//-- Find out if the Username, Phone, or Email is valid.
		//--
        if ($data['username']=="") return $this->make_error(101,"Username, Phone, or Email must be entered!");
		
		$result=$this->checkUser($data['username'],$data['pwd']);
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

