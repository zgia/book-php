<?php

/**
 * NeoFrame 基础配置文件。
 *
 * 本文件包含以下配置选项：
 * Redis 设置，MySQL 设置、密钥
 * NeoFrame 语言设定
 */

/*
 * 系统的运行环境
 * 开发时，请设置为: development
 * 上线部署，请设置为: product
 */
define('NEO_ENVIRONMENT', 'product');

/*
 * 服务运行模式:api,web,cli
 * cli自动采用PHP_SAPI的值
 */
define('NEO_SERVER_TYPE', 'api');

require ABS_PATH . DIRECTORY_SEPARATOR . 'config' . DIRECTORY_SEPARATOR . 'config-' . NEO_ENVIRONMENT . '.php';
