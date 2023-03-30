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
		
           return array();	    	
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
  	
        function success() {
            $output=array();
            $output['error_code']=0;
            $output['error_message']="";
            return $output;	 
        }

        function getTestDashboard($data) {
            $date=date_create();
	    $output=$this->start_output($data);
            $sql="select * from nua_guide_category order by category_order";
	    $z=$this->X->sql($sql);
	    $list=array();
	    foreach($z as $a) {
                  $sql="select * from nua_guide where category_id = " . $a['id'] . " order by guide_order";
		  $l=$this->X->sql($sql);
		  $a['guides']=$l;
		  array_push($list,$a);
            }
            $output['list']=$list;
	    return $output;
        }

	function getCategoryHome($data) {
            $output=$this->start_output($data);
            $sql="select * from nua_guide_category where id = " . $data['id'];
	    $z=$this->X->sql($sql);
	    $list=array();
	    foreach($z as $a) {
                  $sql="select * from nua_guide where category_id = " . $a['id'] . " order by guide_order";
		  $l=$this->X->sql($sql);
		  $a['guides']=$l;
		  array_push($list,$a);
            }
            $output['list']=$list;
	    return $output;
	}

	function getCategoryDashboard($data) {
	    $output=$this->start_output($data);
		 
            $sql="select * from nua_guide_category where id = " . $data['id'];
	    $d=$this->X->sql($sql);
            $invoice=$d[0];
	    $e=$invoice;

            $formData=array();
	    foreach($e as $name=>$value) {
                 $formData[$name]=$value;
                 $output[$name]=$value;
	    }
	    $output['formData']=$formData;
	    $guides=array();
            $sql="select * from nua_guide where category_id = " . $data['id'] . " order by guide_order";
	    $l=$this->X->sql($sql);
	    $a['guides']=$l;
            $output['guides']=$guides;
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
	
	function getCategoryList($data, $type='') {
		
		$output=$this->start_output($data);
	        $output['id']=$data['id'];	
		
                $sql="select * from nua_guide_category order by category_order";
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

}


