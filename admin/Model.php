<?php
namespace app\apply\admin;

use app\admin\controller\Admin;
use app\common\builder\ZBuilder as Zb;
use app\apply\Utils\Utils as Ut;
use think\Db;
use util\File;

class Model extends Admin {
    public function index() {
        $models = Db::name('apply_model')->select();
        $users = Db::name('admin_user')->select();
        foreach($users as $key => $val) {
            $usrs[$val['id']] = $val['nickname'].'('.$val['username'].')';
        }

        $adds = [
            ['hidden', 'ID'],
            ['text', 'name', '模型名称'],
            ['select', 'creater', '指定超级管理员', '', $usrs],
            ['datetime','applystarttime', '申请开始时间'],
            ['datetime','applyendtime', '申请截止时间'],
            ['datetime','intervstarttime', '面试开始时间'],
            ['datetime','intervendtime', '面试截止时间'],
            ['number', 'maxstudent', '最多报名学生数', '-1表示不限制'],
            ['number', 'maxintervtime', '最多使用面试时间数', '-1表示不限制'],
            ['text', 'appid', '微信公众号AppID'],
            ['text', 'appsecret', '微信公众号AppSecret'],
            ['text', 'template_id', '模版消息Template_ID'],            
            ['select', 'allowchoosetime', '是否允许学生自主选择面试时间', '', ['0' => '系统分配，禁止自选', '1' => '学生自选']],                             
            ['select', 'needfile', '是否需要上传文件', '', ['0' => '不需要', '1' => '需要']],            
            ['radio', 'write_ims', '生成IMS企业文件', '', ['否', '是']],
            ['text', 'ims_course_id', 'IMS课程ID'],
            ['text', 'ims_student_email', 'IMS学生邮箱字段名'],
            ['text', 'ims_student_name', 'IMS学生姓名字段名'],
            ['text', 'ims_student_id', 'IMS学生学号字段名'],
            ['select', 'ims_min_status', 'IMS触发学生状态', '', ['无条件', '已完成报名','审核通过','面试时间确定','面试已签到','终审通过']],
            ['hidden', 'create_time', $this->request->time()],
        ];

        return Zb::make('table')
            ->addColumns([
                ['name', '名称', 'text.edit'],
                ['creater', '创建人', 'select', $usrs],
                ['applystarttime', '申请开始时间', 'datetime.edit'],
                ['applyendtime', '申请截止时间', 'datetime.edit'],
                ['maxstudent', '最多报名学生数', 'number'],
                ['maxintervtime', '最多使用面试时间数', 'number'],
                ['appid', '微信公众号APPID', 'text.edit'],         
                ['needfile', '需要上传文件', 'switch'],
                ['status', '状态', 'switch'],
                ['create_time', '创建时间', 'datetime'],
                ['name', '设计问卷', 'link', url('question/index', ['mid' => '__ID__']), '_blank', 'pop'],
                ['right_button', '操作', 'btn']
            ])
            ->setPrimaryKey('ID')
            ->autoEdit($adds, 'apply_model', 'Model', 'update_time', '', true)            
            ->addRightButtons('delete')
            ->autoAdd($adds, 'apply_model', 'Model', 'create_time', '', true)            
            ->addTopButtons('enable,disable,delete')
            ->setRowList($models)
            ->setPageTitle('模型管理')
            ->fetch();
    }

