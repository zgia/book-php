<?php

/**
 * 自定义参数
 *
 * @param \Neo\Neo $neo
 */
function setMiscParams($neo)
{
    $mps = [];

    // 性别
    $mps = [
        'gender' => [
            '1' => '男',
            '2' => '女',
        ],
        'yesorno' => [
            '1' => '是',
            '2' => '否',
        ],
    ];

    $neo->miscparam = $mps;
}

/**
 * 是否为女性
 *
 * @param  mixed $gender
 * @return bool
 */
function isFemale($gender)
{
    return $gender == 2;
}

/**
 * 系统使用的缓存 Keys
 *
 * 分类与参数使用冒号分隔
 *
 * @param \Neo\Neo $neo
 */
function setCacheKeys($neo)
{
    $neo->cacheKeys = [];
}

/**
 * 自定义常量
 */
function initUserInterfaceConstants()
{
}
