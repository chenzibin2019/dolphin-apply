<?php  
namespace app\apply\home;

use app\index\controller\Home;
use think\Db;
use think\Cookie;
use app\admin\controller\Attachment as At;

use app\apply\Utils\Utils as Ut;
use app\apply\Utils\Jssdk as Sdk;
use app\apply\Utils\IMS;

class Studentinput extends Home {
    public function questiontable($mid = null, $veri = 0) {
        if($mid === null) $this->error('非法操作');
        //模型检验
        $model = Ut::getModelSetting($mid);
        $ret = urlencode('https://bkzspy.sdnu.edu.cn/');
        $ret = urldecode(Ut::domain());
        $res = urlencode(json_encode(['url' => 'apply/studentinput/questiontable', 'mid' => $mid]));
        if(!$model) $this->error('参数错误');
        //没有锁定问题
        if($model['lockquestion'] == 0) $this->error('模型未锁定问题，不可以报名');
        $student = Db::name('apply_student_'.$mid)->where('openid', session('openid_'.$mid))->select();
        $question = Db::name('apply_question')->where('mid', $mid)->order('orders,id')->select();        
        if(empty($student)) {
            //填表
            if(!Cookie::has('readnotice_'.$mid)) $this->redirect('weentrance/notice', ['mid' => $mid]);
            $this->assign('title', '填写报名表');
            $this->assign('action', 1);
            if($model['maxstudent'] != -1) 
                if(count(Db::name('apply_student_'.$mid)->select()) >= $model['maxstudent']) $this->error('学生人数已经达到上限，不可以继续报名，请联系组织方申请扩容');
            //写入空数据
            $student = Array();
            foreach($question as $key => $val) 
                $student[$val['name']] = '';
            $this->assign('student', $student);
            if($veri == 0) $this->redirect("https://mp.weixin.qq.com/mp/subscribemsg?action=get_confirm&appid={$model['appid']}&scene=914&template_id={$model['template_id']}&redirect_url={$ret}&reserved={$res}#wechat_redirect");            
        }else {
            //查看问卷
            $this->assign('student', $student[0]);
            $this->assign('title', '查看报名表');
            $this->assign('action', 0);
        }
        //获取问卷表
        if(!$question) $this->error('数据异常');
        foreach($question as $key => $val) {
            $question[$key]['options'] = explode(',', $val['options']);
        }
        $sn = (new Sdk($mid))->getSignPackage();
        $this->assign('questions', $question);
        $this->assign('name', $model['name']);
        $this->assign('jsapi', $sn);
        $this->assign('apidebug', json_encode($sn));
        $this->assign('mid', $mid);
        $this->assign('hasimg', $model['needfile']);
        return $this->fetch('index');

    }

    public function submitApply($mid = null) {
        header("Content-type: text/html; charset=utf-8");        
        if($mid === null) $this->error('非法操作');
        //模型检验
        $model = Ut::getModelSetting($mid);
        if(!$model) $this->error('参数错误');
        //没有锁定问题
        if($model['lockquestion'] == 0) $this->error('模型未锁定问题，不可以报名');
        $student = Db::name('apply_student_'.$mid)->where('openid', session('openid_'.$mid))->select();
        //检查是否超过时间
        if(time()>$model['applyendtime'] || time()<$model['applystarttime']) $this->error('当前不在报名时间内');
        //检查是否已禁用
        if($model['status'] == 0) $this->error('系统暂时关闭，后台数据处理中，请耐心等待');
        //检查是否超过最大报名数
        if($model['maxstudent'] != -1) 
            if(count(Db::name('apply_student_'.$mid)->select()) >= $model['maxstudent']) $this->error('学生人数已经达到上限，不可以继续报名，请联系组织方申请扩容');
        //检查是不是所有的问题都回答了
        $question = Db::name('apply_question')->where('mid', $mid)->select();
        //$data = input('post.');
        foreach($question as $key => $val) {
	    // if($val['required'] == 0) continue;
            if($val['required'] == 1 && (empty(input('post.'.$val['name'])) || input('post.'.$val['name']) == -1)) $this->error('你还存在没有作答的问题：'.$val['name']);
            $data[$val['name']] = input('post.'.$val['name']);
        }
        if(empty(session('openid_'.$mid))) $this->error('微信登陆已过期，请重新登陆');
        $student = Db::name('apply_student_'.$mid)->where('openid', session('openid_'.$mid))->select();
        if(!empty($student)) $this->error('你已经报名该模型，不能重复报名');
        if($model['needfile'] == 1) {
            //下载图片到服务器本地
            $data['file'] = input('post.file');
            $xm = empty($data['姓名'])?'':$data['姓名'];
            $model = Ut::getModelSetting($mid);
            $acc = Ut::getAccessToken($model['appid'], $model['appsecret'], 'cache', 'm'.$mid)['token'];
            if(file_exists(APP_PATH.'/apply/studentimgs/'.$mid.'/'.session('openid_'.$mid).'_'.$mid.'.jpg'))
                unlink(APP_PATH.'/apply/studentimgs/'.$mid.'/'.session('openid_'.$mid).'_'.$mid.'.jpg');
            $this->dFile("https://api.weixin.qq.com/cgi-bin/media/get?access_token={$acc}&media_id={$data['file']}", APP_PATH.'/apply/studentimgs/'.$mid.'/'.session('openid_'.$mid).'_'.$mid.'.jpg');
            $data['file'] = session('openid_'.$mid).'_'.$mid.'.jpg';
        }
        $data['openid'] = session('openid_'.$mid);
        $data['create_time'] = "".time()."";
        Db::query('set names utf8');
        Db::name('apply_student_'.$mid)->insertAll([$data]);
        $this->success('你已成功报名! ', url('Weentrance/exitHere', ['mid' => $mid]));
    }

    private function dFile($url, $savePath){
        ob_start();
        readfile($url);
        $img  = ob_get_contents();
        ob_end_clean();
        $size = strlen($img);
        $fp = fopen($savePath, 'a');
        fwrite($fp, $img);
        fclose($fp);
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
