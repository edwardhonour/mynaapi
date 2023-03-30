<?php
ini_set('display_errors',1);
ini_set('display_startup_errors',1);
header('Access-Control-Allow-Headers: Access-Control-Allow-Origin, Content-Type, Authorization');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET,PUT,POST,DELETE,PATCH,OPTIONS');
header('Content-type: application/json');
require_once('/var/www/classes/class.PSDB.php');
require_once('/var/www/classes/class.broker-forms.php');
$F=new FORMS();

if (isset($_COOKIE['uid'])) { $uid=$_COOKIE['uid']; } else { $uid=55009; }

class SURVEY {
    public $X;
    public $json;
    public $arr;
    function __construct() {
         $this->X=new XRDB();
    }

    function getFacilityDashboard($data) {
          $sql="select * from psp_facility where id = " . $data['id'];
          $r=$this->X->sql($sql);
          $formData=array();       
	  foreach($r[0] as $name=>$value) {
               $formData[$name]=$value;
	  }
          $output['formData']=$formData;
          return $output;
    }

    function gAssessmentList($data) {
          $sql="select * from psp_survey where user_id = " . $data['uid'];
          $r=$this->X->sql($sql);
          $list=array();
          foreach($r as $s) {
              $sql="select * from psp_facility where id = " . $s['facility_id'];
              $q=$this->X->sql($sql);
              if (sizeof($q)>0) {
              $s['facility_name']=$q[0]['facility_name'];
              $s['address']=$q[0]['address'];
              $s['city']=$q[0]['city'];
              $s['state']=$q[0]['state'];
              $s['zip']=$q[0]['zip'];
              }
              $sql="select * from psp_stakeholder where id = " . $s['stakeholder_id'];
              $q=$this->X->sql($sql);
              if (sizeof($q)>0) {
                   $s['stakeholder_name']=$q[0]['org_name'];
	      }
              $sql="select * from psp_template where id = " . $s['template_id'];
              $q=$this->X->sql($sql);
              if (sizeof($q)>0) {
                   $s['template_name']=$q[0]['template_name'];
	      }
              array_push($list,$s);
          }
          return $list;
  }

  function getAssessmentRecord($data) {
     $id=$data['data']['assessment_id'];
     $output=array();
     $sql="select * from psp_survey where id = " . $id;
     $rs=$this->X->sql($sql);
     $output['assessment']=$rs[0];
     $sql="select * from psp_facility where id = " . $rs[0]['facility_id'];
     $rs=$this->X->sql($sql);
     if (sizeof($rs)>0) {
           $output['facility']=$rs[0];
           $sql="select * from psp_stakeholder where id = " . $rs[0]['stakeholder_id'];
           $t=$this->X->sql($sql);
           if (sizeof($t)>0) {
                $output['stakeholder']=$t[0];
           }
     } else {
           $output['facility']=array();
           $output['stakeholder']=array();
     }

     $sql="select * from psp_template where id = " . $output['assessment']['template_id'];
     $rs=$this->X->sql($sql);
     $output['template']=$rs[0];
     return $output;
     
  }

  function getAssessmentDashboard($data) {
     $id=$data['id'];
     $output=array();
     $sql="select * from psp_survey where id = " . $id;
     $rs=$this->X->sql($sql);
     $output['assessment']=$rs[0];
     $sql="select * from psp_facility where id = " . $rs[0]['facility_id'];
     $rs=$this->X->sql($sql);
     if (sizeof($rs)>0) {
           $output['facility']=$rs[0];
           $sql="select * from psp_stakeholder where id = " . $rs[0]['stakeholder_id'];
           $t=$this->X->sql($sql);
           if (sizeof($t)>0) {
                $output['stakeholder']=$t[0];
           }
     } else {
           $output['facility']=array();
           $output['stakeholder']=array();
     }

     $sql="select * from psp_template where id = " . $output['assessment']['template_id'];
     $rs=$this->X->sql($sql);
     $output['template']=$rs[0];

     $sql="select id, section_name from psp_template_section where template_id = " . $output['assessment']['template_id'] . " order by section_order";
     $t=$this->X->sql($sql);
     
     $output['sections']=$t;
     $formData=array();
     $formData['section_id']=0;
     $output['formData']=$formData;
     
     return $output;
     
  }

  function getAssessments($data) {
     $output=array();
     $output['list']=$this->gAssessmentList($data);
     return $output;
  }

    function gFacilityList($data) {
	  $sql="select * from psp_facility where user_id = " . $data['uid'];
	  $r=$this->X->sql($sql);
$list=array();
          foreach($r as $s) {
              $sql="select * from psp_stakeholder where id = " . $s['stakeholder_id'];
              $q=$this->X->sql($sql);
              if (sizeof($q)>0) {
              $s['stakeholder_name']=$q[0]['org_name'];
	      }
              array_push($list,$s);
          }
          return $list;
   }

    function gStakeholderList($data) {
	  $sql="select * from psp_stakeholder where user_id = " . $data['uid'];
	  $r=$this->X->sql($sql);
          $list=array();
          foreach($r as $s) {
         //     $sql="select * from psp_stakeholder where id = " . $s['stakeholder_id'];
         //     $q=$this->X->sql($sql);
         //     if (sizeof($q)>0) {
         //     $s['stakeholder_name']=$q[0]['org_name'];
	 //     }
              array_push($list,$s);
          }
          return $list;
   }

