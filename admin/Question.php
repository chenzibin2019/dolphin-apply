<?php

namespace app\apply\admin;

use app\admin\controller\Admin;
use app\common\builder\ZBuilder as Zb;
use think\Db;

use app\apply\Utils\Utils as Ut;

class Question extends Admin {
    public function index($mid = null) {
        $m = Db::name('apply_question');
        $aids = Ut::getAdminModel(true)['ids'];   
        if(empty($aids)) $this->error('当前没有可以编辑问卷的模型');               
        if($mid != null) {
            if(!in_array($mid, $aids)) $this->error('非法操作，你无权管理该模型，或者该问卷已经被锁定');
            $cid = $mid;
        }else{
            $cid = $aids[0];
        }
        $m = $m->where('mid', $cid)->select();            
        $tabs = [];
        $info = Ut::getAdminModel(true)['info'];
        foreach($info as $key => $val) {
            $tabs[$key] = ['title' => $val, 'url' => url('index', ['mid' => $key])];
        }
        $types = [
            1 => '文本输入区域',
            2 => '单选',
            3 => '多选',
            4 => '多行文本',
            5 => '下拉选择'
        ];

        $ads = [
          	['hidden', 'ID'],
            ['text', 'name', '问题名称'],
            ['select', 'type', '类型', '', $types],
            ['tags', 'options', '备选', '如果类型是单选、多选或下拉选择需要填写此项，学生用户可以根据系统你提供的选项里边进行选择', ''],   
          	['number', 'orders', '排序', '数值越小显示越靠前', 100],
            ['select', 'mid', '所在模型', $cid, $info]
        ];

        $btn = [
            'title' => '锁定问题',
            'class' => 'btn btn-default ajax-get confirm',
            'icon'  => 'fa fa-fw fa-lock',
            'href'  => url('confirm', ['mid' => $cid]),
            'data-title' => '只有锁定问题后学生才可以报名，锁定后所有问题及其所有属性不可以更改，确定要现在锁定吗？'
        ];

        //var_dump($tabs);
        return Zb::make('table')
            ->addColumns([
                ['name', '问题名称', 'text.edit'],
                ['type', '类型', 'select', $types],
                ['create_time', '创建时间', 'datetime'],
                ['status', '状态', 'switch'],
              	['orders', '排序', 'number'],
                ['options', '备选项', 'text.edit'],
                ['right_button', '操作', 'btn']
            ])
            ->setRowList($m)
            ->setPageTitle('编辑问题')
            ->autoEdit($ads, 'apply_question', 'Question')            
            ->addRightButtons('delete')
            ->autoAdd($ads, 'apply_question', 'Question', 'create_time')           
            ->addTopButtons('enable,disable,delete')
            ->addTopButton('custom', $btn)
            ->setPrimaryKey('ID') 
            ->setTabNav($tabs, $cid)
            ->fetch();
    }

    public function confirm($mid = null) {
        if($mid === null) $this->error('非法操作');
        $model = Db::name('apply_model')->where('ID', $mid)->where('lockquestion', 0)->select();
        if(!$model) $this->error('你的模型当前不可以被锁定,-1', '', ['_parent_reload' => 1]);
        $questions = Db::name('apply_question')->where('mid', $mid)->where('status', 1)->select();
        if(!$questions) $this->error('当前模型下不存在任何活动的问题，不能锁定');
        foreach($questions as $key => $val) {
            if(in_array($val['type'], [2,3,5]) && empty($val['options'])) $this->error('问题'.$val['name'].'没有任何选项!');
        }
        Db::name('apply_model')->where('ID', $mid)->setInc('lockquestion');
        $sql = <<<EOF
        CREATE TABLE IF NOT EXISTS `bks_apply_student_{$mid}` (
        `ID` int(11) NOT NULL AUTO_INCREMENT,
        `openid` varchar(255) NOT NULL,
        `create_time` varchar(255) NOT NULL,
        `score` varchar(255) DEFAULT NULL,
        `intervtime` varchar(255) DEFAULT NULL,
        `checkin_time` varchar(255) DEFAULT NULL,
        `update_time` varchar(255) DEFAULT NULL,
        `status` int(255) NOT NULL DEFAULT '0',
        `file` varchar(255) NOT NULL DEFAULT '0',
        `has_ims_acc` int(4) NOT NULL DEFAULT 0,
        PRIMARY KEY (`ID`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8;
EOF;
        Db::query($sql);
        foreach($questions as $key => $val) {
            $sql = <<<EOF
            ALTER TABLE `bks_apply_student_{$mid}`
            ADD COLUMN `{$val['name']}` longtext;
EOF;
            Db::query($sql);
        }

        mkdir(APP_PATH.'/apply/studentimgs/'.$mid.'/');
        $this->success('成功锁定问题，现在可以在你设定的报名时间内进行报名了！', '', ['_parent_reload' => 1]);

    }
}