<?php

namespace App\Controller;

use App\Helper\SigninHelper;
use App\Service\UserService;
use Neo\Base\Controller;

/**
 * APP接口抽象基类
 * Class base
 */
abstract class ApiBaseController extends Controller
{
    /**
     * @var array
     */
    protected $helpers = [];

    /**
     * @var array
     */
    protected $services = [];

    /**
     * @var UserService
     */
    protected $UserService;

    /**
     * 当前控制器是否需要登录验证，默认需要登录
     *
     * @var bool
     */
    protected $needSignin = true;

    /**
     * 允许未登录访问方法
     * @var array
     */
    protected $allowNoLoginMethods = [];

    /**
     * 用户信息有强制输入项，如果没有输入，则只能停在当前页
     *
     * @var bool
     */
    protected $needMandatoryInfo = true;

    /**
     * @var array
     */
    protected $user = [];

    /**
     * @var int
     */
    protected $userid = 0;

    /**
     * Constructor
     */
    public function __construct()
    {
        parent::__construct();

        $this->response->sendAccessControlHeaders();

        $this->addServices('UserService');

        foreach ($this->helpers as $helper) {
            $class = $this->getClassName($helper);

            $this->{$class} = loadHelper($helper);
        }

        foreach ($this->services as $service) {
            $class = $this->getClassName($service);

            $this->{$class} = loadService($service);
        }

        switch ($this->request->getMethod()) {
            case 'GET':
            case 'DELETE':
            case 'PUT':
            case 'POST':
                break;
            case 'OPTIONS':
                $this->options();
                break;
            default:
                $this->forbidden(405);
                break;
        }

        $this->user = neo()->getUser();
        $this->userid = $this->user['id'];
    }

    /**
     * 获取类的变量的名称
     *
     * User\Address AS Address   => Address
     * User\Address              => Address
     * Address                   => Address
     *
     * @param string $class
     *
     * @return string
     */
    protected function getClassName(string &$class)
    {
        if (stripos($class, ' AS ') !== false) {
            [$class, $clazz] = explode(' AS ', $class);
        } elseif (stripos($class, '\\') !== false) {
            $pieces = explode('\\', $class);
            $clazz = end($pieces);
        } else {
            $clazz = $class;
        }

        return $clazz;
    }

    /**
     * 添加助手类
     *
     * @param mixed ...$helpers
     */
    protected function addHelpers(...$helpers)
    {
        $this->_merge($this->helpers, $helpers);
    }

    /**
     * 添加业务类
     *
     * @param mixed ...$services
     */
    protected function addServices(...$services)
    {
        $this->_merge($this->services, $services);
    }

    /**
     * 合并2个数组,并去重
     *
     * @param array $classes
     * @param array $args
     */
    private function _merge(array &$classes, array $args)
    {
        $classes = array_unique(array_merge($classes, $args));
    }

    /**
     * 预先处理
     */
    public function beforeRender()
    {
        if ($this->needSignin && ! in_array($this->neo->routeInfo['func'], $this->allowNoLoginMethods)) {
            if (! SigninHelper::isSignin(neo()->getUser())) {
                $this->resp('需要重新登录。', I_FAILURE);
            }
        }
    }

    /**
     * 403 Forbidden
     *
     * @param int $code
     */
    protected function forbidden(int $code = 403)
    {
        byebye($code);
    }

    /**
     * HTTP Header: OPTIONS
     */
    protected function options()
    {
        byebye(204);
    }

    /**
     * 重载
     *
     * @param $name
     * @param $arguments
     */
    public function __call($name, $arguments)
    {
        $this->resp($name . ' Not Found', I_FAILURE, ['name' => $name, 'args' => $arguments], 404);
    }

    /**
     * 输出JSON格式的数据
     *
     * @param null|string $err
     * @param int         $code
     * @param null|array  $data
     * @param int         $responseCode
     */
    public function resp(?string $err = null, int $code = I_SUCCESS, ?array $data = null, int $responseCode = 200)
    {
        $arr = ['code' => $code];

        if ($err) {
            $arr['msg'] = $err;
        }

        $arr['data'] = empty($data) ? [] : $data;

        printOutJSON($arr, $responseCode);
    }

    /**
     * 使用一个特殊的 Http Status Code 来处理错误
     */
    public function teapot(\Throwable $ex, array $data)
    {
        if (method_exists($ex, 'getMore') && $more = $ex->getMore()) {
            $data = array_merge($data, $more);
        }

        $this->resp($ex->getMessage(), I_FAILURE, $data, I_AM_A_TEAPOT);
    }
}
