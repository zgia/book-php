<?php

namespace App\Helper;

use FastRoute\Dispatcher;
use Neo\Exception\LogicException;
use Neo\Exception\ResourceNotFoundException;
use Neo\Http\Request;

/**
 * 路由
 */
class RouteHelper extends BaseHelper
{
    /**
     * @var Dispatcher
     */
    private $dispatcher;

    /**
     * 初始化
     *
     * @param callable $routeDefinitionCallback
     * @param bool     $cache
     * @param array    $options
     */
    public function init(callable $routeDefinitionCallback, $cache = false, array $options = [])
    {
        if ($cache) {
            $this->dispatcher = \FastRoute\cachedDispatcher($routeDefinitionCallback, $options);
        } else {
            $this->dispatcher = \FastRoute\simpleDispatcher($routeDefinitionCallback, $options);
        }
    }

    /**
     * 加载路由
     */
    public function dispatch()
    {
        $server = $this->neo->getRequest()->neoServer();

        $parts = parse_url($server['REQUEST_URI']);
        $path = $parts['path'] ?? '';

        if ($path == '/favicon.ico') {
            return;
        }

        $path = Request::stripQueryString($path);

        // 特殊处理
        ($path === '' || $path === '/' || $path === '/index.php') && $path = '/index';

        $path = rawurldecode(rtrim($path, '/'));

        $routeInfo = $this->dispatcher->dispatch($server['REQUEST_METHOD'], $path);

        switch ($routeInfo[0]) {
            case Dispatcher::NOT_FOUND:
                throw new ResourceNotFoundException(__f('(%s) Not Found', $path), 404);
                break;
            case Dispatcher::METHOD_NOT_ALLOWED:
                throw new LogicException(
                    __f('Method (%s) for (%s) Not Allowed', implode(', ', $routeInfo[1]), $path),
                    405
                );
                break;
            case Dispatcher::FOUND:
                if (is_array($routeInfo[1]) || is_string($routeInfo[1])) {
                    $this->callFunc($routeInfo);
                } elseif (is_callable($routeInfo[1])) {
                    $routeInfo[1](...$routeInfo[2]);
                } else {
                    throw new LogicException(__f('(%s) Route Error', $path));
                }
                break;
        }
    }

    /**
     * 使用"类->方法"的方式加载控制器
     *
     * @param array $routeInfo
     */
    public function callFunc(array $routeInfo)
    {
        // 数组, 格式：['className', 'funcName']
        if (is_array($routeInfo[1])) {
            [$className, $funcName] = $routeInfo[1];
        } else {
            // 字符串, className@funcName
            [$className, $funcName] = explode('@', $routeInfo[1]);
        }

        $params = [];

        if (! empty($routeInfo[2]) && is_array($routeInfo[2])) {
            if ($funcName === '*') {
                if (isset($routeInfo[2]['function'])) {
                    $funcName = $routeInfo[2]['function'];
                    unset($routeInfo[2]['function']);
                }
            }

            if (! empty($routeInfo[2])) {
                $params = array_values($routeInfo[2]);
            }
        }

        if ($funcName === '*') {
            throw new LogicException(__f('Function(%s) Not Allowed', $funcName));
        }

        // 用于是否登录验证和日志等
        $this->neo->routeInfo = ['class' => $className, 'func' => $funcName, 'params' => $params];

        (new $className())->{$funcName}(...$params);
    }
}
