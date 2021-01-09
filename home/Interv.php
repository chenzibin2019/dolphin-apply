<?php  
namespace app\apply\home;

use app\index\controller\Home;
use think\Db;
use think\Cookie;
use app\admin\controller\Attachment as At;

use app\apply\Utils\Utils as Ut;
use app\apply\Utils\Jssdk as Sdk;

class Interv extends Home {
    public function confirmTime($mid = null, $veri = 0, $openid = null  ) {
        if($mid === null) $this->error('非法操作');
        //模型检验
        $model = Ut::getModelSetting($mid);
        $ret = urlencode('https://bkzspy.sdnu.edu.cn/');
        $ret = urlencode(Ut::domain());
        $res = urlencode(json_encode(['url' => 'apply/interv/confirmTime', 'mid' => $mid]));
        if($veri == 0) $this->redirect("https://mp.weixin.qq.com/mp/subscribemsg?action=get_confirm&appid={$model['appid']}&scene=914&template_id={$model['template_id']}&redirect_url={$ret}&reserved={$res}#wechat_redirect");                    
        if(!$model) $this->error('参数错误');
        //没有锁定问题
        if($model['lockquestion'] == 0) $this->error('模型未锁定问题，不可以报名');
        $student = Db::name('apply_student_'.$mid)->where('openid', $openid)->select();
        if(empty($student)) $this->error('报名信息不存在');
        $this->assign('status', $student[0]['status']);
        $this->assign('mid', $mid);
        $this->assign('jsapi', (new Sdk($mid))->getSignPackage());
        session('openid_'.$mid, $openid);
        if( $student[0]['status'] == 1 && $model['allowchoosetime'] == 1) {
            //选择
            $intervtimes = Db::name('apply_intervtime')->where('mid', $mid)->where('status', 1)->order('order,id')->select();
            foreach($intervtimes as $key => $val) {
                //检查时场次是否报满
                $curr = count(Db::name('apply_student_'.$mid)->where('intervtime', $val['name'])->select());
                if($curr >= $val['maxstudent'] && $val['maxstudent'] != -1) {
                    $intervtimes[$key]['enabled'] = false;
                }else {
                    $intervtimes[$key]['enabled'] = true;
                    if($val['maxstudent'] != -1) {
                        $intervtimes[$key]['remain'] = '当前场次余量：'.($val['maxstudent']-$curr).'人';
                    }else{
                        $intervtimes[$key]['remain'] = '当前场次余量充足';
                    }
                }
            }
            $this->assign('interv', $intervtimes);
            return $this->fetch('select');
        }else{
            if(empty($student[0]['intervtime'])) $this->error('你当前还未被分配面试时间！');
            $this->assign('intervtime', $student[0]['intervtime']);
            return $this->fetch('confirm');
        }

    }

    public function confirm_do($mid = null) {
        if($mid === null) $this->error('非法操作');
        //模型检验
        $model = Ut::getModelSetting($mid);
        if(!$model) $this->error('参数错误');
        //没有锁定问题
        if($model['lockquestion'] == 0) $this->error('模型未锁定问题，不可以报名');
        $student = Db::name('apply_student_'.$mid)->where('openid', session('openid_'.$mid))->select();
        if(empty($student)) $this->error('报名信息不存在');
        if($model['allowchoosetime'] == 1 ) {
            //选择
            $this->error('模型要求你选择一个面试时间，请重新打开该网页');
        }else{
            Db::name('apply_student_'.$mid)->where('openid', session('openid_'.$mid))->update([
                'status' => 2,
                'update_time' => time()
            ]);
            $student = Db::name('apply_student_'.$mid)->where('openid', session('openid_'.$mid))->select();
            if($student[0]['has_ims_acc'] == 0) {
            	$this->write_ims($mid, $student[0]['id'], 2);
            }
            $this->success('你已成功确认面试时间，请记得按时参加面试', url('weentrance/applycallback', ['openid' => session('openid_'.$mid), 'mid' => $mid]));
        }
    }

