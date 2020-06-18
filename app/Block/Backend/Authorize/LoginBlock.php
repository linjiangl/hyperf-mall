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
use App\Request\Backend\Authorize\LoginRequest;
use App\Service\Authorize\AdminAuthorizationService;
use Throwable;

class LoginBlock
{
    public function index(LoginRequest $request)
    {
        $post = $request->validated();
        try {
            $service = new AdminAuthorizationService();
            return $service->login($post['username'], $post['password']);
        } catch (Throwable $e) {
            throw new HttpException($e->getMessage(), $e->getCode());
        }
    }
}