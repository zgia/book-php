<?php

use App\Helper\MvcHelper;
use Neo\Config;
use Neo\Neo;

// 必须使用其他文件加载global.php
if (! defined('NEO_PHP_SCRIPT')) {
    exit('NEO_PHP_SCRIPT must be defined to continue');
}

// 错误报告
ini_set('display_errors', 'on');
error_reporting(E_ALL & ~E_NOTICE & ~E_STRICT & ~E_DEPRECATED);

// 缺省时区：北京时间
date_default_timezone_set('Asia/Shanghai');

// ABS PATH
if (! defined('ABS_PATH')) {
    define('ABS_PATH', dirname(__FILE__));
}

// 系统配置
$NEO_CONFIG = [];
require ABS_PATH . DIRECTORY_SEPARATOR . 'config' . DIRECTORY_SEPARATOR . 'config.php';

// 一些变量
require ABS_PATH . DIRECTORY_SEPARATOR . 'config' . DIRECTORY_SEPARATOR . 'params.php';

// 自动加载
require ABS_PATH . DIRECTORY_SEPARATOR . 'vendor' . DIRECTORY_SEPARATOR . 'autoload.php';

(function () use ($NEO_CONFIG) {
    // 运行模式
    Neo::setServerMode(PHP_SAPI === 'cli' ? 'cli' : NEO_SERVER_TYPE);

    // 初始化NeoFrame
    Config::load($NEO_CONFIG);
    $neo = new Neo();
    MvcHelper::loadDefault($neo);

    // 系统初始化时，处理当前访问的用户
    MvcHelper::checkUser();

    // HTTP方式访问
    if (defined('NEO_LOAD_WEBPAGE') && NEO_LOAD_WEBPAGE) {
        // 路由
        require ABS_PATH . DIRECTORY_SEPARATOR . 'config' . DIRECTORY_SEPARATOR . 'routes.php';

        MvcHelper::dispatch($neo);
    }
})();
