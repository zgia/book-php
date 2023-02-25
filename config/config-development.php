<?php

/**
 * NeoFrame 基础配置文件。
 *
 * 本文件包含以下配置选项：
 * Redis 设置，MySQL 设置、密钥
 * NeoFrame 语言设定
 */

/*
 * NeoFrame 语言设置，默认为中文。
 */
define('NEO_LANG', 'zh-CN');

/*
 * NeoFrame 页面编码设置
 */
define('NEO_CHARSET', 'utf-8');

/*
 * Holds the required PHP version
 */
define('NEO_REQUIRED_PHP_VERSION', '8.1.0');

/*
 * 加密盐
 */
define('NEO_SECRET_SALT', '>V5DzD2kA&f$vC-):Z>11v=e7C^M?2PG-y)K>QwhA2VSx0x)%eMEB-8n s|H0z_k');

/*
 * 时区，和基于时区的时间偏移
 *
 * @link https://www.php.net/manual/en/timezones.php
 */
$NEO_CONFIG['datetime'] = [
    'zone' => 'Asia/Shanghai',
    'offset' => 28800,
];

/*
 * 服务域名
 * 视图模板路径
 * 路径设置，注：结尾不含“/”
 *
 * 如果网址地址是：http://xxx.com/path/to/index.php，path填写： /path/to；
 * 如果网址地址是：http://xxx.com/path/index.php，path填写： /path；
 * 如果网址地址是：http://xxx.com/index.php，path什么都不填写。
 */
$NEO_CONFIG['server'] = [
    'host' => 'book.zgia.net',
    'path' => '',
];

/*
 * 系统文件存放绝对路径
 */
$NEO_CONFIG['dir'] = [
    // 系统文件所在的绝对路径
    'abs_path' => ABS_PATH,
    // 控制器
    'controllers' => ABS_PATH . DIRECTORY_SEPARATOR . 'app' . DIRECTORY_SEPARATOR . 'Controller',
    // 视图模板
    'templates' => ABS_PATH . DIRECTORY_SEPARATOR . 'app' . DIRECTORY_SEPARATOR . 'View',
    // 多语言
    'languages' => ABS_PATH . DIRECTORY_SEPARATOR . 'app' . DIRECTORY_SEPARATOR . 'Language',
    // 缓存文件
    'datastore' => ABS_PATH . DIRECTORY_SEPARATOR . 'datastore',
    // 静态文件
    'content' => ABS_PATH . DIRECTORY_SEPARATOR . 'public',
];

/*
 * Redis 配置
 */
/*
 * 是否启用Redis。
 */
define('NEO_REDIS', false);

/*
 * 在这里设置使用的数据库服务器信息。
 * 数据库版本最低为5.0
 */
$NEO_CONFIG['database'] = [
    'mysql' => [
        'driver' => 'pdo_mysql',
        'prefix' => '',
        'base' => [
            'dbname' => 'library',
            'port' => 3306,
            'user' => 'library',
            'password' => 'u78a_xUM5!@VLOV',
            'charset' => 'utf8mb4',
        ],
        'primary' => ['host' => '127.0.0.1'],
        'replica' => [
            ['host' => '127.0.0.1'],
            ['host' => '127.0.0.1'],
        ],
        'logger' => \Neo\Database\Logger::class,
    ],
];

/*
 * 文件日志级别
 */
// 日志级别: level:
// DEBUG = 100;
// INFO = 200;
// NOTICE = 250;
// WARNING = 300;
// ERROR = 400;
// CRITICAL = 500;
// ALERT = 550;
// EMERGENCY = 600;
// 日志种类: types: file, redis
$NEO_CONFIG['logger'] = [
    'level' => 200,
    'dir' => ABS_PATH . DIRECTORY_SEPARATOR . 'logs' . DIRECTORY_SEPARATOR,
    'id' => sha1(uniqid('', true) . str_shuffle(str_repeat('0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ', 16))),
    'types' => ['file'],
    'file' => [
        'pertype' => true, // 是否每个$type一个日志文件
        'typename' => 'neo', // 如果pertype==false，可以指定日志文件名称，默认为neo
        'formatter' => 'json', // 文件内容格式，默认为json，可选：line, json
    ],
];

/*
 * 登录
 */
$NEO_CONFIG['jwt']['neo'] = [
    'id' => 'neo',
    'appclient' => 'weapp',
    'secret_key' => NEO_SECRET_SALT,
    'expired_time' => 518400,
];
