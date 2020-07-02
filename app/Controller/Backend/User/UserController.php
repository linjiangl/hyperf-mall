<?php

declare(strict_types=1);
/**
 * Multi-user mall
 *
 * @link     https://store.yii.red
 * @document https://document.store.yii.red
 * @contact  8257796@qq.com
 */
namespace App\Controller\Backend\User;

use App\Block\Backend\User\UserBlock;
use App\Controller\AbstractController;
use Hyperf\HttpServer\Contract\RequestInterface;

class UserController extends AbstractController
{
    public function index(RequestInterface $request)
    {
        return (new UserBlock())->index($request);
    }

    public function show(RequestInterface $request, $id)
    {
        return (new UserBlock())->show($request, $id);
    }

    public function disabled(RequestInterface $request)
    {
        $this->setActionName('禁用用户');
        return $request->getAttribute('admin');
    }
}
