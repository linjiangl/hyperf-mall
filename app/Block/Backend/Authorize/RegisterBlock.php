<?php

declare(strict_types=1);
/**
 * Multi-user mall
 *
 * @link     https://www.doubi.site
 * @document https://doc.doubi.site
 * @contact  8257796@qq.com
 */
namespace App\Block\Backend\Authorize;

use App\Exception\HttpException;
use App\Request\Backend\Authorize\RegisterRequest;
use App\Service\Authorize\AdminAuthorizationService;
use Throwable;

class RegisterBlock
{
    public function index(RegisterRequest $request)
    {
        $post = $request->validated();
        try {
            $service = new AdminAuthorizationService();
            return $service->register($post['username'], $post['password'], $post['confirm_password']);
        } catch (Throwable $e) {
            throw new HttpException($e->getMessage(), $e->getCode());
        }
    }
}