    function getFacilityAssessments($data) {
	  $sql="select * from psp_facility where id = " . $data['data']['facility_id'];
          $rs=$this->X->sql($sql);
          $formData=$rs[0];

          $sql="select * from psp_survey where facility_id = " . $data['data']['facility_id'] . " order by id";
          $rs=$this->X->sql($sql);
          $list=array();
          foreach($rs as $r) {
               $sql="select template_name from psp_template where id = " . $r['template_id'];
	       $r3=$this->X->sql($sql);
               if (sizeof($r3)>0) {
                   $r['template_name']=$r3[0]['template_name'];
               } else {
                   $r['template_name']="No Survey Specified";
               }
               array_push($list,$r);
          }
          $output=array();
          $output['list']=$list;
	  $output['formData']=$formData;
          return $output;
    }

    function getFacilityList($data) {
          $sql="select * from psp_facility order by facility_name";
          $rs=$this->X->sql($sql);
          $list=array();
          foreach($rs as $r) {
               $sql="select org_name from psp_stakeholder where id = " . $r['stakeholder_id'];
	       $r3=$this->X->sql($sql);
               if (sizeof($r3)>0) {
                   $r['org_name']=$r3[0]['org_name'];
               } else {
                   $r['org_name']="No Stakeholder Specified";
               }
               array_push($list,$r);
          }
          $output=array();
          $output['list']=$list;
          return $output;
    }

    function getFacilityForm($data) {
         
	    $output=array();
	    $formData=array();
	    $formData['table_name']="psp_facility";
	    $formData['action']="insert";
	    $formData['id']="";
	    $formData['facility_name']="";
	    $formData['address']="";
	    $formData['address_2']="";
	    $formData['city']="";
	    $formData['state']="";
	    $formData['zip']="";
	    $formData['lng']="";
	    $formData['lat']="";
            if ($data['data']['stakeholder_id']!='-1') {
	        $formData['stakeholder_id']=$data['data']['stakeholder_id'];
            } else {
	        $formData['stakeholder_id']="";
            }
	    $output['formData']=$formData;

	    $sql="select id, org_name from psp_stakeholder where user_id = " . $data['uid'] . " order by org_name";
	    $rs=$this->X->sql($sql);
	    $output['stakeholders']=$rs;

	    return $output;
    }

    function postScheduleForm($data) {
            $post=$data['data'];
            $post['user_id']=$data['uid'];
            $sql="select stakeholder_id from psp_facility where id = " . $post['facility_id'];
            $r=$this->X->sql($sql);
            $post['stakeholder_id']=$r[0]['stakeholder_id'];
            $output=array();
            $this->X->post($post);
            $output['error_code']=0;
            return $output;
    }

    function getScheduleForm($data) {
         
	    $output=array();
	    $formData=array();
	    $formData['table_name']="psp_survey";
	    $formData['action']="insert";
	    $formData['id']="";
	    $formData['facility_id']="";
	    $formData['stakeholder_id']="";
	    $formData['template_id']="";
	    $formData['due_date']="";
	    $formData['est_start_date']="";
	    $formData['est_complete_date']="";
            if ($data['data']['stakeholder_id']!='-1') {
	        $formData['stakeholder_id']=$data['data']['stakeholder_id'];
            } else {
	        $formData['stakeholder_id']="";
            }
            if ($data['data']['facility_id']!='-1') {
	        $formData['facility_id']=$data['data']['facility_id'];
            } else {
	        $formData['stakeholder_id']="";
            }
	    $output['formData']=$formData;

	    $sql="select id, org_name from psp_stakeholder where user_id = " . $data['uid'] . " order by org_name";
	    $rs=$this->X->sql($sql);
	    $output['stakeholders']=$rs;

	    $sql="select id, facility_name from psp_facility order by facility_name";
	    $rs=$this->X->sql($sql);
	    $output['facilities']=$rs;

	    $sql="select id, template_name from psp_template order by template_name";
	    $rs=$this->X->sql($sql);
	    $output['templates']=$rs;

	    return $output;
    }

    function postStakeholderForm($data) {
            $data['data']['user_id']=$data['uid'];
	    $this->X->post($data['data']);
	    $output=array();
	    $output['error_code']=0;
	    $f=$this->getStakeholderForm($data); 
	    $output['formData']=$f;
	    return $output;
    } 

    function postFacilityForm($data) {
	    $this->X->post($data['data']);
	    $output=array();
	    $output['error_code']=0;
	    $f=$this->getFacilityForm($data); 
	    $output['formData']=$f;
	    return $output;
    }

    function getStakeholderDashboard($data) {

	    $sql="select * from psp_stakeholder where id = " . $data['id'];
	    $rs=$this->X->sql($sql);

	    $formData=array();
	    foreach($rs[0] as $name=>$value) {
                 if ($name!='create_timestamp') $formData[$name]=$value;
	    }
	    $output=array();
	    $output['formData']=$formData;

	    $output['list']=array();

	    $sql="select * from psp_facility where stakeholder_id = " . $data['id'] . " order by facility_name";
	    $rs=$this->X->sql($sql);

	    $output['facilities']=$rs;

	    return $output;
    } 

