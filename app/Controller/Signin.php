<?php

namespace App\Controller;

use App\Exception\UserException;
use App\Helper\SigninHelper;
use App\Service\UserService;

/**
 * 登陆控制器
 */
class Signin extends ApiBaseController
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

    public function changePassword()
    {
        $post = input(
            'p',
            [
                'password' => INPUT_TYPE_STR,
                'password1' => INPUT_TYPE_STR,
                'password2' => INPUT_TYPE_STR,
            ]
        );

        try {
            $this->UserService->changePassword($post);
            $this->resp('ok');
        } catch (\Throwable $ex) {
            $this->teapot($ex, $post);
        }
    }

    /**
     * 开发登陆
     */
    public function dologin()
    {
        $post = input(
            'p',
            [
                'mobile' => INPUT_TYPE_STR,
                'password' => INPUT_TYPE_STR,
            ]
        );

        try {
            $user = SigninHelper::checkSigninInfo($post['mobile'], $post['password']);
            $data = $this->getUser($user);
            $token = UserService::newJwtToken($user);

            $this->resp('登录成功', I_SUCCESS, ['token' => $token, 'user' => $data]);
        } catch (UserException $ex) {
            $this->resp($ex->getMessage(), I_FAILURE, $post, $ex->getCode());
        }
    }

    public function islogin()
    {
        if ($this->user) {
            $this->resp('ok', I_SUCCESS, ['user' => $this->getUser($this->user)]);
        } else {
            $this->resp('验证失败，请重新登录。', I_FAILURE, [], 401);
        }
    }

    private function getUser($user)
    {
        return [
            'mobile' => $user['mobile'],
            'name' => $user['realname'],
            'permissions' => ['books' => 1, 'chapters' => 1, 'content' => 1],
        ];
    }
}
