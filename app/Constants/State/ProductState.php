<?php

declare(strict_types=1);
/**
 * Multi-user mall
 *
 * @link     https://store.yii.red
 * @document https://document.store.yii.red
 * @contact  8257796@qq.com
 */
namespace App\Constants\State;

class ProductState extends AbstractState
{
    // 状态
    const STATUS_DISABLED = 0;
    const STATUS_ENABLED = 1;

    // 类型
    const TYPE_GENERAL = 'general';
    const TYPE_COUPON = 'coupon';

    public static function getStatus(): array
    {
        return [
            self::STATUS_DISABLED => '下架',
            self::STATUS_ENABLED => '上架',
        ];
    }

    public static function getTypes(): array
    {
        return [
            self::TYPE_GENERAL => '普通',
            self::TYPE_COUPON => '优惠券',
        ];
    }
}