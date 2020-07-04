<?php

declare(strict_types=1);
/**
 * Multi-user mall
 *
 * @link     https://store.yii.red
 * @document https://document.store.yii.red
 * @contact  8257796@qq.com
 */

use App\Exception\InternalException;
use Hyperf\Contract\StdoutLoggerInterface;
use Hyperf\Framework\Logger\StdoutLogger;
use Hyperf\HttpMessage\Stream\SwooleStream;
use Hyperf\HttpServer\Contract\RequestInterface;
use Hyperf\HttpServer\Contract\ResponseInterface;
use Hyperf\Logger\LoggerFactory;
use Hyperf\Redis\Redis;
use Hyperf\Utils\ApplicationContext;
use Psr\Container\ContainerInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Log\LoggerInterface;
use Psr\SimpleCache\CacheInterface;
use Swoole\WebSocket\Server as WebSocketServer;

/*
 * 容器实例
 */
if (! function_exists('container')) {
    function container(): ContainerInterface
    {
        return ApplicationContext::getContainer();
    }
}

/*
 * redis 客户端实例
 */
if (! function_exists('redis')) {
    function redis(): Redis
    {
        return container()->get(Redis::class);
    }
}

/*
 * websocket 实例
 */
if (! function_exists('websocket')) {
    function websocket(): WebSocketServer
    {
        return container()->get(WebSocketServer::class);
    }
}

/*
 * 缓存实例 简单的缓存
 */
if (! function_exists('cache')) {
    function cache(): CacheInterface
    {
        return container()->get(CacheInterface::class);
    }
}

/*
 * 控制台日志
 */
if (! function_exists('stdLog')) {
    function stdLog(): StdoutLogger
    {
        return container()->get(StdoutLoggerInterface::class);
    }
}

/*
 * 文件日志
 */
if (! function_exists('logger')) {
    function logger(): LoggerInterface
    {
        return container()->get(LoggerFactory::class)->make();
    }
}

if (! function_exists('request')) {
    function request(): RequestInterface
    {
        return container()->get(ServerRequestInterface::class);
    }
}

if (! function_exists('response')) {
    function response(): ResponseInterface
    {
        return container()->get(ResponseInterface::class);
    }
}

if (! function_exists('response_json')) {
    function response_json($data, string $message = '', int $code = 200)
    {
        $code = $code ?: 500;
        $message = $message ?: 'ok';
        $data = json_encode([
            'code' => $code,
            'message' => $message,
            'data' => $data,
        ], JSON_UNESCAPED_UNICODE);
        return response()->withAddedHeader('Content-Type', 'application/json')->withStatus($code)->withBody(new SwooleStream($data));
    }
}

if (! function_exists('get_action_name')) {
    function get_action_name(string $path): string
    {
        $name = substr(strchr($path, '/'), 1);
        return $name ?: 'index';
    }
}

if (! function_exists('get_validated_regex')) {
    /**
     * 获取验证规则正则表达式
     * @param string $option 选项
     * @param string $prefix 前缀
     * @return string
     */
    function get_validated_regex(string $option = 'mobile', string $prefix = 'regex:'): string
    {
        switch ($option) {
            case 'mobile':
                $regex = '/^1\d{10}$/';
                break;
            default:
                throw new InternalException("{$option}未定义表达式");
        }
        return $prefix . $regex;
    }
}
