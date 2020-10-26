<?php

declare(strict_types=1);
/**
 * Multi-user mall
 *
 * @link     https://store.yii.red
 * @document https://document.store.yii.red
 * @contact  8257796@qq.com
 */
namespace App\Core\Block\Frontend\Authorize;

use App\Core\Block\BaseBlock;
use App\Core\Service\Authorize\UserAuthorizationService;
use App\Exception\HttpException;
use Hyperf\HttpServer\Contract\RequestInterface;
use Throwable;

class RegisterBlock extends BaseBlock
{
    public function register(RequestInterface $request): array
    {
        try {
            $data = $request->post();
            $service = new UserAuthorizationService();
            return $service->register($data['username'], $data['password'], $data['password_confirmation']);
        } catch (Throwable $e) {
            throw new HttpException($e->getMessage(), $e->getCode());
        }
    }
}
