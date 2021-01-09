<?php

namespace app\apply\admin;

use app\admin\controller\Admin;
use app\common\builder\ZBuilder as Zb;
use think\Db;
use app\apply\Utils\Utils as Ut;

use util\PHPZip;

class Student extends Admin {
    public function index($mid = null) {
        $map = $this->getMap();
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
        //获取问题
        $qs = Db::name('apply_question')->where('mid', $cid)->select();  

        $sta = [
            '-1' => '报名终止',
            '0' => '已完成报名',
            '1' => '审核通过',
            '2' => '面试时间确定',
            '3' => '面试已签到',
            '4' => '终审通过'
        ];

        $cols = [
	    ['ID', '报名编号'],
            ['openid', '微信识别码'],
            ['create_time', '报名时间', 'datetime'],
            ['intervtime', '面试时间', 'text.edit'],
            ['checkin_time', '签到时间', 'datetime'],
            ['status', '状态', 'select', $sta],
            ['update_time', '最后更新时间', 'datetime'],
            ['file', '附件', 'link', url('student/showImg', ['mid' => $cid, 'openid' => '__openid__']), '_blank', 'pop']            
        ];

        //写入问卷
        $sr = [];
        foreach($qs as $key => $val) {
            $cols[$key+8] = [$val['name'], $val['name'], 'text.edit'];
            $sr[(string)$val['name']] = $val['name'];
        }

        //只有锁定问题才拉取学生数据，否则直接输出空白
        $minfo = Db::name('apply_model')->where('id', $cid)->select();
        $m = $minfo[0];
        if($m['lockquestion'] == 0){
            $std = [];
            $tips = '当前模型没有锁定问卷，不可以管理学生，请进入左侧问卷管理锁定问卷之后才可以操作';
            $tType = 'warning';
        }else{
            $std = Db::name('apply_student_'.$cid)->where($map)->paginate();
            $tips = '可以在下方管理学生,当前累计报名'.count($std).'人';
            $tType = 'info';
        }


        $btn = [
            'title' => '导出报名表',
            'class' => 'btn btn-default',
            'icon'  => 'fa fa-fw fa-download',
            'href'  => url('export', ['mid' => $cid])
        ];

        $btn_export = [
            'title' => '打包所有图像',
            'class' => 'btn btn-default',
            'icon'  => 'fa fa-fw fa-file-zip-o',
            'href'  => url('downloadImages', ['mid' => $cid])
        ];

        return Zb::make('table')
            ->setTabNav($tabs, $cid)
            ->setPageTips($tips, $tType)
            ->addColumns($cols)
          	->setPrimaryKey('ID')
            ->setTableName('apply_student_'.$cid)
            ->addFilter('status', $sta)
            ->setRowList($std)
            ->setSearch($sr)
            ->addTopButtons('delete')
            ->addTopButton('custom', $btn)  
            ->addTopButton('custom', $btn_export)                                
            ->fetch();
    }

    public function export($mid = null) {
        $aids = Ut::getAdminModel()['ids']; 
        if(empty($aids)) $this->error('当前没有模型');                 
        if($mid != null) {
            if(!in_array($mid, $aids)) $this->error('非法操作，你无权管理该模型');
            $cid = $mid;
        }else{
            $this->error('不能直接访问该网页');
        }
        //cid为当前需要操作的id
        //生成表头
        $std = Db::name('apply_student_'.$cid)->select();
        $i = 0;
        foreach($std[0] as $key => $val) {
            $cellName[$i++] = [$key, 'auto', $key];
        }
        // 调用插件（传入插件名，[导出文件名、表头信息、具体数据]）
        plugin_action('Excel/Excel/export', ['apply', $cellName, $std]);
    }

    public function downloadImages($mid = null) {
        $aids = Ut::getAdminModel()['ids']; 
        if(empty($aids)) $this->error('当前没有模型');                 
        if($mid != null) {
            if(!in_array($mid, $aids)) $this->error('非法操作，你无权管理该模型');
            $cid = $mid;
        }else{
            $this->error('不能直接访问该网页');
        }
        $model = Ut::getModelSetting($cid);
        if($model['lockquestion'] == 0 || $model['needfile'] == 0) $this->error('当前不支持该操作'); 
        set_time_limit(0);
        $name = '学生图像_'.$cid.'.zip';
        $img_dir = APP_PATH.'/apply/studentimgs/'.$cid;
        $archive = new PHPZip;
        return $archive->ZipAndDownload($img_dir, $name);
    }

