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
use App\Model\Attachment;

class AttachmentDao extends AbstractDao
{
    protected $model = Attachment::class;

    protected $noAllowActions = [];

    protected $notFoundMessage = '附件不存在';

    public function getInfoByIndex(string $index): Attachment
    {
        return $this->getInfoByCondition([['index', '=', $index]]);
    }

    public function getInfoByMd5(string $md5): Attachment
    {
        return $this->getInfoByCondition([['md5', '=', $md5]]);
    }
}