    public function modeleditor($mid = null) {
        $aids = Ut::getAdminModel()['ids'];  
        if ($this->request->isPost()) {
            if($mid == null) $mid = input('post.ID');
            if(!in_array($mid, $aids)) $this->error('非法操作，你无权管理该模型');
            
            $data = $this->request->post();
            $data['applystarttime'] = strtotime($data['applystarttime']);
            $data['applyendtime'] = strtotime($data['applyendtime']);
            
            if (false !== Db::name('apply_model')->where('ID', $mid)->update($data)) {
                $this->success('处理成功');
            } else {
                $this->error('处理失败');
            }
          
        }
        if(empty($aids)) $this->error('当前没有模型');                 
        if($mid != null) {
            if(!in_array($mid, $aids)) $this->error('非法操作，你无权管理该模型');
            $cid = $mid;
        }else{
            $cid = $aids[0];
        }
        //cid为当前需要操作的id
        //生成tab
        $tabs = [];
        $info = Ut::getAdminModel()['info'];
        foreach($info as $key => $val) {
            $tabs[$key] = ['title' => $val, 'url' => url('modeleditor', ['mid' => $key])];
        }

        $model = Db::name('apply_model')->where('ID', $cid)->select();

        if(empty($model)) $this->error('不存在可以编辑的模型！');
        $model[0]['link'] = Ut::domain().'apply/weentrance/applyindex/mid/'.$model[0]['ID'];
        $btn = [
            'title' => '初始化模型',
            'target' => '_blank',
            'href' => url('init', ['mid' => $cid]), // 此属性仅用于a标签按钮，button按钮不产生作用
            'icon' => 'fa fa-fw fa-refresh'
        ];
        

        $adds = [
            ['hidden', 'ID'],
            ['static', 'name', '模型名称', '修改模型名称请联系系统管理员处理'],
            ['static', 'link', '微信链接', '你可以将此链接添加致微信端自动回复或者是阅读原文以及图文外链，学生用户在微信打开此链接便可完成报名、选择/确认面试时间等操作，注意此链接只能在微信端打开！'],            
            ['datetime','applystarttime', '申请开始时间'],
            ['datetime','applyendtime', '申请截止时间'],
            ['datetime','intervstarttime', '面试开始时间'],
            ['datetime','intervendtime', '面试截止时间'],
            ['text', 'appid', '微信公众号AppID'],
            ['password', 'appsecret', '微信公众号AppSecret'],
            ['text', 'template_id', '模版消息Template_ID'],            
            ['select', 'allowchoosetime', '是否允许学生自主选择面试时间', '', ['0' => '系统分配，禁止自选', '1' => '学生自选']],                             
            ['select', 'needfile', '是否需要上传文件', '', ['0' => '不需要', '1' => '需要']],  
            ['ueditor', 'notice', '报名须知', '注意，请尽量使用纯文本（word）编辑，不要使用135等编辑器编辑，否则容易出现错位情况！'],                                       
            ['wangeditor', 'intervtemplate', '面试提醒模版消息内容'],
            ['wangeditor', 'resulttemplate', '录用通知模版消息内容'],      
            ['wangeditor', 'notemplate', '不予录用模版消息内容'], 
            ['text', 'ims_student_email', 'IMS学生邮箱字段名', '用于对接培训系统，不清楚这是什么请联系系统管理员！'],
            ['text', 'ims_student_name', 'IMS学生姓名字段名', '用于对接培训系统，不清楚这是什么请联系系统管理员！'],
            ['text', 'ims_student_id', 'IMS学生学号字段名', '用于对接培训系统，不清楚这是什么请联系系统管理员！'],
            ['select', 'ims_min_status', 'IMS触发学生状态', '用于对接培训系统，不清楚这是什么请联系系统管理员！', ['已完成报名','审核通过','面试时间确定','面试已签到','终审通过']],
            ['hidden', 'create_time', $this->request->time()],
        ];

        return Zb::make('form')
            ->setTabNav($tabs, $cid)
            ->addFormItems($adds)
            ->addButton('init', $btn, 'a')
            ->setFormData($model[0])
            ->fetch();
    }

    public function init($mid = null) {
        $aids = Ut::getAdminModel()['ids']; 
        if(empty($aids)) $this->error('当前没有模型');                 
        if($mid != null) {
            if(!in_array($mid, $aids)) $this->error('非法操作，你无权管理该模型');
            $cid = $mid;
        }else{
            $this->error('不能直接访问该网页');
        }
        //cid为当前需要操作的id
        $model = Ut::getModelSetting($cid);
        if($model['lockquestion'] == 0) $this->error('只有已经锁定问题的模型才可以初始化');
        //删除所有问题
        //Db::name('apply_question')->where('mid', $cid)->delete();
        //删除数据表
        Db::query('drop table bks_apply_student_'.$mid);
        //删除文件夹
        $module_dir = APP_PATH.'/apply/studentimgs/'.$cid;

        // 删除旧的导出数据
        if (is_dir($module_dir)) {
            File::del_dir($module_dir);
        }
         //解除锁定模式
         Db::name('apply_model')->where('ID', $cid)->setDec('lockquestion');
         $this->success('初始化成功');
    }

}
