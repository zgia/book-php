<?php

namespace App\Controller;

use App\Service\SystemService;

/**
 * 首页控制器
 */
class Index extends ApiBaseController
{
    // 不要登录验证
    protected $needSignin = false;

    /**
     * Constructor
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * 网站首页
     */
    public function index()
    {
        $this->resp('Hello World.');
    }

    public function globalOptions()
    {
        $options = [
            'pwdminlen' => getOption('pwdminlen'),
        ];

        // 图书分类
        //$categories = SystemService::categories();

        $data = [
            'options' => $options,
            'perpage' => PERPAGE,
        ];

        $this->resp('category', I_SUCCESS, $data);
    }
}
