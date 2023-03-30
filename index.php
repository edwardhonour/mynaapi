<?php
ini_set('display_errors',1);
ini_set('display_startup_errors',1);
header('Access-Control-Allow-Headers: Access-Control-Allow-Origin, Content-Type, Authorization');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET,PUT,POST,DELETE,PATCH,OPTIONS');
header('Content-type: application/json');
require_once('/var/www/classes/class.pages.php');
require_once('/var/www/classes/class.forms.php');
require_once('/var/www/classes/class.users.php');
require_once('/var/www/classes/class.members.php');
require_once('/var/www/classes/class.security.php');
require_once('/var/www/classes/class.messages.php');
require_once('/var/www/classes/class.quotes.php');
$P=new PAGES();
$F=new FORMS();
$U=new USERS();
$M=new MEMBERS();
$Q=new QUOTES();
$S=new SECURITY();
$TXT=new MESSAGES();
$data = file_get_contents("php://input");
$data = json_decode($data, TRUE);
$output=array();
if (!isset($data['q'])) $data['q']="vertical-menu";
$aa=explode("/",$data['q']);
if (isset($aa[1])) {
     $data['q']=$aa[1];
     if (isset($aa[2])) {
         $data['id']=$aa[2]; 
	 }
     if (isset($aa[3])) {
         $data['id2']=$aa[3]; 
	 }		 
	 if (isset($aa[4])) {
         $data['id3']=$aa[4]; 
	 }		 
}
if ($data['q']=='login') {
    $output=$F->getLogin($data);
} else {		

	if ($data['q']!="post-enroll") {
		$output=$F->start_output($data);
	} else {
		$output=array();
		$output['user']=array();
		$output['user']['force_logout']=0;
		$output['user']['force_off']=0;
	}
   if ($output['user']['force_logout']>0) {
		$o=json_encode($output);
		$o=stripcslashes($o);
		$o=str_replace('null','""',$o);
		echo $o; 
        die();		
   }
 	
switch ($data['q']) {
    case 'send-test-email':
       $TXT->sendMail("support@nuaxess.email", "ed@artfin.com", "Edward", "My NuAxess", "support@nuaxess.email");
        break;
case 'submit-quote':
        $output=$F->submitQuoteRequest($data);
        break;	
		case 'broker-admin-invite':
                $output=$TXT->sendBrokerAdminInvite($data);
                break;	
		case 'duplicate-employees':
                $output=$F->getDuplicateEmployees($data);
                break;	
		case 'fix-duplicate-employees':
                $output=$F->fixDuplicateEmployees($data);
                break;	
		case 'invoice-dashboard':
                $output=$F->invoiceDashboard($data);
                break;	
		case 'employee-lookup':
                $output=$F->getTableFormData($data,"nua_employee");
                break;	
		case 'add-invoice':
                $output=$F->getInvoiceForm($data);
                break;	
		case 'invoice-list':
                $output=$F->getInvoiceList($data);
                break;	
		case 'post-employee-lookup':
                $output=$F->postEmployeeLookup($data);
                break;	
		case 'post-add-invoice':
                $output=$F->postAddInvoice($data);
                break;	
		case 'admin-invite':
                $output=$TXT->sendAdminInvite($data);
                break;	
		case 'apa-plan-list':
                $output=$F->apaPlanList($data);
                break;	
		case 'member-lookup':
                $output=$F->memberLookup($data);
                break;	
		case 'guardian-lookup':
                $output=$F->guardianLookup($data);
                break;	
		case 'submit-quote-request':
                $output=$F->submitQuoteRequest($data);
                break;	
		case 'post-add-member-family':
                $output=$F->postAddMemberFamily($data);
                break;		
		case 'post-add-medication':
                $output=$F->postAddMedication($data);
                break;	
	    case 'plans':
                $output=$F->getMemberPlans($data);
                break;
	    case 'medications':
                $output=$F->getFamilyMedications($data);
                break;					
        case 'dashboard':
                $output=$F->getMemberDashboard($data);
                break;	
        case 'sadmin':
                $output=$F->getTestDashboard($data);
                break;
        case 'badmin':
                $output=$F->getBAdminDashboard($data);
                break;
        case 'post-enroll':
                $output=$F->postEnroll($data);
                break;
        case 'post-edit-plan':
                $output=$F->postEditPlan($data);
                break;
        case 'post-edit-user':
                $output=$F->postEditUser($data);
                break;
        case 'post-edit-company':
                $output=$F->postEditCompany($data);
                break;
        case 'post-edit-insurance':
                $output=$F->postEditInsurance($data);
                break;
		case 'send-invite':
		       $output=$F->sendInviteTxt($data);
			   break;
		case 'info':
		       $output=$F->getEmployeeInfo($data);
			   break;
	    case 'previous':
			   $output=$F->getIHQInsurance($data);
			   break;
	//Adds
        case 'add-org':
                $output=$F->getAddOrgForm($data);
                break;
        case 'family':
                $output=$F->getEmployeeFamily($data);
                break;
        case 'add-company':
                $output=$F->getCompanyFormData($data);
                break;
        case 'add-user':
                $output=$F->getUserFormData($data);
                break;
        case 'add-plan':
                $output=$F->getTableFormData("nua_plan");
                break;
        case 'add-employee':
                $output=$F->getTableFormData("nua_employee");
                break;
        case 'add-quote':
                $output=$F->getTableFormData("nua_quote");
                break;
        case 'add-quote-request':
                $output=$F->getQuoteRequestFormData($data);
                break;
	//Post Adds				
        case 'post-add-org':
                $output=$F->postAddOrg($data);
                break;
        case 'post-make-plans':
                $output=$F->postMakePlans($data);
                break;
        case 'post-add-company':
                $output=$F->postAddCompany($data,"company");
                break;
        case 'post-add-employee':
                $output=$F->postAdd($data,"nua_employee");
                break;
	        case 'post-edit-ihq-info':
                $output=$F->postEditIHQInfo($data);
                break;
	        case 'post-ihq-answer':
                $output=$F->postIHQAnswer($data);
                break;
	    case 'post-add-employee-small':
                $output=$F->postAddEmployeeSmall($data,"nua_employee");
                break;
        case 'post-add-quote':
                $output=$F->postAdd($data,"nua_quote");
                break;
        case 'post-add-plan':
		        if ($data['formData']['order_id']=="") $data['formData']['order_id']="0"; 
                $output=$F->postAdd($data,"nua_plan");
                break;
        case 'post-add-prospect':
                $output=$F->postAddProspect($data,"nua_plan");
                break;
        case 'post-add-quote-request':
                $output=$F->postAddQuoteRequest($data,"nua_quote");
                break;
        case 'post-edit-quote':
                $output=$F->postEditQuoteRequest($data);
                break;
        case 'post-edit-quote-request':
                $output=$F->postEditQuoteRequest($data);
                break;
        case 'post-edit-quote-background':
                $output=$F->postEditQuoteRequestBackground($data);
                break;
        case 'post-edit-cont':
                $output=$F->postEditCont($data);
                break;
        case 'post-submit-quote':
                $output=$F->postSubmitQuoteRequest($data,"nua_plan");
                break;
        case 'post-add-user':
                $output=$F->postAddUser($data);
                break;
        case 'post-add-family':
                $output=$F->postAddFamily($data);
                break;
        //Edits
        case 'edit-org':
                $output=$F->getEdit($data,"nua_org");
                break;
        case 'edit-company':
                $output=$F->getEditCompany($data,"nua_company");
                break;
        case 'edit-employee':
                $output=$F->getEdit($data,"nua_employee");
                break;
        case 'edit-quote':
                $output=$F->getEdit($data,"nua_quote");
                break;
        case 'edit-plan':
                $output=$F->getEdit($data,"nua_plan");
                break;
        case 'edit-user':
                $output=$F->getEditUser($data);
                break;
        case 'edit-quote-request':
                $output=$F->getEditQuoteRequest($data);
                break;
        //Dashboards			
        case 'org-dashboard':
                $output=$F->getOrgDashboard($data);
                break;
        case 'company-dashboard':
                $output=$F->getCompanyDashboard($data);
                break;
        case 'apa-company-dashboard':
                $output=$F->getAPACompanyDashboard($data);
                break;
        case 'employee-dashboard':
                $output=$F->getEmployeeDashboard($data);
                break;
        case 'employee-ihq':
                $output=$F->getEmployeeIHQ($data);
                break;
        case 'ihq':
                $output=$F->getMemberIHQ($data);
                break;
        case 'quote-request-dashboard':
                $output=$F->getQuoteRequestDashboard($data);
                break;
        case 'quote-dashboard':
                $output=$F->getQuoteDashboard($data);
                break;
        case 'plan-dashboard':
                $output=$F->getPlanDashboard($data);
                break;
        case 'user-dashboard':
                $output=$F->getUserDashboard($data);
                break;
        //Lists
        case 'org-list':
                $output=$F->getOrgList($data);
                break;
        case 'company-list':
                $output=$F->getCompanyList($data);
                break;				
        case 'employee-list':
                $output=$F->getEmployeeList($data);
                break;	
        case 'plan-list':
                $output=$F->getPlanList($data);
                break;	
        case 'user-list':
                $output=$F->getUserList($data);
                break;	
        case 'quote-request-list':
                $output=$F->getRequestList($data);
                break;					
        case 'users':
                $output=$U->getUserList($data);
                break;
        case 'user-dashboard':
                $output=$U->getUserDashboard($data);
                break;
        case 'add-user':
                $output=$U->getAddUser($data);
                break;
        case 'post-user-sadmin':
                $output=$U->postAddUser($data,"sadmin");
                break;
        case 'post-edit-employee':
                $output=$F->postEditEmployee($data);
                break;				
        case 'post-add-level':
                $output=$F->postAddLevel($data,"sadmin");
                break;		
	//-- Plans
        case 'plans':
                $output=$data;
                $output['error']="0";
                break;
        case 'plan-dashboard':
                $output=$data;
                $output['error']="0";
                break;
    //-- Organizations
        case 'organizations':
                $output=$data;
                $output['error']="0";
                break;
        case 'organization-dashboard':
                $output=$data;
                $output['error']="0";
                break;
	//-- Companies
        case 'companies':
                $output=$data;
                $output['error']="0";
                break;
        case 'company-dashboard':
                $output=$data;
                $output['error']="0";
                break;;
	//-- Roles
        case 'roles':
                $output=$data;
                $output['error']="0";
                break;
        case 'role-dashboard':
                $output=$data;
                $output['error']="0";
                break;
        case 'add-role':
                $output=$data;
                $output['error']="0";
                break;
	//-- Admins
        case 'sadmin':
                $output=$data;
                $output['error']="0";
                break;
        case 'badmin':
                $output=$data;
                $output['error']="0";
                break;
        case 'eadmin':
                $output=$data;
                $output['error']="0";
                break;
        case 'employee':
                $output=$data;
                $output['error']="0";
                break;				
        case 'v':
                $output=$P->getDashboardData($data);
                break;
        case 'h':
                $output=$P->getDashboardData($data);
                break;
        case 'tables-basic':
        case 'tables-basic':
                $output=$P->getTableData($data);
                break;
        case 'users':
                $output=$U->getUserList($data);
                break;
        default:
                $output=$P->getDashboardData($data);
                break;
	}
}

$o=json_encode($output);
$o=stripcslashes($o);
$o=str_replace('null','""',$o);
echo $o;
?>



