<?php

namespace app\apply\admin;

use app\admin\controller\Admin;
use app\common\builder\ZBuilder as Zb;
use think\Db;

use app\apply\Utils\Utils as Ut;

class Intervtime extends Admin {
    public function index($mid = null) {
        $aids = Ut::getAdminModel()['ids'];   
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
            $tabs[$key] = ['title' => $val, 'url' => url('index', ['mid' => $key])];
        }
        $adds = [
            ['text', 'name', '名称', '学生用户选择面试时间或管理员分配面试时间时，会看到该字段，建议以<code>时间+地点</code>格式创建，比如<code>5月18日下午2.00，C101</code>'],
            ['number', 'maxstudent', '当场最多报名学生数', '当该场学生用户报名达到该数目时，该场会变得不可用，管理员也无法进行分配,-1表示不限制'],
            ['hidden', 'create_time', $this->request->time()],
            ['select', 'mid', '所在模型', '', $info]
        ];



        $interv = Db::name('apply_intervtime')->where('mid', $cid)->select();



        foreach($interv as $k => $it) {
            if(Ut::getModelSetting($cid)['lockquestion'] == 1) {
                $interv[$k]['already_set'] = count(Db::name('apply_student_'.$cid)->where('intervtime', $it['name'])->select());
                $interv[$k]['already_checkin'] = count(Db::name('apply_student_'.$cid)->where('intervtime', $it['name'])->where('status', 3)->select());                
            }else {
                $interv[$k]['already_set'] = 0;
                $interv[$k]['already_checkin'] = 0;
                

            }
        }

        return Zb::make('table')
            ->addColumns([
                ['name', '名称', 'text.edit'],
                ['maxstudent', '当场最多报名学生数', 'number'],
                ['already_set', '当前场次已经报名学生数'],
                ['already_checkin', '当前场次已经签到学生数'],
                ['status', '状态', 'switch'],
                ['order', '排序', 'text.edit'],
		['create_time', '创建时间', 'datetime'],
                ['right_button', '操作', 'btn']
            ])
            ->setTabNav($tabs, $cid)
            ->autoAdd($adds, 'apply_intervtime', '', 'create_time')            
            ->addTopButtons('enable,disable,delete')
            ->setTableName('apply_intervtime')
            ->setPrimaryKey('ID')
            ->autoEdit($adds, 'apply_intervtime', '', 'update_time')            
            ->addRightButtons('delete')
            ->setRowList($interv)
            ->fetch();
    }
}
