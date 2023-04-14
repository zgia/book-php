<?php

use App\Helper\BaseHelper;
use App\Service\BaseService;
use App\Service\UserService;
use Neo\Base\Model;
use Neo\Cache\File\File as FileCache;
use Neo\Config;
use Neo\Exception\NeoException;
use Neo\Html\Page;
use Neo\NeoLog;
use Symfony\Component\Mailer\Exception\TransportExceptionInterface;
use Symfony\Component\Mailer\Mailer;
use Symfony\Component\Mailer\Transport;
use Symfony\Component\Mime\Address;
use Symfony\Component\Mime\Email;

/**
 * 加载工具类
 *
 * @param string $helper    工具类
 * @param string $namespace 命名空间前缀
 *
 * @return BaseHelper 加载的类
 */
function loadHelper(string $helper, $namespace = 'App\\Helper')
{
    return loadClass($helper, $namespace);
}

/**
 * 加载业务
 *
 * @param string $service   业务类
 * @param string $namespace 命名空间前缀
 *
 * @return BaseService 加载的类
 */
function loadService(string $service, $namespace = 'App\\Service')
{
    return loadClass($service, $namespace);
}

/**
 * 加载模型
 *
 * @param string $model     模型类
 * @param string $namespace 命名空间前缀
 *
 * @return Model 加载的类
 */
function loadModel(string $model, $namespace = 'App\\Model')
{
    return loadClass($model, $namespace);
}

/**
 * 当前是否开发环境
 *
 * @return bool
 */
function isDevelop()
{
    return ! defined('NEO_ENVIRONMENT') || NEO_ENVIRONMENT != 'product';
}

/**
 * 检查用户是否为管理员
 *
 * @param array $user 用户，如果为空，则使用当前用户
 *
 * @return bool 是或者否
 */
function isAdministrator(?array $user = null)
{
    $user || $user = UserService::user();

    return $user['id'] == 1;
}

/**
 * 将输出内容数组转为JSON格式输出显示
 *
 * @param string $msg        消息
 * @param int    $code       错误码
 * @param int    $statusCode Http状态码
 */
function printErrorJSON(string $msg, int $code = 1, int $statusCode = 200)
{
    $jsonarray = [
        'code' => $code,
        'msg' => $msg,
    ];

    printOutJSON($jsonarray, $statusCode);
}

/**
 * 将输出内容数组转为JSON格式输出显示
 *
 * @param string $msg        消息
 * @param int    $code       错误码
 * @param int    $statusCode Http状态码
 */
function printSuccessJSON(string $msg, int $code = 0, int $statusCode = 200)
{
    $jsonarray = [
        'code' => $code,
        'msg' => $msg,
    ];

    printOutJSON($jsonarray, $statusCode);
}

/**
 * 功能未完成，请等待
 */
function pleaseWait()
{
    displayError(__('Please wait a moment, we come back soon...'));
}

/**
 * 跳转时显示错误信息。如果指定URL，则显示错误信息后，自动跳转到这个URL。
 * 否则，停留在错误信息页面，等待用户后退。
 *
 * @param string $message 跳转时，显示的信息
 * @param string $url     页面跳转地址
 * @param bool   $back    是否显示后退链接
 */
function displayError(string $message, string $url = '', bool $back = true)
{
    if (neo()->getRequest()->isAjax()) {
        printErrorJSON($message);
    } else {
        Page::redirect($url, $message, __('Error'), $url ? 2 : 0, true, $back);
    }
}

/**
 * 显示提示信息
 *
 * @param string $message 显示的信息
 * @param string $url     页面跳转地址
 * @param bool   $back    是否显示后退链接
 */
function displayMessage(string $message, string $url = '', $back = false)
{
    if (neo()->getRequest()->isAjax()) {
        printSuccessJSON($message);
    } else {
        Page::redirect($url, $message, __('Information'), 0, false, $back);
    }
}

/**
 * Send email
 *
 * @param string       $subject     邮件主题
 * @param string       $body        邮件内容
 * @param array|string $to          收件人地址，可以多个
 * @param string       $contentType text/plain 或者 text/html
 * @param string       $attachment  附件绝对路径
 * @param int          $priority    优先级，默认Email::PRIORITY_NORMAL
 * @param array|string $cc          抄送
 * @param array|string $bcc         暗送
 *
 * @throws NeoException
 * @return true
 */
function sendMail(string $subject, string $body, array|string $to, string $contentType = 'text/plain', ?string $attachment = null, int $priority = Email::PRIORITY_NORMAL, array|string $cc = null, array|string $bcc = null)
{
    $MAILER_DSN = sprintf('smtp://%s:%s@%s:%s', getOption('smtpusername'), getOption('smtppassword'), getOption('smtphost'), getOption('smtpport'));

    $mailer = new Mailer(Transport::fromDsn($MAILER_DSN));

    // 文本邮件还是html邮件
    $contentFunc = $contentType != 'text/plain' ? 'html' : 'text';

    $email = (new Email())
        ->from(new Address(getOption('smtpfrommail'), getOption('smtpfromname')))
        ->to(...(array) $to)
        ->priority($priority)
        ->subject($subject)
        ->{$contentFunc}($body);

    if ($attachment && is_file($attachment)) {
        $email->attachFromPath($attachment);
    }

    if ($cc) {
        $email->cc(...(array) $cc);
    }
    if ($bcc) {
        $email->bcc(...(array) $bcc);
    }

    try {
        $mailer->send($email);

        return true;
    } catch (TransportExceptionInterface $ex) {
        throw new NeoException($ex->getMessage(), $ex->getCode());
    }
}

/**
 * 记录用户操作到日志表
 *
 * @param array $log 日志内容
 *
 * @return int 日志ID
 */
function actionLog(array $log)
{
    return BaseService::getLogModel()->save($log);
}

/**
 * 返回某个系统设置项
 *
 * @param string     $key     系统设置的某个项
 * @param null|mixed $default 没有获取到值时，可以返回一个默认值
 *
 * @return mixed 如果这个项目不存在，则返回NULL
 */
function getOption(string $key, $default = null)
{
    static $options = null;

    if (is_null($options)) {
        $options = FileCache::read('options', ABS_PATH . DIRECTORY_SEPARATOR . 'config');
    }
    $opt = $options[$key]['value'];

    if (is_null($opt)) {
        $opt = $default;
    }

    return $opt;
}

/**
 * 拼接URI
 *
 * @param mixed $uri
 *
 * @return string
 */
function baseURL(...$uri)
{
    return ABS_URL . '/' . ltrim(implode('', $uri), '/');
}

/**
 * 跳转
 */
function redirect()
{
    $args = func_get_args();

    $args[0] = $args[0] ?? '';

    if (! $args[0] || $args[0][0] === '/') {
        $args[0] = baseURL($args[0]);
    }

    Page::redirect(...$args);
}

/**
 * 人民币分转为人民币元
 *
 * @param int $fen
 *
 * @return float
 */
function fen2yuan($fen)
{
    return number_format($fen / 100, 2, '.', '');
}

/**
 * 判断当前页是什么
 *
 * @param string $page
 *
 * @return bool
 */
function isPage(string $page): bool
{
    return stripos(neo()->getRequest()->getRequestUri(), $page) === 1;
}