    function getStakeholderInfo($data) {

	    $sql="select * from psp_stakeholder where id = " . $data['data']['stakeholder_id'];
	    $rs=$this->X->sql($sql);
            if (sizeof($rs)>0) {
	         $formData=array();
	         foreach($rs[0] as $name=>$value) {
                     if ($name!='create_timestamp') $formData[$name]=$value;
	         }
	         $output=array();
	         $output['formData']=$formData;

	         $sql="select * from psp_facility where stakeholder_id = " . $data['data']['stakeholder_id'] . " order by facility_name";
	         $rs=$this->X->sql($sql);
          
	         $output['facilities']=$rs;
            } else {
                 $output['facilities']=array();
                 $output['formData']=array();
            }
	    return $output;
    } 

    function getFacilityPreviewInfo($data) {

       if (isset($data['data']['facility_id'])) {
	    $sql="select * from psp_facility where id = " . $data['data']['facility_id'];
	    $rs=$this->X->sql($sql);
            if (sizeof($rs)>0) {
	         $formData=array();
	         foreach($rs[0] as $name=>$value) {
                     if ($name!='create_timestamp') $formData[$name]=$value;
	         }
	         $output=array();
	         $output['formData']=$formData;

	         $sql="select * from psp_survey where facility_id = " . $data['data']['facility_id'] . " order by id";
	         $rs=$this->X->sql($sql);
	         $output['surveys']=$rs;
            } else {
                 $output['surveys']=array();
                 $output['formData']=array();
	    }
	    return $output;
       } else {
            $output=array();
            $formData=array();
            $formData['id']="-2";
            $output['formData']=$formData;
            return $output;
       }
    } 

    function getStakeholderForm($data) {
	    $output=array();
	    $formData=array();
	    $formData['table_name']="psp_stakeholder";
	    $formData['action']="insert";
	    $formData['id']="";
	    $formData['org_name']="";
	    $formData['contact_name']="";
	    $formData['contact_email']="";
	    $formData['contact_phone']="";
	    $formData['user_id']=$data['uid'];
	    $formData['address']="";
	    $formData['city']="";
	    $formData['state']="";
	    $formData['zip']="";
	    $formData['account_type']="";
	    $formData['description']="";
	    $output['formData']=$formData;
	    return $output;
    }
	function getSurveyHome($data) {
			$sql="select * FROM psp_template_section ORDER BY SECTION_ORDER";
			$rs=$this->X->sql($sql);
			$output=array();
			$output['section_list']=$rs;
			if (!isset($data['data']['section_id'])) $data['data']['section_id']="10";
			$output['current_section']=$this->getSurveySection($data);
			return $output;
	}

	function getAssessmentSection($data) {
			$output=array();
			if (!isset($data['data']['section_id'])) $data['data']['section_id']="10";
			$output['current_section']=$this->getCurrentSurveySection($data);
			return $output;
	}

	function getCurrentSurveySection($data) {
		$id=$data['data']['section_id'];
		$sql="SELECT * FROM psp_template_section WHERE id = " . $id;
		$rs=$this->X->sql($sql);

		$sql="SELECT id, option_id, section_id, parent_id, option_text, option_type, ";
		$sql.=" option_order, option_group FROM psp_template_option WHERE ";
		$sql.=" section_id = " . $id . " AND parent_id = 0 ORDER BY option_order";

		$sections=array();
		$rs2=$this->X->sql($sql);
		$output=array();
		$output['section']=$rs[0];
		$output['questions']=$rs2;
		return $output;
	}

	function getStakeholderList($data) {
			$sql="select * FROM psp_stakeholder ORDER BY org_name";
			$rs=$this->X->sql($sql);
			$output=array();
			$output['list']=$rs;

			return $output;
	}
	function addTemplate($data) {
			$formData=$data['data'];
			$post=array();
			$post['id']="";
			$post['table_name']="psp_template";
			$post['action']="insert";
			$post['template_name']=$data['data']['template_name'];
			$post['template_type']=$data['data']['template_type'];
			$post['asset_type']=$data['data']['asset_type'];
			$post['visibility']=$data['data']['visibility'];
			$post['sector']=$data['data']['sector'];
                        $post['user_id']=$data['uid'];
			$this->X->post($post);
			$output=$this->getSurveyTemplates($data);
			return $output;
	}

	function getAdminDashboard($data) {
		$user=array();
		$user['forced_logout']=0;
		$output=array();
		$output['user']=$user;
                $output['assessments']=$this->gAssessmentList($data);
                $output['facilities']=$this->gFacilityList($data);
		return $output;
	}

    function addOption($data) {

		$post=array();
		$post=$data['data'];
		$post['table_name']="psp_template_option";
		$post['action']="insert";

		$post['option_order']=intval($data['data']['option_order'])+5;
		$this->X->post($post);
		$this->reorderSection($post['section_id']);

		return array();

	}

