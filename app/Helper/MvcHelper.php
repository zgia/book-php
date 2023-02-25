<?php

namespace App\Helper;

use Neo\Cache\Redis\RedisNull;
use Neo\Config;
use Neo\Exception\NeoException;
use Neo\Html\Page;
use Neo\Http\Cookie;
use Neo\HttpAuth\HttpJWT;
use Neo\Neo;
use Neo\Utility;

/**
 * Class MvcHelper
 */
class MvcHelper extends BaseHelper
{
    /**
     * 401 unauthorized
     */
    public static function unauthorized(): void
    {
        byebye(401);
    }

    /**
     * 获取httpJWT对象
     *
     * @param array $config
     *
     * @return HttpJWT
     */
    public static function httpJwt(?array $config = null): HttpJWT
    {
        static $jwt = [];

        $config || $config = static::getHttpJwtConfig('neo');

        if (! $jwt[$config['id']]) {
            $jwt[$config['id']] = new HttpJWT($config);
        }

        return $jwt[$config['id']];
    }

    /**
     * 获取缺省JWT配置，即当前系统用配置
     *
     * @param string $key
     *
     * @return array
     */
    public static function getHttpJwtConfig(string $key): array
    {
        $config = Config::get('jwt', $key);

        if (! $config) {
            throw new NeoException(__f('Invalid JWT config, maybe key(%s) is not exist.', $key));
        }

        return $config;
    }

    /**
     * 系统相关常量
     */
    public static function initFunctionalityConstants(): void
    {
        /*
         * 当前登录用户的用户组
         */
        // 管理员
        define('USERGROUP_ADMINISTRATOR', 1);
        // 禁用用户
        define('USERGROUP_BAN', 2);
        // 普通用户
        define('USERGROUP_USER', 3);

        /*
         * 时间
         */
        // 一天的秒数
        define('SECONDS_DAY', 86400);
        // 一周的秒数
        define('SECONDS_WEEK', 604800);
        // 一月的秒数
        define('SECONDS_MONTH', 2592000);
        // 一年的秒数
        define('SECONDS_YEAR', 31536000);
        // 定义Redis常默认缓存时间: 一周
        define('REDIS_DEFAULT_TIMEOUT', SECONDS_WEEK);

        /*
         * 性别
         */
        define('GENDER_MALE', 1);
        define('GENDER_FEMALE', 2);

        /*
         * 接口返回
         */
        // 成功
        define('I_SUCCESS', 0);
        // 失败
        define('I_FAILURE', 1);

        // 418 I'm a teapot
        define('I_AM_A_TEAPOT', 418);
    }

    /**
     * 初始化系统设置缓存
     *
     * @param Neo $neo
     */
    public static function initOptionsContant(Neo $neo): void
    {
        // 自定义时区，比如：Asia/Shanghai
        $datetimezone = Config::get('datetime', 'zone');
        if (! $datetimezone) {
            $datetimezone = getOption('datetimezone', date_default_timezone_get() ?: 'UTC');
            Config::set('datetime', 'zone', $datetimezone);
        }

        Config::set('datetime', 'offset', timezone_offset_get(timezone_open($datetimezone), date_create()));

        // 分页每页记录数
        if (! defined('PERPAGE')) {
            define('PERPAGE', getOption('perpage', 20));
        }

        // 含域名的URL，用于跳转、邮件等等脱离系统环境的URL
        if (! defined('ABS_URL')) {
            define('ABS_URL', neo()->getRequest()->getSchemeAndHttpHost() . Config::get('server', 'path'));
        }

        // 系统URL
        if (! defined('SYSTEM_URL')) {
            define('SYSTEM_URL', getOption('baseurl', '') . Config::get('server', 'path'));
        }

        // Content路径，用于样式，脚本，图片等等
        if (! defined('CONTENT_URL')) {
            define('CONTENT_URL', SYSTEM_URL . '/assets');
        }

        // Content路径，用于样式，脚本，图片等等
        if (! defined('ABS_CONTENT_URL')) {
            define('ABS_CONTENT_URL', ABS_URL . '/assets');
        }

        // 使用redirect方法跳转时，添加一个随机数
        if (! defined('REDIRECT_WITH_RANDOM')) {
            define('REDIRECT_WITH_RANDOM', false);
        }
    }