    public function showImg($mid = null, $openid = null) {
        if($mid == null || $openid == null) $this->error('非法操作');
        $aids = Ut::getAdminModel()['ids']; 
        if(empty($aids)) $this->error('当前没有模型');                 
        if($mid != null) {
            if(!in_array($mid, $aids)) $this->error('非法操作，你无权管理该模型');
            $cid = $mid;
        }else{
            $this->error('不能直接访问该网页');
        }

        $model = Db::name('apply_model')->where('ID', $mid)->select();
        if($model[0]['needfile'] == 0) $this->error('当前模型不允许学生上传图片');
        $student = Db::name('apply_student_'.$mid)->where('openid', $openid)->select();
        if(!$student) $this->error('学生openid错误');
        $file = $student[0]['file'];
        $data = file_get_contents(APP_PATH.'/apply/studentimgs/'.$mid.'/'.$file);
        header("Content-Type: image/jpeg;text/html; charset=utf-8");
        echo $data;
        exit;
    }

    
    public function viewinfo($mid = null, $act = null) {
        $info = Ut::getAdminModel()['info'];

        if($act == 'getQus') {
            $qs = Db::name('apply_question')->where('mid', $mid)->select();
            if(empty($qs)) return json(['code' => 0, 'msg' => '找不到模型对应的问题']);

            $qqs = []; $idx = 0;

            foreach($qs as $i) $qqs[$idx++] = ['key' => $i['name'], 'value' => $i['name']];

            return json(['code' => '1', 'msg' => 'ok', 'list' => $qqs]);
        }
        if($this->request->isPost()) {
            $mid = input('post.mid');
            $aids = Ut::getAdminModel()['ids'];   
            if(empty($aids)) $this->error('当前没有模型');                 
            if($mid != null) {
                if(!in_array($mid, $aids)) $this->error('非法操作，你无权管理该模型');
                $cid = $mid;
            }else{
                $this->error('不能直接访问该网页');
            }
            //cid为当前需要操作的id 

            if(empty(input('post.key')) || empty(input('post.val'))) $this->error('非法操作，信息请填写完整');
            @$students = Db::name('apply_student_'.$cid)->where(input('post.key'), 'like', '%'.input('post.val').'%')->select();
            if(empty($students)) $this->error('找不到学生');
            $std = $students[count($students) - 1];
            ///学生构建器
            $idx = 0;
            foreach($std as $k => $v) {
                $stdd[$idx++] = [
                    'static', $k, $k, '学生提交的'.$k
                ];
            }
            //状态构建
            $sts = [
                '-1' => '报名终止',
                '0' => '已完成报名',
                '1' => '审核通过',
                '2' => '面试时间确定',
                '3' => '面试已签到',
                '4' => '终审通过'
            ];

            //重建部分学生构建器
            $rbt = [
                ['static', 'ID', '报名序号'],
                ['static', 'openid', '微信侧身份特征码'],
                ['datetime', 'create_time', '报名时间', '', '尚未确定', '', 'readonly'],
                ['static', 'score', '成绩'],
                ['static', 'intervtime', '选定面试时间'],
                ['datetime', 'checkin_time', '签到时间', '', '尚未确定', '', 'readonly'],
                ['datetime', 'update_time', '更新时间', '', '尚未确定', '', 'readonly'],
                ['static', 'status', '当前状态'],
                ['wangeditor', 'file', '上传文件']
            ];

            //重建
            foreach($rbt as $k => $v) {
                $stdd[$k] = $v;
               
            }
//var_dump($stdd);exit;
            $std['file'] = "<img src=\"".url('showimg', ['mid' => $cid, 'openid' => $std['openid']])."\" />";


            $std['status'] = $sts[$std['status']];
            return Zb::make('form')
                ->addFormItems($stdd)
                ->setFormData($std)
                ->hideBtn('submit')
                ->fetch();
        }
        
        return Zb::make('form')
            ->addFormItems([
                ['linkage', 'mid', '查看模型', '', $info, '', url('viewinfo', ['act' => 'getQus']), 'key'],
                ['select', 'key', '查找索引', '', []],
                ['text', 'val', '查找关键字']
            ])
            ->isAjax(false)
            ->setPageTips('请注意：该功能仅限查找报名表进行查看，请尽量使用唯一字段进行查找，如学号，身份证号等，如果查找引擎查找到不止一个结果则只能显示最后一条记录')
            ->fetch();
    }
  //  public function quickedit($record = []) {
//	$this->success('ok');
    //}
}