	function reorderSection($section_id) {
		$sql="select id, option_order FROM psp_template_option where section_id = " . $section_id . " ORDER BY option_order";
		$rs=$this->X->sql($sql);
		$counter=0;
		foreach($rs as $r) {
			$counter+=10;
			$post=array();
			$post['id']=$r['id'];
			$post['table_name']="psp_template_option";
			$post['action']="insert";
			$post['option_order']=$counter;
			$this->X->post($post);
		}
	}

    function addBaseQuestion($data) {

		$post=array();
		$post=$data['data'];
		$post['table_name']="psp_template_option";
		$post['action']="insert";

		$post['option_order']=intval($data['data']['option_order'])+5;
		$this->reorderSection($post['section_id']);
		return array();

	}

	function deleteBaseQuestion($data) {
			$id=$data['data']['option_id'];
			$sql="delete from psp_template_option where id = " . $id;
			$this->X->execute($sql);
			return array();
	}

	function deleteTemplate($data) {
			$formData=$data['data'];
			$id=$data['data']['template_id'];
			$sql="delete from psp_template where id = " . $id;
			$this->X->execute($sql);
			$sql="delete from psp_template_option where section_id IN (select id FROM ";
			$sql.=" psp_template_section where template_id = " . $id . ")";
			$this->X->execute($sql);
			$sql="delete from psp_template_section where template_id = " . $id;
			$this->X->execute($sql);
			return array();
	}

	function addTemplateSection($data) {
			$formData=$data['data'];
			$post=array();
			$post['id']=$data['data']['id'];
			$post['table_name']="psp_template_section";
			$post['action']="insert";
			$post['section_name']=$data['data']['section_name'];
			$post['section_order']=intval($data['data']['section_order'])+5;
			$post['template_id']=$data['data']['template_id'];
			$this->X->post($post);
			$data['id']=$data['data']['template_id'];

			$sql="select id, section_order FROM psp_template_section where template_id = " . $data['id'] . " ORDER BY section_order";
			$rs=$this->X->sql($sql);
			$counter=0;
			foreach($rs as $r) {
				$counter+=10;
				$post=array();
				$post['id']=$r['id'];
				$post['table_name']="psp_template_section";
				$post['action']="insert";
				$post['section_order']=$counter;
				$this->X->post($post);
			}
			return array();
	}

	function deleteTemplateSection($data) {
			$formData=$data['data'];
			$id=$data['data']['id'];
			$sql="delete from psp_template_option where section_id = " . $id;
			$this->X->execute($sql);
			$sql="delete from psp_template_section where id = " . $id;
			$this->X->execute($sql);
			return array();
	}

	function getSurveySection($data) {

		//-- Get the list of main questions for the section.

		$id=$data['data']['section_id'];
		$sql="SELECT * FROM psp_template_section WHERE id = " . $id;
		$rs=$this->X->sql($sql);

		$sql="SELECT id, option_id, section_id, parent_id, option_text, option_type, option_order, option_group FROM psp_template_option WHERE ";
		$sql.="section_id = " . $id . " AND parent_id = 0 ORDER BY option_order";

		$sections=array();
		$rs2=$this->X->sql($sql);


		$output=array();
		$output['section']=$rs[0];
		$output['questions']=$rs2;


		return $output;

	}


	function getSurveyPreviewSection($data) {
		$id=$data['id'];
		$id2=$data['id2'];
		$sql="SELECT * FROM psp_template_section WHERE template_id = " . $id . " order by section_order";
		$rs=$this->X->sql($sql);
		$output=array();
		$output['sections']=$rs;
		$formData=array();
		$formData['template_id']=$id;
		$formData['section_id']=$id2;

		$sql="select template_name from psp_template where id = " . $id;
		$rx=$this->X->sql($sql);
		$formData['template_name']=$rx[0]['template_name'];

		if ($id2==0) { 
                    $formData['section_id']=$output['sections'][0]['id'];
                    $id2=$output['sections'][0]['id'];
		}

		$sql="select * from psp_template_section where id = " . $formData['section_id'];
                $t=$this->X->sql($sql);
		$formData['section_name']=$t[0]['section_name'];

		$output['formData']=$formData;

		$sql="SELECT id, option_id, section_id, parent_id, option_text, option_type, option_order, option_group FROM psp_template_option WHERE ";
		$sql.="section_id = " . $id2 . " AND parent_id = 0 ORDER BY option_order";

		$rs2=$this->X->sql($sql);
		$output['questions']=$rs2;

		return $output;
	}


	function pushOption($options, $survey_id, $question_id) {

		$sql="select * from psp_template_option WHERE option_id = " . $question_id;
		$rs=$this->X->sql($sql);
		$o=$rs[0];

		$sql="select option_text from psp_template_option_answers WHERE option_id = " . $question_id . " AND survey_id = " . $survey_id;
		$rs=$this->X->sql($sql);

		if (sizeof($rs)==0) {
			if ($o['option_type']!='Radio Group'&&$o['option_type']!='Checkbox Group') {
				$options['p'.$question_id]="";
			} else {
				$options['p'.$question_id]="";
			}
		} else {
			if ($o['option_type']!='Radio Group'&&$o['option_type']!='Checkbox Group') {
				$options['p'.$question_id]=$rs[0]['option_text'];
			} else {
				// Lets experiment with putting sub-option values in the checkbox value.
				$options['p'.$question_id]=$rs[0]['option_text'];
			}
		}

		return $options;
	}