    /**
     * 系统初始化时，处理当前访问的用户
     */
    public static function checkUser(): void
    {
        $user = [];

        try {
            $user = loadService('UserService')->getCurrentUser();
        } catch (\Throwable $ex) {
            // TODO
        }

        $userid = $user['id'] ?? 0;

        // 用户
        if (! defined('CURRENT_USER_ID')) {
            define('CURRENT_USER_ID', (int) $userid);
        }
    }

    /**
     * 检查网站是否关闭维护
     */
    public static function isWebClosed(): void
    {
        if (defined('IGNORE_WEBCLOSED') && IGNORE_WEBCLOSED || empty(getOption('closesite'))) {
            return;
        }

        if (! isAdministrator()) {
            displayError(__('Site is closed for maintenance, please wait a moment.'), '', false);
        }
    }

    /**
     * 载入系统缺省
     *
     * @param Neo $neo
     */
    public static function loadDefault(Neo $neo): void
    {
        // API系统
        $neo->request->setAjax(true);

        // 系统运行配置
        static::initFunctionalityConstants();

        // 用户自定义常量
        if (function_exists('initUserInterfaceConstants')) {
            initUserInterfaceConstants();
        }

        // 自定义参数
        setMiscParams($neo);

        // 缓存Key
        setCacheKeys($neo);

        // 初始化Redis
        $neo->setRedis(defined('NEO_REDIS') && NEO_REDIS ? Neo::initRedis('neo') : new RedisNull());

        // 系统设置
        static::initOptionsContant($neo);

        // 检查PHP版本
        Utility::checkPHPVersion(NEO_REQUIRED_PHP_VERSION);

        // 是否维护模式
        static::isWebClosed();

        // 是否记录API日志
        $neo['log_api_response'] = (bool) getOption('log_api_response');
        $neo['log_api_request'] = (bool) getOption('log_api_request');
    }

    /**
     * 加载Web页面处理
     *
     * @param Neo $neo
     */
    public static function dispatch(Neo $neo): void
    {
        // 分页导航第几页
        define('CURRENT_PAGE', (int) max($neo->getRequest()->neoRequest('p'), 1));

        // 路由
        $route = new RouteHelper();
        $route->init(
            customizedRoutes(),
            NEO_ROUTE_CACHE_ENABLE,
            ['cacheFile' => $neo['datastore_dir'] . DIRECTORY_SEPARATOR . 'neo_routecaches.php']
        );
        $route->dispatch();
    }

    /**
     * 渲染模板
     *
     * @param string      $file             模板路径
     * @param bool        $withHeaderFooter 是否在模板的前后添加Header和Footer
     * @param null|string $special          指定的Header和Footer
     */
    public static function renderTemplate(string $file, bool $withHeaderFooter = true, ?string $special = null): void
    {
        $template = neo()->getTemplate();

        if ($withHeaderFooter) {
            $template->getTemplateFile('frame/header', $special);
        }

        $template->getTemplateFile($file);

        if ($withHeaderFooter) {
            $template->getTemplateFile('frame/footer', $special);
        }
    }

