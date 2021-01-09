<?php 
namespace app\apply\utils;

use think\Db;
use think\Cache;

class Utils {
    static public function test() {
        return 'ss';
    }

    static public function getAdminModel($locked = false) {

        Cache::clear();
        $uid = is_signin();
        if($uid == 1) {
            //系统管理员，返回所有模型
            $models = Db::name('apply_model')->select();  
        }else{
            $map['adminids'] = array('like', '%|'.$uid.'|%');
            $models = Db::name('apply_model')->where('creater', $uid)->whereOr('adminids', 'LIKE','%|'.$uid.'|%')->select();
            //return $models;
        }
        $ret = ['ids' => [], 'name' => []];
        $i = 0;
        foreach($models as $key => $val) {
            if($locked && $val['lockquestion'] == 1) continue;
            $ret['ids'][$i++] = $val['ID'];
            $ret['info'][$val['ID']] = $val['name'];
        }
        return $ret;
    }

    static public function getAccessToken($appid, $secret, $from = 'cache', $cache_flag = 'm0') {
        if($from == 'cache') {
            $cache = Cache::get('ap_'.$cache_flag.'_acc');
            if(!$cache) return self::getAccessToken($appid, $secret, 'weserver', $cache_flag);
            return ['errcode' => 0, 'token' => $cache, 'from' => 'cache'];
        }else{ 
            $ret = json_decode(file_get_contents('https://api.weixin.qq.com/cgi-bin/token?grant_type=client_credential&appid='.$appid.'&secret='.$secret), true);
            if(empty($ret['access_token'])) return $ret;
            Cache::set('ap_'.$cache_flag.'_acc', $ret['access_token'], $ret['expires_in']);
            return ['errcode' => 0, 'token' => $ret['access_token'], 'from' => 'weserver'];
        }
    }

    static public function getModelSetting($mid) {
        $model = Db::name('apply_model')->where('ID', $mid)->select();
        if($model == null) return false;
        return $model[0];
    }

    static public function domain() {
        $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off' || $_SERVER['SERVER_PORT'] == 443) ? "https://" : "http://";
        return $protocol.$_SERVER['HTTP_HOST'].'/';
    }
}