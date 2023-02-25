<?php

namespace App\Service;

use App\Exception\UserException;
use App\Helper\MvcHelper;
use App\Helper\SigninHelper;
use App\Model\Member\UserModel;
use Neo\Http\Cookie;
use Neo\Str;
use Neo\Utility;

/**
 * Class UserService
 */
class UserService extends BaseService
{
    /**
     * 获取当前的用户信息
     *
     * @return array
     */
    public static function user()
    {
        return neo()->getUser();
    }

    /**
     * 获取用户信息
     *
     * @param int $userid 用户ID
     *
     * @return array
     */
    public static function getUser($userid)
    {
        $userid = (int) $userid;

        return $userid ? static::getUserModel()->getUserById($userid) : static::emptyUser();
    }

    /**
     * 获取JWT
     */
    public function getJwtToken()
    {
        $request = neo()->getRequest();

        $token = trim($request->neoServer('HTTP_AUTHORIZATION'));

        if (! $token) {
            $token || $token = Cookie::get('jwt', $request);
            $token || $token = $request->neoRequest('jwt');

            $token && $token = 'Bearer ' . trim($token);
        }

        return $token;
    }

    /**
     * 通过JWT获取用户信息
     *
     * @return array
     */
    public function getUserByJWT()
    {
        // strlen('Bearer ') + strlen('......'),
        $token = $this->getJwtToken();
        if (! $token || strlen($token) < 100) {
            return static::emptyUser();
        }

        $jwt = MvcHelper::httpJwt();
        if (! $jwt->authenticate('Authorization: ' . $token)) {
            return static::emptyUser();
        }
        $data = $jwt->getData();

        $user = static::getUser($data['uid']);

        if (! $user) {
            return static::emptyUser();
        }

        // 还有一天过期时，自动更新
        if ($data['exp'] < timenow() + 24 * 3600) {
            $user['jwt'] = static::newJwtToken($user, $jwt);
            $user['renew_jwt'] = true;
        }

        $user['authed_by_jwt'] = true;

        return $user;
    }

    /**
     * @param array   $user
     * @param HttpJWT $jwt
     *
     * @return string
     */
    public static function newJwtToken(array $user, $jwt = null)
    {
        if (! $jwt) {
            $jwt = MvcHelper::httpJwt();
        }

        return $jwt->getUserToken('BookLibrary', $user['id'], $user['username']);
    }

    /**
     * 获取当前用户
     *
     * @return array
     */
    public function getCurrentUser()
    {
        $user = $this->getUserByJWT();
        if ($user) {
            $this->neo->setUser($user);

            return $user;
        }

        $user = static::getUser(Cookie::get('user_id'));

        if ($user && SigninHelper::isSignin($user)) {
            $this->neo->setUser($user);

            return $user;
        }

        return static::emptyUser();
    }

    /**
     * 创建新用户/编辑用户
     *
     * @param array $user 用户信息
     *
     * @return string 成功返回NULL，否则返回错误信息
     */
    public static function saveUser($user)
    {
        $userid = intval($user['id'] ?? '');
        unset($user['id']);

        // 检查当前用户是否存在
        $editUserInfo = [];
        if ($userid) {
            $editUserInfo = static::getUser($userid);
            if (empty($editUserInfo['id'])) {
                throw new UserException(__('The user is not existed.'));
            }
        }

        $unique = static::isUniqueMobile($user['mobile'], $userid);
        if (! $unique) {
            throw new UserException('已经有相同手机号的用户存在了。');
        }

        if ($userid) {
            $user['updatedat'] = timenow();
            static::getUserModel()->update($user, ['id' => $userid]);
        } else {
            // 新建用户时，密码默认为手机号的最后7位
            $user['salt'] = Str::salt();
            $user['password'] = static::getInitPassword($user['mobile'], $user['salt']);

            $user['createdat'] = timenow();
            $userid = static::getUserModel()->createUser($user);
        }

        // 保存日志
        $log = [
            'type' => 'user',
            'action' => 'update',
            'id' => $userid,
            'from' => $editUserInfo,
            'to' => $user,
        ];
        actionLog($log);

        return $userid;
    }

    /**
     * 初始密码为手机号后7位
     * @param mixed $mobile
     * @param mixed $salt
     */
    public static function getInitPassword($mobile, $salt)
    {
        return static::genPassword(substr($mobile, -7), $salt);
    }

    /**
     * 修改密码
     *
     * @param array $post
     */
    public function changePassword(array $post)
    {
        $user = self::user();
        if ($user['password'] != static::genPassword($post['password'], $user['salt'])) {
            throw new UserException('错误的旧密码，请检查后重新输入。');
        }

        if (strlen($post['password1']) < getOption('pwdminlen')) {
            throw new UserException('密码至少' . getOption('pwdminlen') . '个字符，建议输入数字、大小写混合的字母和特殊字符。');
        }

        if ($post['password1'] != $post['password2']) {
            throw new UserException('两个密码不匹配，请检查后重新输入。');
        }

        static::getUserModel()->updatePassword($user['id'], static::genPassword($post['password1'], $user['salt']));
    }

    /**
     * 空用户
     *
     * @return array 空数组
     */
    public static function emptyUser()
    {
        return UserModel::emptyUser();
    }

    /**
     * @param array $cond
     * @param int   $userid
     *
     * @return bool
     */
    public static function isUnique(array $cond, int $userid = 0)
    {
        $user = static::getUserModel()->getUserByCond($cond);

        // 查无此人
        if (! $user || ! $user['id']) {
            return true;
        }

        // 如果有用户，则检查是否用户自己
        if ($userid && $user['id'] == $userid) {
            return true;
        }
        return false;
    }

    /**
     * 修改账户信息时，检查用户输入的用户名是否唯一。
     *
     * @param string $username 用户名
     * @param int    $userid   用户ID
     *
     * @return bool true表示唯一
     */
    public static function isUniqueUsername(string $username, int $userid = 0)
    {
        return static::isUnique(compact('username'), $userid);
    }

    /**
     * 注册手机用户时，检查用户输入的手机号是否唯一。
     *
     * @param string $mobile mobile
     * @param int    $userid 用户ID
     *
     * @return bool true表示唯一
     */
    public static function isUniqueMobile(string $mobile, int $userid = 0)
    {
        return static::isUnique(compact('mobile'), $userid);
    }

    /**
     * 生成用户的密码
     *
     * @param string $src  用户输入的原始串
     * @param string $salt 加密盐
     *
     * @return string 计算后得到的密码
     */
    public static function genPassword(string $src, string $salt)
    {
        if (Utility::isMD5Str($src)) {
            return md5($src . $salt);
        }

        return md5(md5($src) . $salt);
    }
}
