<?php

namespace App\Model\Member;

use Neo\Base\Model;

/**
 * 用户模型
 */
class UserModel extends Model
{
    protected $table = 'user';

    /**
     * 设置登录信息
     *
     * @param array $user 用户
     */
    public function newLogin(array $user)
    {
        $this->updateUserVisit($user);
    }

    /**
     * 更新用户的最后访问信息
     *
     * @param array $user 用户信信息
     */
    public function updateUserVisit(array $user)
    {
        $this->save(
            [
                'lastactivity' => timenow(),
                'lastip' => neo()->getRequest()->getClientIp(),
                'lastrealip' => neo()->getRequest()->altIp(),
            ],
            ['id' => $user['id']]
        );
    }

    /**
     * 新增用户
     *
     * @param array $user 用户信息
     *
     * @return int 用户ID
     */
    public function createUser(array $user)
    {
        return $this->insert($user);
    }

    /**
     * 根据条件获取用户信息
     *
     * @param array $cond
     *
     * @return null|array|object
     */
    public function getUserByCond(array $cond)
    {
        return $cond ? $this->row($cond) : [];
    }

    /**
     * 根据电子邮件获取用户信息
     *
     * @param string $email 电子邮件
     *
     * @return array
     */
    public function getUserByEmail(?string $email)
    {
        return $this->getUserByCond(compact('email'));
    }

    /**
     * 根据手机号获取用户信息
     *
     * @param string $mobile 手机号码
     *
     * @return array
     */
    public function getUserByMobile(?string $mobile)
    {
        return $this->getUserByCond(compact('mobile'));
    }

    /**
     * 根据用户名获取用户信息
     *
     * @param string $username 用户名
     *
     * @return array
     */
    public function getUserByName(?string $username)
    {
        return $this->getUserByCond(compact('username'));
    }

    /**
     * 根据用户ID获取用户信息
     *
     * @param int $userid 用户ID
     *
     * @return array
     */
    public function getUserById(int $userid)
    {
        $users = $this->getUsersByUserids($userid);

        return $users[$userid] ?? [];
    }

    /**
     * 根据多个用户ID获取多用户信息
     *
     * @param array|int $userids 用户ID
     *
     * @return array 用户信息
     */
    public function getUsersByUserids($userids)
    {
        if (empty($userids)) {
            return [];
        }

        return $this->rows(['id' => $userids, 'deletedat' => 0]);
    }

    /**
     * 更新用户密码
     *
     * @param int    $userid
     * @param string $password 经过HASH
     */
    public function updatePassword(int $userid, string $password)
    {
        $this->update(['password' => $password], ['id' => $userid]);
    }

    /**
     * 新用户，ID为0
     *
     * @return array 用户基础信息
     */
    public static function newUser()
    {
        return ['id' => 0, 'username' => null, 'email' => null];
    }

    /**
     * 空用户
     *
     * @return array 空数组
     */
    public static function emptyUser()
    {
        return [];
    }
}