    public function select_do($mid = null) {
        $interv = input('post.interv');
        if($mid === null) $this->error('非法操作');
        //模型检验
        $model = Ut::getModelSetting($mid);
        if(!$model) $this->error('参数错误');
        //没有锁定问题
        if($model['lockquestion'] == 0) $this->error('模型未锁定问题，不可以报名');
        $student = Db::name('apply_student_'.$mid)->where('openid', session('openid_'.$mid))->select();
        if(empty($student)) $this->error('报名信息不存在');
        if($model['allowchoosetime'] == 0 ) {
            //选择
            $this->error('你无权选择面试时间');
        }
        if($student[0]['status'] != 1) $this->error('状态错误');
        //检查所提供的interv存在
        $intervtime = Db::name('apply_intervtime')->where('mid', $mid)->where('status', 1)->where('name', $interv)->select();
        if(!$intervtime) $this->error('你选择的面试时间不正确'.$mid.$interv);
        //检查是否已经报满
        $curr = count(Db::name('apply_student_'.$mid)->where('intervtime', $interv)->select());
        if($curr >= $intervtime[0]['maxstudent'] && $intervtime[0]['maxstudent'] != -1) {
            $this->error('当前场次已经报满，请返回重选');
        }else {
            //选择面试时间
            Db::name('apply_student_'.$mid)->where('openid', session('openid_'.$mid))->update([
                'intervtime' => $interv,
                'status' => 2,
                'update_time' => time()
            ]);
            $student = Db::name('apply_student_'.$mid)->where('openid', session('openid_'.$mid))->select();
            if($student[0]['has_ims_acc'] == 0) {
            	$this->write_ims($mid, $student[0]['id'], 2);
            }
            $this->success('你已经成功选择面试时间', url('weentrance/applycallback', ['openid' => session('openid_'.$mid), 'mid' => $mid]));
        }
        
    }

    public function check_in($mid = null) {
        if($mid == null) $this->error('入口不合法');
        $model = Ut::getModelSetting($mid);
        if(!$model) $this->error('参数错误');
        if(Cookie::has('openid_'.$mid)) $this->redirect(url('check_incallback', ['openid' => Cookie::get('openid_'.$mid), 'mid' => $mid]));
        //访问微信授权页面
        $redirect = urlencode(Ut::domain().'apply/weentrance/applycallback');
        $this->redirect("https://open.weixin.qq.com/connect/oauth2/authorize?appid={$model['appid']}&redirect_uri={$redirect}&response_type=code&scope=snsapi_base&state={$mid}#wechat_redirect");
    }

    public function check_incallback($openid = null, $mid = null) {
        if($mid === null) $mid = input('get.state');
        //模型检验
        $model = Ut::getModelSetting($mid);
        if(!$model) $this->error('参数错误');
        //没有锁定问题
        if($model['lockquestion'] == 0) $this->error('模型未锁定问题，不可以报名');
        //检查是否超过时间
        //if(time()>$model['applyendtime'] || time()<$model['applystarttime']) $this->error('当前不在报名时间内');
        //检查是否已禁用
        if($model['status'] == 0) $this->error('系统暂时关闭，后台数据处理中，请耐心等待');
        if($openid === null) {
            if(empty('get.code') || empty('get.state')) $this->error('请通过合法入口访问本系统');
            $code = input('get.code');
            //$mid = input('get.state');
            //获取openid
            $userinfo = json_decode(file_get_contents("https://api.weixin.qq.com/sns/oauth2/access_token?appid={$model['appid']}&secret={$model['appsecret']}&code={$code}&grant_type=authorization_code"), true);
            if(empty($userinfo['openid'])) $this->error('正在引导授权页，如果持续出现该问题，请联系管理员:'.$userinfo['errmsg'], url('applyindex', ['mid' => $mid]));
            $openid = $userinfo['openid'];
            Cookie::set('openid_'.$mid, $openid, 30*24*60*60);
        }
        //获取数据库中的学生数据
        $student = Db::name('apply_student_'.$mid)->where('openid', $openid)->select();
        if(!$student) {
            //没有报名过
            $this->error('你还没有报名过，不能进行签到');
        }else {
            //已经报名
            $status = $student[0]['status'];
            if($status !=  2) $this->error('你当前的状态不支持签到');
            Db::name('apply_student_'.$mid)->where('openid', $openid)->update([
                'checkin_time' => time(),
                'status' => 3,
                'update_time' => time()
            ]);
            $student = Db::name('apply_student_'.$mid)->where('openid', $openid)->select();
            if($student[0]['has_ims_acc'] == 0) {
            	$this->write_ims($mid, $student[0]['id'], 3);
            }
            $this->success('你已经成功签到', url('weentrance/applycallback', ['openid' => session('openid_'.$mid), 'mid' => $mid]));
        }
    }

    public function test() {
       var_dump(dp_send_message('11', '22', 1));
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
