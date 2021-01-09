<?php
namespace app\apply\validate;

use think\Validate;

class Question extends Validate {
    protected $rule = [
        'name|模型名称' => 'require',
        'type|类型' => 'require'
    ];

}