<?php

// 路由是否缓存？
define('NEO_ROUTE_CACHE_ENABLE', false);

/**
 * 设置自定义路由
 *
 * @return Closure
 */
function customizedRoutes()
{
    return function (FastRoute\RouteCollector $r) {
        // 首页
        $r->addRoute('GET', '/[index]', 'App\Controller\Index@index');
        $r->addRoute('GET', '/globaloptions', 'App\Controller\Index@globalOptions');

        // 登陆
        $r->addGroup(
            '/signin',
            function (FastRoute\RouteCollector $r) {
                $r->addRoute('GET', '[/index]', 'App\Controller\Signin@index');
                $r->addRoute('GET', '/islogin', 'App\Controller\Signin@islogin');
                $r->addRoute('POST', '/dologin', 'App\Controller\Signin@dologin');
                $r->addRoute('POST', '/changepassword', 'App\Controller\Signin@changePassword');
            }
        );

        // 图书
        $r->addGroup(
            '/book',
            function (FastRoute\RouteCollector $r) {
                $r->addRoute('POST', '/download', 'App\Controller\Book@download');

                $r->addRoute('GET', '/items', 'App\Controller\Book@index');
                $r->addRoute('POST', '[/]', 'App\Controller\Book@update');
                $r->addRoute('GET', '[/]', 'App\Controller\Book@get');
                $r->addRoute('DELETE', '[/]', 'App\Controller\Book@delete');

                $r->addRoute('GET', '/getvolumes', 'App\Controller\Book@getVolumes');
                $r->addRoute('POST', '/updatevolume', 'App\Controller\Book@updateVolume');
                $r->addRoute('DELETE', '/deletevolume', 'App\Controller\Book@deleteVolume');

                $r->addRoute('GET', '/getchapters', 'App\Controller\Book@getChapters');
                $r->addRoute('GET', '/getchapter', 'App\Controller\Book@getChapter');
                $r->addRoute('POST', '/updatechapter', 'App\Controller\Book@updateChapter');
                $r->addRoute('DELETE', '/deletechapter', 'App\Controller\Book@deleteChapter');
            }
        );
    };
}
