<?php

declare(strict_types=1);
/**
 * Multi-user mall
 *
 * @link     https://store.yii.red
 * @document https://document.store.yii.red
 * @contact  8257796@qq.com
 */
namespace App\Core\Dao\System;

use App\Core\Dao\AbstractDao;
use App\Model\District;

class DistrictDao extends AbstractDao
{
    protected $model = District::class;

    protected $noAllowActions = [];

    protected $notFoundMessage = '地区不存在';
}