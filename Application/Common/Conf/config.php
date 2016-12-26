<?php
return array(
	//基本配置
    'MODULE_ALLOW_LIST' => array('Admin','Agent'),
    'DEFAULT_MODULE' => 'Admin', //默认模块
    'URL_CASE_INSENSITIVE' => true, //URL不区分大小写
    'URL_MODEL' => 1, // nginx为1 PATH_INFO模式 http://www.example.com/admin/user || Apache为2 REWRITE模式

    //REST API
    'REST_ENABLE' => true,
    'REST_GET' => 'Get',
    'REST_POST' => 'Post',
    'REST_PUT' => 'Put',
    'REST_DELETE' => 'Delete',

    //数据库配置 mongodb socketTimeoutMS=-1
    'MONGO_SERVER' => 'mongodb://backdbuser:backdbpwd@115.28.230.12:27017/backDb?authMechanism=SCRAM-SHA-1',
    'MONGO_DB' => 'backDb',

    //theme
    'DEFAULT_THEME' => 'Admin',
    'LAYOUT_ON' => true,
    'LAYOUT_NAME' => 'layout',

    //分页
    'PAGE_NUM' => 10,
    'PAGE_CALLBACK' => 'menuClick',
);