<?php

define('NEO_PHP_SCRIPT', 'index');
define('NEO_LOAD_WEBPAGE', PHP_SAPI !== 'cli');

require dirname(__FILE__, 2) . DIRECTORY_SEPARATOR . 'global.php';

// 页面解析到此，输出调试信息，关闭数据库链接......
byebye();