	function getSurveyTemplates($data) {

		$output=array();
		$sql="select * FROM psp_template order by id";
		$rs=$this->X->sql($sql);
		$output['list']=$rs;
		$formData=array();
		$formData['id']="";
		$formData['template_name']="";
		$formData['template_type']="";
		$formData['asset_type']="";
		$formData['visibility']="Public";
		$formData['sector']="General";
		$output['formData']=$formData;
		return $output;

	}

	function getTemplateDashboard($data) {

		$output=array();
		$formData=array();
		$formData['id']="";
		$formData['section_name']="";
		$formData['section_order']="";
		$formData['template_id']=$data['id'];

		$sql="select * from psp_template where id = " . $data['id'];
		$rs=$this->X->sql($sql);
		$output['template']=$rs[0];

		$sql="select id, section_name, section_order, '' AS click, '' AS click2 FROM psp_template_section where template_id = " . $data['id'] . " order by section_order";
		$rs=$this->X->sql($sql);
		$output['list']=$rs;
		$sections=array();
		$s=array();
		$s['value']=0;
		$s['text']="At the Beginning";
		array_push($sections,$s);
		$last_id=0;
		foreach($rs as $r) {
			$s=array();
			$s['value']=$r['section_order'];
			$s['text']='After ' . $r['section_name'];
			$last_id=$r['section_order'];
			array_push($sections,$s);
		}
		$s=array();
		$s['value']=$last_id;
		$s['text']="At the End";
		array_push($sections,$s);
		$output['sections']=$sections;

		$output['formData']=$formData;
		return $output;

	}

	function getTemplateSectionDashboard($data) {

		$id=$data['id'];
		$sql="SELECT * FROM psp_template_section WHERE id = " . $id;
		$rs=$this->X->sql($sql);

		$sql="SELECT id, option_id, section_id, parent_id, option_text, option_type, option_order, option_group FROM psp_template_option WHERE ";
		$sql.="section_id = " . $id . " AND parent_id = 0 ORDER BY option_order";

		$rs2=$this->X->sql($sql);
		$sections=array();
		$s=array();
		$s['value']=0;
		$s['text']="At the Beginning";
		array_push($sections,$s);
		foreach($rs2 as $r) {
			$s=array();
			$s['value']=$r['option_order'];
			$s['text']="After " . $r['option_text'];
			array_push($sections,$s);
		}
		$output=array();
		$output['sections']=$sections;
		$output['section']=$rs[0];
		$output['questions']=$rs2;
		$formData=array();
		$formData['id']="";
		$formData['section_id']=$data['id'];
		$formData['option_id']="";
		$formData['option_text']="";
		$formData['option_type']="";
		$formData['option_group']="0";
		$formData['option_order']="0";
		$formData['parent_id']="0";
		$formData['height']="";
		$formData['width']="";
		$formData['third_person_text']="";
		$formData['help_text']="";
		$formData['validation_text']="";
		$output['formData']=$formData;
		return $output;

	}