    /**
     * 分页函数
     *
     * @param string $url       URL
     * @param int    $totalItem 总记录数
     * @param int    $perpage   每页显示数量
     * @param bool   $showTotal 显示总数
     *
     * @return string
     */
    public static function paginate(string $url, int $totalItem, int $perpage = PERPAGE, bool $showTotal = true): string
    {
        if (! headers_sent()) {
            Cookie::set('backpage', '1');
        }

        // 分页数量
        $totalPage = ceil($totalItem / $perpage ?: PERPAGE);

        if ($totalPage < 2) {
            return '';
        }

        $url .= strpos($url, '?') === false ? '?p=' : '&p=';
        $url = str_replace('?&', '?', $url);

        // 分页左右间隔数
        $halfPer = 5;
        $current = CURRENT_PAGE;

        $prevUrl = ($current > 1) ? static::pgLink($url . ($current - 1), '&laquo;') : static::pgLink(
            '#',
            '&laquo;',
            'disabled'
        );
        $fisrtUrl = ($current > $halfPer + 1) ? static::pgLink($url . '1', '1') : '';

        $normUrl = '';
        for ($i = max($current - $halfPer, 1), $j = min($current + $halfPer, $totalPage); $i <= $j; ++$i) {
            $normUrl .= static::pgLink($url . $i, "{$i}", $i == $current ? 'active' : '');
        }

        $lastUrl = ($totalPage - $halfPer > $current) ? static::pgLink($url . $totalPage, "{$totalPage}") : '';
        $nextUrl = ($current < $totalPage) ? static::pgLink($url . ($current + 1), '&raquo;') : static::pgLink(
            '#',
            '&raquo;',
            'disabled'
        );
        $rows = ($showTotal) ? static::pgLink('#', $totalItem . __('Rows'), 'disabled') : '';

        return "<ul class=\"pagination pagination-centered\">{$prevUrl}{$fisrtUrl}{$normUrl}{$lastUrl}{$nextUrl}{$rows}</ul>";
    }

    /**
     * 生成分页的页面URL
     *
     * @param string $url
     * @param string $text
     * @param string $class
     *
     * @return string
     */
    protected static function pgLink(string $url, string $text, string $class = ''): string
    {
        $class && $class = "class='{$class}'";

        return sprintf('<li %s><a href="%s">%s</a></li>', $class, $url, $text);
    }

    /**
     * 显示面包屑导航
     *
     * @param array $breadcrumbs
     *
     * @return string
     */
    public static function displayBreadcrumbs(array $breadcrumbs = []): string
    {
        $htmls = [];
        if ($breadcrumbs && is_array($breadcrumbs)) {
            $breadcrumbs = array_merge(['/' => __('Home')], $breadcrumbs);
        } else {
            $breadcrumbs = [__('Home')];
        }

        $i = 0;
        foreach ($breadcrumbs as $link => $breadcrumb) {
            $html = '';

            if (! $i) {
                $html = '<i class="fa fa-home"></i>';
            }

            if ($link && ! is_numeric($link)) {
                if ($link[0] === '/') {
                    $link = baseURL($link);
                }

                $html .= sprintf('<a href="%s"><samp>%s</samp></a>', $link, $breadcrumb);
            } else {
                $html .= sprintf('<samp>%s</samp>', $breadcrumb);
            }

            ++$i;
            if ($i < count($breadcrumbs)) {
                $html .= '<i class="fa fa-angle-right"></i>';
            }

            $htmls[] = sprintf('<li>%s</li>', $html);
        }

        return implode('', $htmls);
    }

    /**
     * 生成下拉选择框
     *
     * @param array  $array    Array of value => text pairs representing '<option value="$key">$value</option>' fields
     * @param string $name     Name for select field
     * @param string $id       Id for select field
     * @param string $selected Selected option
     * @param string $onchange Select onchange event
     * @param int    $size     Size of select field (non-zero means multi-line)
     * @param bool   $multiple Whether or not to allow multiple selections
     *
     * @return string select
     */
    public static function constructSelect(
        array $array,
        ?string $name = null,
        ?string $id = null,
        ?string $selected = null,
        ?string $onchange = null,
        int $size = 0,
        bool $multiple = false
    ): string {
        if (empty($array[0])) {
            $array = ['' => __('Please Select One')] + $array;
        }

        $fmt = '<select name="%s" id="%s" class="form-control input-inline" %s %s %s>%s</select>';

        return sprintf(
            $fmt,
            $name,
            $id,
            $size ? "size=\"{$size}\"" : '',
            $multiple ? 'multiple="multiple"' : '',
            $onchange ? "onchange=\"{$onchange}\"" : '',
            static::constructSelectOptions($array, $selected)
        );
    }

