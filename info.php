<?php
return [
    // 模块名[必填]
    'name'        => 'apply',
    // 模块标题[必填]
    'title'       => '纳新',
    // 模块唯一标识[必填]，格式：模块名.开发者标识.module
    'identifier'  => 'apply.sdnuzsjyc',
    // 开发者[必填]
    'author'      => 'Chenzibin',
    // 版本[必填],格式采用三段式：主版本号.次版本号.修订版本号
    'version'     => '1.0.0',
    // 模块描述[必填] 
    'description' => '无纸化纳新管理系统',
    'need_plugin' => [
        ['Excel', 'excel.ming.plugin', '1.0.1']
    ],
    'tables' => [
        'apply_intervtime', 'apply_model', 'apply_question'
    ]
];