<?php
namespace app\apply\admin;

use app\admin\controller\Admin;
use app\common\builder\ZBuilder as Zb;
use think\Db;
use app\apply\Utils\Utils as Ut;
use app\apply\Utils\IMS;

class Manage extends Admin {
    public function auth($mid = null) {
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
            $tabs[$key] = ['title' => $val, 'url' => url('auth', ['mid' => $key])];
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
            ['openid', '微信识别码'],
            ['create_time', '报名时间', 'datetime'],
            ['intervtime', '面试时间', 'text.edit'],
            ['checkin_time', '签到时间', 'datetime'],
            ['status', '状态', 'select', $sta],
            ['update_time', '最后更新时间', 'datetime'],
            ['file', '附件', 'link', url('student/showImg', ['mid' => $mid, 'openid' => '__openid__']), '_blank', 'pop']            
        ];

        //写入问卷
        $sr = [];
        foreach($qs as $key => $val) {
            $cols[$key+7] = [$val['name'], $val['name'], 'text.edit'];
            $sr[$val['name']] = $val['name'];
        }

        //只有锁定问题才拉取学生数据，否则直接输出空白
        $minfo = Db::name('apply_model')->where('id', $cid)->select();
        $m = $minfo[0];
        if($m['lockquestion'] == 0){
            $std = [];
            $tips = '注意，当前问卷尚未锁定，无法进行初审，请联系管理员进行锁定，然后才可以进行纳新控制.';
            $tType = 'warning';
        }else{
            $map = $this->getMap();
            $std = Db::name('apply_student_'.$cid)->where('status', 0)->where($map)->paginate();
            $tips = '可以在下方进行初审，注意，审核后不可以撤回，在此处仅可处理已报名且没有审核过的学生，如果需要撤回操作，请联系管理员使用纳新-学生管理直接修改学生当前状态';
            $tType = 'info';
        }

        $btn_auth = [
            'title' => '审核通过',
            'icon' => 'fa fa-fw fa-check',
            'class' => 'btn btn-primary js-get',
            'href' => url('auth_do', ['do' => '1', 'mid' => $cid])
        ];

        $btn_auth_fail = [
            'title' => '审核不通过',
            'icon' => 'fa fa-fw fa-remove',
            'class' => 'btn btn-warning js-get',
            'href' => url('auth_do', ['do' => '-1', 'mid' => $cid])
        ];

