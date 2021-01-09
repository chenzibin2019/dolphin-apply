<?php
namespace app\apply\validate;

use think\Validate;

class Model extends Validate {
    protected $rule = [
        'name|模型名称' => 'require',
        'creater|超级管理员' => 'require',
        'applystarttime|申请开始时间' => 'require',
        'applyendtime|申请结束时间' => 'require',
        'intervstarttime|面试开始时间' => 'require',
        'intervendtime|面试结束时间' => 'require'
    ];

    protected $message = [
        'name.requireIf' => '所有内容均为必填',
        'creater.requireIf' => '所有内容均为必填',
        'applystarttime.requireIf' => '所有内容均为必填',
        'applyendtime.requireIf' => '所有内容均为必填',
        'intervstarttime.requireIf' => '所有内容均为必填',
        'intervendtime.requireIf' => '所有内容均为必填'        
    ];
}