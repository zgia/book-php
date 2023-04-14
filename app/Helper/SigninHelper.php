<?php

namespace App\Helper;

use App\Exception\UserException;
use App\Service\BaseService;
use App\Service\UserService;
use Neo\Http\Cookie;

/**
 * 检查用户是否登陆
 */
class SigninHelper extends BaseHelper
{
    /**
     * 检查当前用户是否登陆
     *
     * @param array $user 当前用户
     *
     * @return bool true 表示已经登陆
     */
    public static function isSignin(?array $user = null)
    {
        if (empty($user)) {
            return false;
        }

        if ($user['authed_by_jwt']) {
            return true;
        }

        $password = (string) Cookie::get('userpassword');
        return $password && $user['id'] && ! $user['deleted'] && ($user['password'] == $password);
    }

    /**
     * 前台用户退出登录
     */
    public static function userLogout()
    {
        Cookie::set('user_id', '');
        Cookie::set('username', '');
        Cookie::set('userpassword', '');
    }

    /**
     * 前台用户登录，设置Cookie
     *
     * @param array $user   用户
     * @param bool  $keepme 是否记录用户登陆状态，有效期1个月
     */
    public static function userSignin(array $user, bool $keepme = false)
    {
        $expire = $keepme ? SECONDS_YEAR : 0;

        Cookie::set('user_id', $user['id'], $expire);
        Cookie::set('user_name', $user['username'], $expire);
        Cookie::set('user_password', $user['password'], $expire);
    }

    /**
     * 登陆时，校验信息
     *
     * @param string $mobile      mobile
     * @param string $password    密码
     * @param string $captchacode 验证码
     *
     * @return array 校验成功，返回用户数组，否则抛出异常
     */
    public static function checkSigninInfo(string $mobile, string $password, ?string $captchacode = null)
    {
        if (empty($mobile) || empty($password)) {
            throw new UserException('请输入手机号或者密码。', 400);
        }

        $model = BaseService::getUserModel();

        // 验证用户名、密码
        $user = $model->getUserByMobile($mobile);

        if (! $user['id']) {
            throw new UserException('用户不存在。', 404);
        }

        if ($user['password'] != UserService::genPassword($password, $user['salt'])) {
            throw new UserException('输入了错误的密码。', 401);
        }

        return $user;
    }
}
