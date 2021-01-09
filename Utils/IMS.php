<?php
namespace app\apply\Utils;
use think\Db;

class IMS {
  private $person_add_tpl = '<person><sourcedid><source>STD_ADD_%d_%s</source><id>%s</id></sourcedid><userid authenticationtype="manual">%s</userid><name><fn>%s</fn><n><family>%s</family><given>%s</given></n></name><email>%s</email></person>';
  private $person_rml_tpl = '';
  private $member_add_tpl = '|<member><sourcedid><source>COURSE_REG_%s_%s</source><id>%s</id></sourcedid><role roletype="01"><status>1</status></role></member>';
  private $member_rml_tpl = '|<member><sourcedid><source>COURSE_WDR_%s_%s</source><id>%s</id></sourcedid><role roletype="01"><status>0</status></role></member>';
  public $config;
  private $mid;
  private $uid;
  private $op;

  public function __construct($mid, $uid, $op='add') {
    $model = Utils::getModelSetting($mid);
    $this->mid = $mid;
    $this->uid = $uid;
    $this->op = $op;
    $this->config = $model;
  }
  
  public function getIMSFileContent() {
  	if($this->config['write_ims'] == 0) return false;
  	$student = Db::name('apply_student_'.$this->mid)->where('id', $this->uid)->select();
  	if($student == null) return false;
  	$student = $student[0];
  	if($this->op == 'add') {
  		$ims_content = sprintf($this->person_add_tpl, $this->mid, $student[$this->config['ims_student_id']], $student[$this->config['ims_student_id']], $student[$this->config['ims_student_id']], $student[$this->config['ims_student_name']], mb_substr($student[$this->config['ims_student_name']],0,1), mb_substr($student[$this->config['ims_student_name']],1), $student[$this->config['ims_student_email']]);
  		$ims_content .= sprintf($this->member_add_tpl, $this->config['ims_course_id'], $student[$this->config['ims_student_id']], $student[$this->config['ims_student_id']]);
  	}else {
  		$ims_content = sprintf($this->member_rml_tmp, $this->config['ims_course_id'], $student[$this->config['ims_student_id']], $student[$this->config['ims_student_id']]);
  	}
  	return $ims_content;
  }


}

