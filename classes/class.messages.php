<?php

//--
//--  MyNuAccess
//--
//--  Edward Honour  
//--  November 30, 2021
//--
//--  Messaging Class
//--  Send Text messages using Twilio and Emails using MailerSend.
//--
//
require_once('/var/www/classes/class.XRDB.php');
require_once('/var/www/classes/Services/Twilio2.php');
require_once('/var/www/classes/class.security.php');

class MESSAGES {

	protected $X;
	
        function __construct() {
           $this->X=new XRDB();    
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
	
}

                         

