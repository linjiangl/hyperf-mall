<?php

declare(strict_types=1);
/**
 * Multi-user mall
 *
 * @link     https://store.yii.red
 * @document https://document.store.yii.red
 * @contact  8257796@qq.com
 */
namespace App\Controller\Backend\Admin;

use App\Block\Backend\Admin\AdminBlock;
use App\Controller\AbstractController;

class AdminController extends AbstractController
{
    protected $block = AdminBlock::class;
}
