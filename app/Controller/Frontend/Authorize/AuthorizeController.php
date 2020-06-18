<?php

declare(strict_types=1);
/**
 * Multi-user mall
 *
 * @link     https://www.doubi.site
 * @document https://doc.doubi.site
 * @contact  8257796@qq.com
 */
namespace App\Controller\Frontend\Authorize;

use App\Block\Frontend\Authorize\AuthorizeBlock;
use App\Controller\AbstractController;
use Hyperf\HttpServer\Contract\RequestInterface;

class AuthorizeController extends AbstractController
{
    public function index(RequestInterface $request)
    {
        return (new AuthorizeBlock())->index($request);
    }
}