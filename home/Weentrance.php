<?php 
namespace app\apply\home;

use app\index\controller\Home;
use think\Db;
use think\Cookie;
use think\Cache;

use app\apply\Utils\Utils as Ut;
use app\apply\Utils\Jssdk as Sdk;

class Weentrance extends Home {
    public function applyindex($mid = null) {
        if($mid == null) $this->error('入口不合法');
        $model = Ut::getModelSetting($mid);
        if(!$model) $this->error('参数错误');
        if(Cookie::has('openid_'.$mid)) $this->redirect(url('applycallback', ['openid' => Cookie::get('openid_'.$mid), 'mid' => $mid]));
        //访问微信授权页面
        $redirect = urlencode(Ut::domain().'apply/weentrance/applycallback');
        $this->redirect("https://open.weixin.qq.com/connect/oauth2/authorize?appid={$model['appid']}&redirect_uri={$redirect}&response_type=code&scope=snsapi_base&state={$mid}#wechat_redirect");
    }

    public function applycallback($openid = null, $mid = null) {
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
        //检查是否超过最大报名数
        //if($model['maxstudent'] != -1) 
            //if(count(Db::name('apply_student_'.$mid)->select()) >= $model['maxstudent']) $this->error('学生人数已经达到上限，不可以继续报名，请联系组织方申请扩容');
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
            $status = -2;
            $student_name = '';
        }else {
            //已经报名
            $status = $student[0]['status'];
            $student_name = empty($student[0]['姓名'])?'':$student[0]['姓名'];
        }
        if($status == -1) $this->error('抱歉你没有通过审核，报名终止!');
        $this->assign('name', $student_name);
	$this->assign('model', $model['name']);
        $this->assign('status', $status);
        $this->assign('mid', $mid);
        $this->assign('jsapi', (new Sdk($mid))->getSignPackage());        
        session('openid_'.$mid, $openid);
        return $this->fetch('index');
    }


    public function domain() {
        echo Ut::domain();
    }

    public function notice($mid = null) {
        if($mid == null) $this->error('入口不合法');
        $model = Ut::getModelSetting($mid);
        if(!$model) $this->error('参数错误');
        $this->assign('notice', $model['notice']);
        $this->assign('mid', $mid);
        Cookie::set('readnotice_'.$mid, 'ok', 30*24*60*60);
        return $this->fetch('notice');
    }

    public function a() {
        Cookie::clear();
      	Cache::clear();
        return 'ok';
    }

    public function wipeCache($mid = null) {
        if($mid == null) $this->error('入口不合法');
        $model = Ut::getModelSetting($mid);
        if(!$model) $this->error('参数错误');
        $re = Ut::getAccessToken($model['appid'], $model['appsecret'], 'weserver', 'm'.$mid)['errcode'];        
        if($re != 0) $this->error('重新获取access_token出错！');
        Cache::clear('jsapi_'.$mid);
        $this->success('已经重新分配access_token;清空jsapi签名成功');
    }

    public function exitHere($mid = null) { 
        if($mid == null) $this->error('入口不合法');
        $model = Ut::getModelSetting($mid);
        if(!$model) $this->error('参数错误');
        $this->assign('jsapi', (new Sdk($mid))->getSignPackage());      
        return $this->fetch('exitpage');
    }
}