	function getSurveyQuestion($data) {

		$output=array();
		//-- Question we are displaying
		$id=$data['data']['question_id'];
		//-- Survey we are performing.
		$survey_id=$data['data']['survey_id'];

		$formData=array();
		$formData['question_id']=$id;
		$formData['survey_id']=$id;

		$conditionals=array();

		//-- Get the master question.
		$sql="SELECT * FROM psp_template_option WHERE option_id = " . $id;
		$rs=$this->X->sql($sql);
		$output['question']=$rs[0];
		$formData=$this->pushOption($formData,$survey_id, $id);
		$conditionals['p'.$id]=$formData['p'.$id];

		$output['question']['model']='p'.$id;

		//-- Select all the selectable options.
		$sql="SELECT id, option_id, section_id, parent_id, option_text, option_type, option_group, option_order FROM psp_template_option WHERE ";
		$sql.=" option_group = " . $id . " ORDER BY option_order";
		$rs2=$this->X->sql($sql);

		$options=array();
		foreach($rs2 as $r) {
			if ($r['option_text']=='Checkbox') { $formData=$this->pushOption($formData,$survey_id, $r['option_id']); }
			if ($r['option_text']=='Checkbox Button') { $formData=$this->pushOption($formData,$survey_id, $r['option_id']); }
			if ($r['option_text']=='Radio Button') { $formData=$this->pushOption($formData,$survey_id, $r['option_id']); }
			if ($r['option_text']=='Text') { $formData=$this->pushOption($formData,$survey_id, $r['option_id']); }
			if ($r['option_text']=='Textarea') { $formData=$this->pushOption($formData,$survey_id, $r['option_id']); }
			if ($r['option_text']=='Date') { $formData=$this->pushOption($formData,$survey_id, $r['option_id']); }
			array_push($options,$r);
		}
		$output['options']=$options;

		//-- Look for Children that are not selectable
		//-- Select all the selectable options.
		$sql="SELECT id, option_id, section_id, parent_id, option_text, option_type, option_group, option_order FROM psp_template_option WHERE ";
		$sql.=" option_group = " . $id . " ORDER BY option_order";
		$rs2=$this->X->sql($sql);
		$boilerplate=array();
		foreach($rs2 as $rr) {
			$sql="SELECT id, option_id, section_id, parent_id, option_text, option_type, option_order, option_group FROM psp_template_option WHERE ";
			$sql.=" parent_id = " . $rr['option_id'] . " AND option_group = 0 ORDER BY option_order";
			$rs3=$this->X->sql($sql);
			foreach($rs3 as $r) {
				$r['model']='p'.$r['parent_id'];
				array_push($boilerplate,$r);
			}
		}
		$output['boilerplate']=$boilerplate;

		//-- Look for hidden children of selectable options - This makes the recursive call...
		$sql="SELECT id, option_id, section_id, parent_id, option_text, option_type, option_order FROM psp_template_option WHERE ";
		$sql.=" option_group = " . $id . " ORDER BY option_order";
		$rs2=$this->X->sql($sql);
		$options=array();
		foreach($rs2 as $r) {
			$sql="SELECT id, option_id, section_id, parent_id, option_text, option_type, option_order FROM psp_template_option WHERE ";
			$sql.=" parent_id = " . $r['option_id'] . " AND option_group = 0 ORDER BY option_order";
			$rs2=$this->X->sql($sql);
            if (sizeof($rs2)>0) {
				array_push($options,$r);
			    $sql="select option_text from psp_template_option_answers WHERE option_id = " . $r['option_id'] . " AND survey_id = " . $survey_id;
				$rs=$this->X->sql($sql);
				$conditionals['p'.$r['option_id']]='N';
			}
		}

		$output['formData']=$formData;
		$output['conditionals']=$conditionals;

		return $output;

	}

function getOneOption($data) {

		$output=array();
		$id=$data['data']['id'];

		$formData=array();
		$conditionals=array();

		$sql="SELECT * FROM psp_template_option WHERE option_id = " . $id;
		$rs=$this->X->sql($sql);
		$output['question']=$rs[0];
		foreach($rs[0] as $name => $value) {
				if ($name!='CREATE_TIMESTAMP') {
					$formData[$name]=$value;
				}
		}

		$sql="SELECT id, option_id, section_id, parent_id, option_text, option_type, option_group, option_order FROM psp_template_option WHERE ";
		$sql.=" option_group = " . $id . " ORDER BY option_order";
		$rs2=$this->X->sql($sql);
		$output['options']=$rs2;

		$suboptions=array();
		foreach($output['options'] as $o) {
			$sql="SELECT id, option_id, section_id, parent_id, option_text, option_type, option_group, option_order FROM psp_template_option WHERE ";
			$sql.=" option_group = 0 AND parent_id = " . $o['option_id'] . " ORDER BY option_order";
			$rs2=$this->X->sql($sql);
			foreach($rs2 as $rr) {
				$rr['PARENT']=$o['option_text'];
				array_push($suboptions,$rr);
			}
		}
		$output['suboptions']=$suboptions;
		$output['formData']=$formData;

		return $output;

	}

function getOneFormOption($data) {

		$output=array();
		$section_id=$data['data']['section_id'];
		$parent_id=$data['data']['parent_id'];
		$option_group=$data['data']['option_group'];
		$option_id=$data['data']['option_id'];

		//-- Rules
		//--    If Parent id = 0, then it's a base question for the section.
		//--    If Option Group = 0, then it is a top level question:
		//--		Only: Radio Group, Checkbox Group, Title, Subtitle, text, textarea, date...
		//--    If Option Group <> 0, then it is an answer option:
		//--         If - Radio Group then only Radio Button.
		//--         If - Checkbox Group then only Check boxes.
		//--         If - Title then no subs.
		//--         If - Subtitle then no subs.
		//--         If textarea, text, date then no subs.

		$formData=array();
		$formData['table_name']='psp_template_option';
		$formData['action']='insert';

		$conditionals=array();

        $typeoptions=array();
		if ($parent_id!='0') {
			$sql="SELECT * FROM psp_template_option WHERE option_id = " . $parent_id;
			$rs=$this->X->sql($sql);
			$parent=$rs[0];
		} else {
			$parent=array();
			$parent['id']=0;
			$parent['option_text']="";
			$parent['option_type']="";
			$parent['option_group']="";
		}
        if ($option_group!='0') {
			$sql="SELECT * FROM psp_template_option WHERE option_id = " . $option_group;
			$rs=$this->X->sql($sql);
			$parent=$rs[0];
			$parent_type=$rs[0]['option_type'];
			if ($parent_type=="Radio Group") {
				$to=array();
				$to['value']="Radio Button";
				$to['text']="Radio Button";
				array_push($typeoptions,$to);
				$to=array();
				$to['value']="Label";
				$to['text']="Label";
				array_push($typeoptions,$to);
			}
			if ($parent_type=="Checkbox Group") {
				$to=array();
				$to['value']="Checkbox";
				$to['text']="Checkbox";
				array_push($typeoptions,$to);
				$to=array();
				$to['value']="Label";
				$to['text']="Label";
				array_push($typeoptions,$to);
			}
			if ($parent_type=="Select Group") {
				$to=array();
				$to['value']="Select";
				$to['text']="Select";
				array_push($typeoptions,$to);
			}
			if ($parent_type=="Title") {
				$to=array();
				$to['value']="Text Field";
				$to['text']="Text Field";
				array_push($typeoptions,$to);
				$to=array();
				$to['value']="Textarea";
				$to['text']="Textarea";
				array_push($typeoptions,$to);
				$to=array();
				$to['value']="Date";
				$to['text']="Date";
				array_push($typeoptions,$to);
				$to=array();
				$to['value']="Title";
				$to['text']="Title";
				array_push($typeoptions,$to);
				$to=array();
				$to['value']="Subtitle";
				$to['text']="Subtitle";
				array_push($typeoptions,$to);
				$to=array();
				$to['value']="Label";
				$to['text']="Label";
				array_push($typeoptions,$to);
			}
			if ($parent_type=="Subtitle") {
				$to=array();
				$to['value']="Text Field";
				$to['text']="Text Field";
				array_push($typeoptions,$to);
				$to=array();
				$to['value']="Textarea";
				$to['text']="Textarea";
				array_push($typeoptions,$to);
				$to=array();
				$to['value']="Date";
				$to['text']="Date";
				array_push($typeoptions,$to);
				$to=array();
				$to['value']="Title";
				$to['text']="Title";
				array_push($typeoptions,$to);
				$to=array();
				$to['value']="Subtitle";
				$to['text']="Subtitle";
				array_push($typeoptions,$to);
				$to=array();
				$to['value']="Label";
				$to['text']="Label";
				array_push($typeoptions,$to);
			}
		} else {
			$to=array();
			$to['value']="Radio Group";
			$to['text']="Radio Group";
			array_push($typeoptions,$to);
			$to=array();
			$to['value']="Checkbox Group";
			$to['text']="Checkbox Group";
			array_push($typeoptions,$to);
			$to=array();
			$to['value']="Select Group";
			$to['text']="Select Group";
			array_push($typeoptions,$to);
			$to=array();
			$to['value']="Title";
			$to['text']="Title";
			array_push($typeoptions,$to);
			$to=array();
			$to['value']="Subtitle";
			$to['text']="Subtitle";
			array_push($typeoptions,$to);
			$to=array();
			$to['value']="Text Field";
			$to['text']="Text Field";
			array_push($typeoptions,$to);
			$to=array();
			$to['value']="Textarea";
			$to['text']="Textarea";
			array_push($typeoptions,$to);
			$to=array();
			$to['value']="Label";
			$to['text']="Label";
			array_push($typeoptions,$to);
			$to=array();
			$to['value']="Date";
			$to['text']="Date";
			array_push($typeoptions,$to);
		}

		if ($option_id!=0) {
			$sql="SELECT * FROM psp_template_option WHERE option_id = " . $option_id;
			$rs=$this->X->sql($sql);
			if (sizeof($rs)>0) {
				foreach($rs[0] as $name => $value) {
					if ($name!='CREATE_TIMESTAMP') {
						$formData[$name]=$value;
					}
				}
			} else {
				$formData['id']="";
				$formData['option_id']="";
				$formData['section_id']=$section_id;
				$formData['parent_id']=$parent_id;
				$formData['option_group']=$option_group;
				$formData['option_text']="";
				$formData['option_type']="";
				$formData['option_order']="";
				$formData['third_person_text']="";
				$formData['help_text']="";
				$formData['validation_text']="";
				$formData['height']="";
				$formData['width']="";
			}
		} else {
				$formData['id']="";
				$formData['option_id']="";
				$formData['section_id']=$section_id;
				$formData['parent_id']=$parent_id;
				$formData['option_group']=$option_group;
				$formData['option_text']="";
				$formData['option_type']="";
				$formData['option_order']="";
				$formData['third_person_text']="";
				$formData['help_text']="";
				$formData['validation_text']="";
				$formData['height']="";
				$formData['width']="";
		}
		$sql="SELECT * FROM psp_template_option WHERE parent_id = " . $parent_id;
		$sql.=" AND option_group = " . $option_group . " ORDER BY option_order";
		$rs2=$this->X->sql($sql);
		$order_options=array();
		if ($parent_id==0) {
			$oo=array();
			$oo['value']="0";
			$oo['name']="At the Beginning";
		} else {
			$oo['value']=$parent['option_order'];
			$oo['name']="After " . $parent['option_text'];
		}
		array_push($order_options,$oo);
		foreach($rs2 as $rs) {
			$oo=array();
			$oo['value']=$rs['option_order'];
			$oo['name']="After - " . $rs['option_text'];
			array_push($order_options,$oo);
		}

		$output['location_options']=$order_options;
		$output['type_options']=$typeoptions;
		$output['parent']=$parent;
		$output['formData']=$formData;
		return $output;

	}
	function addFormOption($data) {
		$post=$data['data'];
		$post['table_name']="psp_template_option";
		$post['action']="insert";
		$this->X->post($post);
		$this->reorderSection($post['section_id']);

		$output=array();
		return $output;

	}
	function previewSection($data) {
		$sql="select * from psp_template_section where id = " . $data['id'];
		$rs=$this->X->sql($sql);
		$output=$data;
		$output['section']=$rs[0];
		return $output;
	}
	function deleteStakeholder($data) {
                $output=array();
		return $output;
	}
}