        return Zb::make('table')
            ->setTabNav($tabs, $cid)
            ->setPageTips($tips, $tType)
            ->addColumns($cols)
            ->setRowList($std)
            ->addTopButton('custom', $btn_auth)
            ->addTopButton('custom', $btn_auth_fail)            
            ->setPrimaryKey('ID')
            ->setSearch($sr)
            ->setTableName('apply_student_'.$cid)
            ->fetch();
    }

    public function auth_do($mid = null, $do = '-1') {
        $ids = explode(',', input('get.ids'));
        $aids = Ut::getAdminModel(true)['ids'];
        //if(!in_array($mid, $aids)) $this->error('模型不存在或者不允许审核');
        foreach($ids as $key => $val) {
            Db::name('apply_student_'.$mid)->where('id', $val)->update(['status' => $do, 'update_time' => time()]);
            $student = Db::name('apply_student_'.$mid)->where('id', $val)->select();
            if($student && $student[0]['has_ims_acc'] == 0) {
            	$this->write_ims($mid, $val, (int) $do);
            }
        }
        $this->success('处理成功');
    }

    public function setinterv_pre($mid = null) {
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
            $tabs[$key] = ['title' => $val, 'url' => url('setinterv_pre', ['mid' => $key])];
        }
        //获取问题
        $qs = Db::name('apply_question')->where('mid', $cid)->select();  

        $cols = [
            ['openid', '微信识别码'],
            ['create_time', '报名时间', 'datetime'],
            ['intervtime', '面试时间', 'text.edit'],
            ['checkin_time', '签到时间', 'datetime'],
            ['update_time', '最后更新时间', 'datetime'],
            ['file', '附件', 'link', url('student/showImg', ['mid' => $mid, 'openid' => '__openid__']), '_blank', 'pop']                           
        ];

        //写入问卷
        $sr = [];
        foreach($qs as $key => $val) {
            $cols[$key+7] = [$val['name'], $val['name'], 'text.edit'];
            $sr[$val['name']] = $val['name'];
        }            

        $btn_set = [
            'title' => '对选中的学生分配面试时间',
            'icon' => 'fa fa-fw fa-clock-o',
            'class' => 'btn btn-primary js-get',
            'href' => url('setinterv', ['mid' => $cid])
        ];

        //只有锁定问题才拉取学生数据，否则直接输出空白
        $minfo = Db::name('apply_model')->where('id', $cid)->select();
        $m = $minfo[0];
        if($m['lockquestion'] == 0){
            $std = [];
            $tips = '注意，当前问卷尚未锁定，无法进行初审，请联系管理员进行锁定，然后才可以进行纳新控制';
            $tType = 'warning';
        }else{
            $std = Db::name('apply_student_'.$cid)->where('status', 1)->where($map)->paginate();
            $tips = '可以在下方分配面试时间地点，分配后将会邀请学生进行确认，需要学生自选的无需执行此步骤';
            $tType = 'info';
        }

        return Zb::make('table')
            ->setTabNav($tabs, $cid)
            ->setPageTips($tips, $tType)
            ->addColumns($cols)
            ->setRowList($std)      
            ->addTopButton('custom', $btn_set)  
            ->setPrimaryKey('ID')
            ->setSearch($sr)
            ->setTableName('apply_student_'.$cid)
            ->fetch();

    }

    public function setinterv($mid = null) {
        $aids = Ut::getAdminModel()['ids'];   
        if(empty($aids)) $this->error('当前没有模型');                 
        if($mid != null) {
            if(!in_array($mid, $aids)) $this->error('非法操作，你无权管理该模型');
            $cid = $mid;
        }else{
            $this->error('不能直接访问该网页');
        }
        //cid为当前需要操作的id
        if($this->request->isPost()) {
            //保存
            $max_student = Db::name('apply_intervtime')->where('id', input('post.intervtime'))->where('status', 1)->select();
            if(!$max_student) $this->error('面试时间不存在');
            //检查面试时间是否已用尽
            $currentStd = count(Db::name('apply_student_'.$cid)->where('intervtime', $max_student[0]['name'])->select());
            $toinsert = explode(',', input('post.ids'));
            if($max_student[0]['maxstudent'] <= $currentStd + count($toinsert)) $this->error('当前面试场次已经达到最大学生');
            foreach($toinsert as $key => $val) {
                Db::name('apply_student_'.$cid)->where('ID', $val)->update([
                    'intervtime' => $max_student[0]['name'],
                    'update_time' => time()
                ]);
            }
            $this->success('操作成功!');
        }
        $interv = Db::name('apply_intervtime')->where('status', 1)->where('mid', $mid)->select();

        $intervtimes = [];
        foreach($interv as $key => $val) {
            $intervtimes[$val['ID']] = $val['name'];
        }

        return Zb::make('form')
            ->addFormItems([
                ['hidden', 'ids', input('get.ids')],
                ['select', 'intervtime', '指定的面试时间', '', $intervtimes]
            ])
            ->setPageTitle('分配面试时间地点')
            ->fetch();
    }

    public function check_in($mid = null) {
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
        $sr = [];
        foreach($info as $key => $val) {
            $tabs[$key] = ['title' => $val, 'url' => url('check_in', ['mid' => $key])];
        }
        //生成二维码
        
        $qrcode[0] = ['qrcode' => '<img src="https://sapi.k780.com/?app=qr.get&data=https://app.zsb.sdnuxmt.cn/apply/interv/check_in/mid/'.$cid.'&level=L&size=6" />'];

        return Zb::make('table')
            ->hideCheckbox()
            ->addColumn('qrcode', '请使用报名时的微信扫码签到')
            ->setRowList($qrcode)
            ->setTabNav($tabs, $cid)            
            ->fetch();
    }

    public function input_result($mid = null) {
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
            $tabs[$key] = ['title' => $val, 'url' => url('input_result', ['mid' => $key])];
        }
        //获取问题
        $qs = Db::name('apply_question')->where('mid', $cid)->paginate();  

        $sta = [
            '-1' => '报名终止',
            '0' => '已完成报名',
            '1' => '审核通过',
            '2' => '面试时间确定',
            '3' => '面试已签到',
            '4' => '终审通过'
        ];

        $cols = [
            ['openid', '微信识别码'],
            ['create_time', '报名时间', 'datetime'],
            ['intervtime', '面试时间', 'text.edit'],
            ['checkin_time', '签到时间', 'datetime'],
            ['status', '状态', 'select', $sta],
            ['update_time', '最后更新时间', 'datetime'],
            ['file', '附件', 'link', url('student/showImg', ['mid' => $mid, 'openid' => '__openid__']), '_blank', 'pop']                            
        ];

        //写入问卷
        $sr = [];
        foreach($qs as $key => $val) {
            $cols[$key+7] = [$val['name'], $val['name'], 'text.edit'];
            $sr[$val['name']] = $val['name'];
        }

        //只有锁定问题才拉取学生数据，否则直接输出空白
        $minfo = Db::name('apply_model')->where('id', $cid)->select();
        $m = $minfo[0];
        if($m['lockquestion'] == 0){
            $std = [];
            $tips = '注意，当前问卷尚未锁定，无法进行操作，请联系管理员进行锁定，然后才可以进行纳新控制';
            $tType = 'warning';
        }else{
            $map = $this->getMap();
            $std = Db::name('apply_student_'.$cid)->where('status', 3)->where($map)->select();
            $tips = '可以在下方输入最终结果，注意，输入后不可以撤回，在此处仅可处理面试已签到的学生，如果需要撤回操作，请联系管理员使用纳新-学生管理直接修改学生当前状态';
            $tType = 'info';
        }

        $btn_auth = [
            'title' => '终审通过',
            'icon' => 'fa fa-fw fa-check',
            'class' => 'btn btn-primary js-get',
            'href' => url('result_do', ['do' => '4', 'mid' => $cid])
        ];

        $btn_auth_fail = [
            'title' => '终审不通过',
            'icon' => 'fa fa-fw fa-remove',
            'class' => 'btn btn-warning js-get',
            'href' => url('result_do', ['do' => '-1', 'mid' => $cid])
        ];

        return Zb::make('table')
            ->setTabNav($tabs, $cid)
            ->setPageTips($tips, $tType)
            ->addColumns($cols)
            ->setRowList($std)
            ->addTopButton('custom', $btn_auth)
            ->addTopButton('custom', $btn_auth_fail)            
            ->setPrimaryKey('ID')
            ->setSearch($sr)
            ->setTableName('apply_student_'.$cid)
            ->fetch();
    }

    public function result_do($mid = null, $do = '-1') {
        $ids = explode(',', input('get.ids'));
        $aids = Ut::getAdminModel(true)['ids'];
        //if(!in_array($mid, $aids)) $this->error('模型不存在或者不允许审核');
        foreach($ids as $key => $val) {
            Db::name('apply_student_'.$mid)->where('id', $val)->update(['status' => $do, 'update_time' => time()]);
            $student = Db::name('apply_student_'.$mid)->where('id', $val)->select();
            if($student && $student[0]['has_ims_acc'] == 0) {
            	$this->write_ims($mid, $val, (int) $do);
            }
        }
        $this->success('处理成功');
    }

    public function wx_notice($mid = null) {
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
            $tabs[$key] = ['title' => $val, 'url' => url('wx_notice', ['mid' => $key])];
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
            ['openid', '微信识别码'],
            ['create_time', '报名时间', 'datetime'],
            ['intervtime', '面试时间', 'text.edit'],
            ['checkin_time', '签到时间', 'datetime'],
            ['status', '状态', 'select', $sta],
            ['update_time', '最后更新时间', 'datetime'],
            ['file', '附件', 'link', url('student/showImg', ['mid' => $mid, 'openid' => '__openid__']), '_blank', 'pop']                           
        ];

        //写入问卷
        $sr = [];
        foreach($qs as $key => $val) {
            $cols[$key+7] = [$val['name'], $val['name'], 'text.edit'];
            $sr[$val['name']] = $val['name'];
        }

        //只有锁定问题才拉取学生数据，否则直接输出空白
        $minfo = Db::name('apply_model')->where('id', $cid)->select();
        $m = $minfo[0];
        if($m['lockquestion'] == 0){
            $std = [];
            $tips = '注意，当前问卷尚未锁定，无法进行操作，请联系管理员进行锁定，然后才可以进行纳新控制';
            $tType = 'warning';
        }else{
            $map = $this->getMap();
            $std = Db::name('apply_student_'.$cid)->where('status', 'in', [-1, 1, 4])->where($map)->paginate();
            $tips = '注意，受到微信限制，提交成功的模版消息（一次性订阅）只能在特定时间使用，请勿滥用，具体请咨询管理员，你只可以对状态为审核通过的学生下发面试时间选择/确认的模版消息、对状态为终审通过的学生下发录用通知或对审核不通过的学生下发不予录用通知（由于导致审核不通过的原因很多，该功能建议慎用)，提交时无需选择消息类型，系统将会自动选择，该功能仅限绑定了微信公众平台且公众平台已认证的模型使用，若模型不满足条件，提交将会失败';
            $tType = 'info';
        }

        $btn_send = [
            'title' => '下发消息',
            'icon' => 'fa fa-fw fa-cloud-upload',
            'class' => 'btn btn-warning js-get',
            'href' => url('msg_do', ['mid' => $cid])
        ];


        return Zb::make('table')
            ->setTabNav($tabs, $cid)
            ->setPageTips($tips, $tType)
            ->addColumns($cols)
            ->setRowList($std)
            ->addTopButton('custom', $btn_send)  
            ->addFilter('status', $sta)      
            ->setPrimaryKey('ID')
            ->setSearch($sr)
            ->setTableName('apply_student_'.$cid)
            ->fetch();
    }

    public function msg_do($mid = null) {
        $aids = Ut::getAdminModel()['ids'];   
        if(empty($aids)) $this->error('当前没有模型');                 
        if($mid != null) {
            if(!in_array($mid, $aids)) $this->error('非法操作，你无权管理该模型');
            $cid = $mid;
        }else{
            $this->error('不能直接访问该网页');
        }
        //cid为当前需要操作的id
        //获取详细信息

        $model = Ut::getModelSetting($cid);
        $model['intervtemplate'] = strip_tags($model['intervtemplate']);
        $model['resulttemplate'] = strip_tags($model['resulttemplate']);
        $model['notemplate'] = strip_tags($model['notemplate']);
        
        $acc = Ut::getAccessToken($model['appid'], $model['appsecret'])['token'];
        $template_id = $model['template_id'];
        $uids = explode(',', input('get.ids'));

        foreach($uids as $key => $val) {
            $student = Db::name('apply_student_'.$cid)->where('ID', $val)->select();
            if(!$student) continue;
            $openid = $student[0]['openid'];
            $status = $student[0]['status'];

            switch($status) {
                case -1: {
                    $title = '结果通知';
                    $content = empty($model['notemplate'])?'同学你好，经过层层筛选，你最终的录用结果为：不予录用，请悉知！':$model['notemplate'];
                    $turl = '';
                    break;
                }
                case 1: {
                    $title = '面试通知';
                    $content = empty($model['intervtemplate'])?'同学你好，恭喜你通过网上初审，进入面试阶段，根据系统规定，你需要进行面试时间的选择，请你点击详情链接，选择你合适的面试时间，然后提交，我们会按照你的选择给你安排面试，请注意一旦选定面试时间后将不可更改，':$model['intervtemplate'];
                    $turl = 'https://app.zsb.sdnuxmt.cn'.home_url('apply/interv/confirmTime', ['mid' => $cid]);
                    break;
                }
                case 4: {
                    $title = '结果通知';
                    $content = empty($model['resulttemplate'])?'同学你好，经过层层筛选，你最终的录用结果为：录用，请悉知，后续问题管理团队会跟你取得联系！':$model['resulttemplate'];
                    $turl = '';
                    break;
                }
            }

            //下发
            $url = 'https://api.weixin.qq.com/cgi-bin/message/template/subscribe?access_token='.$acc;
            $data = Array(
                'touser' => $openid,
                'template_id' => $template_id,
                'scene' => 914,
                'title' => $title,
                'data' => Array(
                    'content' => Array(
                        'value' => $content,
                        'color' => '#0000FF'
                    )
                )
            );

            if($turl != '') $data['url'] = $turl;

            $ret = https_request($url, json_encode($data, JSON_UNESCAPED_UNICODE));
            //action_log('send_template_msg', 'apply_manage', $val, is_signin(), '用户ID'.$val.'return:'.$ret);        
        }
        $this->success('提交成功');
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
            $students = Db::name('apply_student_'.$cid)->where(input('post.key'), input('post.val'))->select();
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
                ['static', 'file', '上传文件名', '此处不支持查看该内容，请到学生管理查看']
            ];

            //重建
            foreach($rbt as $k => $v) {
                $stdd[$k] = $v;
            }
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

	private function write_ims($mid, $uid, $level) {
		// 1 审核通过  2 面试时间确定  3 面试以签到 4 审核通过
		$auth_level = Ut::getModelSetting($mid);
		if(!$auth_level) return;
		if($level >= $auth_level['ims_min_status']) {
			$content = (new IMS($mid, $uid, 'add'))->getIMSFileContent();
			if($content) file_put_contents(APP_PATH.'/apply/IMS/'.$mid.'.'.$uid.'.ims', $content);
		} else {
			return;
		}		
	}
}