    /**
     * Returns a list of <option> fields, optionally with one selected
     *
     * @param array        $array      Array of value => text pairs representing '<option value="$key">$value</option>' fields
     * @param array|string $selectedid Selected option
     *
     * @return string List of <option> tags
     */
    public static function constructSelectOptions(?array $array = null, $selectedid = ''): string
    {
        $options = '';
        if (! is_array($array)) {
            return $options;
        }

        foreach ($array as $key => $val) {
            if (is_array($val)) {
                $options .= sprintf(
                    '<optgroup label="%s">%s</optgroup>',
                    $key,
                    static::constructSelectOptions($val, $selectedid)
                );
            } else {
                $selected = (in_array($key, (array) $selectedid) ? ' selected="selected"' : '');

                if (preg_match('#^NOONELEFT#', $val, $temp)) {
                    $nooneleft = 'class="clazzisfull"';

                    $val = preg_replace('#^NOONELEFT#', '', $val);
                } else {
                    $nooneleft = '';
                }

                $options .= sprintf(
                    '<option value="%s" %s %s>%s</option>',
                    $key !== 'no_value' ? $key : '',
                    $selected,
                    $nooneleft,
                    $val
                );
            }
        }

        return $options;
    }

    /**
     * Returns a list of <input type="radio" /> buttons, optionally with one selected
     *
     * @param string $name    Name for radio buttons
     * @param array  $array   Array of value => text pairs representing '<input type="radio" value="$key" />$value' fields
     * @param string $checked Selected radio button value
     * @param bool   $inline  set <br> between radios
     *
     * @return string List of <input type="radio" /> buttons
     */
    public static function constructRadio(string $name, array $array, ?string $checked = null, bool $inline = true): string
    {
        if (! is_array($array)) {
            return '';
        }

        $options = '';

        foreach ($array as $key => $val) {
            if (is_array($val)) {
                $options .= sprintf(
                    '<strong>%s</strong>%s',
                    $key,
                    $inline ? '<br />' : ''
                );
                $options .= static::constructRadio($name, $val, $checked, $inline);
            } else {
                $fmt = "<label %s><input type='radio' name='%s' value='%s' %s> %s</label>";
                $options .= sprintf(
                    $fmt,
                    $inline ? 'class="radio-inline"' : '',
                    $name,
                    $key !== 'no_value' ? $key : '',
                    $key == $checked ? 'checked="checked"' : '',
                    $val
                );
            }
        }

        return sprintf("<div class='radio-list'>%s</div>", $options);
    }

    /**
     * Returns a list of <input type="checkbox" /> buttons, optionally with one selected
     *
     * @param string $name    Name for checkbox buttons
     * @param array  $array   Array of value => text pairs representing '<input type="checkbox" value="$key" />$value' fields
     * @param array  $checked Selected checkbox button value
     * @param bool   $inline  set <br> between checkboxes
     *
     * @return string List of <input type="checkbox" /> buttons
     */
    public static function constructCheckbox(string $name, array $array, array $checked, bool $inline = true): string
    {
        if (! is_array($array)) {
            return '';
        }

        $options = '';

        foreach ($array as $key => $val) {
            if (is_array($val)) {
                $options .= sprintf(
                    '<strong>%s</strong>%s',
                    $key,
                    $inline ? '<br />' : ''
                );
                $options .= static::constructCheckbox($name, $val, $checked, $inline);
            } else {
                $fmt = '<label %s><input type="checkbox" name="%s" value="%s" %s> %s</label>"';
                $options .= sprintf(
                    $fmt,
                    $inline ? 'class="checkbox-inline"' : '',
                    $name,
                    $key !== 'no_value' ? $key : '',
                    in_array($key, $checked) ? 'checked="checked"' : '',
                    $val
                );
            }
        }

        return sprintf("<div class='checkbox-list'>%s</div>", $options);
    }

    /**
     * 简单页面的头
     */
    public static function printFlushPageHeader(): void
    {
        if (! headers_sent() and function_exists('ob_start')) {
            while (ob_get_level()) {
                @ob_end_clean();
            }
            ob_start();
        }

        static::renderTemplate('frame/header-lite', false);

        Page::doflush();
    }

    public static function printFlushPageFooter(): void
    {
        if (function_exists('ob_start')) {
            $text = ob_get_contents();
            while (ob_get_level()) {
                @ob_end_clean();
            }

            @header('Content-Length: ' . strlen($text));
            echo $text;
        }
        flush();

        static::renderTemplate('frame/footer-lite', false);

        Page::doflush();
    }
}