$A=new SURVEY();
$output=array();


$data = file_get_contents("php://input");
$data = json_decode($data, TRUE);
if (!isset($data['q'])) $data['q']="user";
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

$output=array();
if ($data['q']=='login') {
    $o=$F->getLogin($data);
} else {

        if ($data['q']!="post-enroll") {
                $o=$F->start_output($data);
        } else {
                $o=array();
                $o['user']=array();
                $o['user']['force_logout']=0;
                $o['user']['force_off']=0;
        }
   if ($o['user']['force_logout']>0) {
		$output['user']=$o['user'];
                $o=json_encode($output);
                $o=stripcslashes($o);
                $o=str_replace('null','""',$o);
                echo $o;
        die();
   }
}

   switch ($data['q']) {
    case 'get-stakeholder-info':
		     $output=$A->getStakeholderInfo($data);
		 break;
    case 'post-facility-form':
		     $output=$A->postFacilityForm($data);
		 break;
    case 'get-stakeholder-form':
		     $output=$A->getStakeholderForm($data);
		 break;
    case 'get-facility-form':
		     $output=$A->getFacilityForm($data);
		 break;
    case 'add-option':
		     $output=$A->addOption($data);
			 break;
    case 'post-form-option':
		     $output=$A->addFormOption($data);
			 break;
    case 'post-base-question':
		     $output=$A->addBaseQuestion($data);
			 break;
    case 'add-template':
		     $output=$A->addTemplate($data);
			 break;
    case 'delete-template':
		     $output=$A->deleteTemplate($data);
			 break;
    case 'add-template-section':
		     $output=$A->addTemplateSection($data);
			 break;
    case 'get-survey-section':
		     $output=$A->getSurveySection($data);
			 break;
    case 'get-survey-question':
		     $output=$A->getSurveyQuestion($data);
			 break;
    case 'survey':
		     $output=$A->getSurveyHome($data);
			 break;
case 'templates':
case 'template-list':
case 'preview-list':
     $output=$A->getSurveyTemplates($data);
	 break;
case 'preview-section':
     $output=$A->getSurveyPreviewSection($data);
	 break;
case 'template-dashboard':
     $output=$A->getTemplateDashboard($data);
	 break;
case 'template-section-dashboard':
     $output=$A->getTemplateSectionDashboard($data);
	 break;
case 'get-one-option':
     $output=$A->getOneOption($data);
			 break;
		case 'get-one-form-option':
		     $output=$A->getOneFormOption($data);
			 break;
		case 'post-form-option':
		     $output=$A->addFormOption($data);
			 break;
		case 'post-stakeholder-form':
		     $output=$A->postStakeholderForm($data);
			 break;
		case 'preview-section':
		     $output=$A->previewSection($data);
			 break;
		case 'stakeholder-list':
		     $output=$A->getStakeholderList($data);
			 break;
		case 'stakeholders':
		     $output=$A->getStakeholderList($data);
			 break;
		case 'stakeholder-dashboard':
		     $output=$A->getStakeholderDashboard($data);
			 break;
		case 'facilities':
		     $output=$A->getFacilityList($data);
			 break;
		case 'facility-dashboard':
		     $output=$A->getFacilityDashboard($data);
			 break;
		case 'surveys':
		     $output=$A->getSurveyList($data);
			 break;
		case 'survey-dashboard':
		     $output=$A->getFacilityDashboard($data);
			 break;
		case 'get-template-list':
		     $output=$A->getSurveyTemplates($data);
			 break;
		case 'previews':
		     $output=$A->getSurveyTemplates($data);
			 break;
		case 'sadmin':
		     $output=$A->getAdminDashboard($data);
			 break;
		case 'get-schedule-form':
		     $output=$A->getScheduleForm($data);
			 break;
		case 'post-schedule-form':
		     $output=$A->postScheduleForm($data);
			 break;
		case 'get-facility-assessments':
		     $output=$A->getFacilityAssessments($data);
			 break;
		case 'assessments':
		     $output=$A->getAssessments($data);
			 break;
		case 'get-facility-preview-info':
		     $output=$A->getFacilityPreviewInfo($data);
			 break;
		case 'get-saa-form':
		     $output=$A->getFacilityPreviewInfo($data);
			 break;
		case 'get-tenant-form':
		     $output=$A->getTenantForm($data);
			 break;
		case 'get-assessment-record':
		     $output=$A->getAssessmentRecord($data);
			 break;
		case 'assessment-dashboard':
		     $output=$A->getAssessmentDashboard($data);
			 break;
		case 'get-assessment-section':
		     $output=$A->getAssessmentSection($data);
			 break;
		case 'delete-template-section':
		     $output=$A->deleteTemplateSection($data);
			 break;
       default:
    $output=$F->getLogin($data);
    //        $output=$A->getAdminDashboard($data);
            break;
    }

$output['user']=$o;
$o=array();
$o=str_replace('null','""',json_encode($output, JSON_HEX_TAG |
        JSON_HEX_APOS |
        JSON_HEX_QUOT |
        JSON_HEX_AMP |
        JSON_UNESCAPED_UNICODE));

echo $o;

?>
