<?php

declare(strict_types=1);
/**
 * Multi-user mall
 *
 * @link     https://mall.xcmei.com
 * @document https://mall.xcmei.com
 * @contact  8257796@qq.com
 */
namespace App\Core\Service\Order;

use App\Constants\State\Order\CartState;
use App\Core\Dao\Goods\GoodsSkuDao;
use App\Core\Dao\Order\CartDao;
use App\Core\Service\AbstractService;
use App\Core\Service\Goods\Stock\Change\StockCartService;
use App\Core\Service\Goods\Stock\StockChangeService;
use App\Exception\BadRequestException;
use App\Model\Cart;
use Exception;
use Hyperf\DbConnection\Db;

class CartService extends AbstractService
{
    protected string $dao = CartDao::class;

    /**
     * 添加购物车.
     */
    public function add(array $user, int $skuId, int $quantity = 1, array $append = []): Cart
    {
        $sku = (new GoodsSkuDao())->info($skuId);
        $data = [
            'goods_id' => $sku->goods_id,
            'quantity' => $quantity,
        ];
        if (! empty($append)) {
            $data = array_merge($data, $append);
        }

        Db::beginTransaction();
        try {
            // 创建购物车
            $cart = (new CartDao())->firstOrCreate([
                'user_id' => $user['id'],
                'goods_sku_id' => $sku->id,
            ], $data);

            /** @var StockCartService $stockChangeService */
            $stockChangeService = new StockChangeService(StockChangeService::STOCK_CART);
            if ($cart->wasRecentlyCreated) {
                // 创建占用库存
                $tmpCart = $cart->toArray();
                $tmpCart['sku'] = $sku->toArray();
                $stockChangeService->setParams(['cart' => $tmpCart])->created($user, $cart->id, '添加购物车');
            } else {
                // 购物车商品已存在，增加占用库存数量
                $data['quantity'] = $cart->quantity + $quantity;
                $cart->update($data);

                $tmpCart = $cart->toArray();
                $tmpCart['sku'] = $sku->toArray();
                $stockChangeService->setParams(['cart' => $tmpCart])->updated($user, $cart->id, '修改购物车');
            }

            Db::commit();
            return $cart;
        } catch (Exception $e) {
            Db::rollBack();
            throw new BadRequestException($e->getMessage());
        }
    }

    /**
     * 修改购物车.
     */
    public function modify(array $user, int $cartId, int $quantity = 1, array $append = []): Cart
    {
        $cart = (new CartDao())->getInfoByCondition([
            ['id', '=', $cartId],
            ['user_id', '=', $user['id']],
        ]);

        $data = [
            'quantity' => $quantity,
        ];
        if (! empty($append)) {
            $data = array_merge($data, $append);
        }

        Db::beginTransaction();
        try {
            // 修改购物车商品数量
            $cart->update($data);

            // 修改后的购物车商品数量
            $tmpCart = $cart->toArray();
            $tmpCart['sku'] = $cart->sku->toArray();

            // 修改占用库存
            /** @var StockCartService $stockChangeService */
            $stockChangeService = new StockChangeService(StockChangeService::STOCK_CART);
            $stockChangeService->setParams(['cart' => $tmpCart])->updated($user, $cart->id, '修改购物车');

            Db::commit();
            return $cart;
        } catch (Exception $e) {
            Db::rollBack();
            throw new BadRequestException($e->getMessage());
        }
    }

    /**
     * 删除购物车商品.
     * @throws Exception
     */
    public function delete(array $user, int $cartId): bool
    {
        $cart = (new CartDao())->getInfoByCondition([
            ['id', '=', $cartId],
            ['user_id', '=', $user['id']],
        ]);

        Db::beginTransaction();
        try {
            // 恢复占用库存
            /** @var StockCartService $stockChangeService */
            $stockChangeService = new StockChangeService(StockChangeService::STOCK_CART);
            $stockChangeService->recovery($user, $cart->id);

            // 删除购物车商品
            $cart->delete();

            Db::commit();
            return true;
        } catch (Exception $e) {
            Db::rollBack();
            throw new BadRequestException($e->getMessage());
        }
    }

    /**
     * 清空购物车商品
     */
    public function clear(array $user): bool
    {
        $condition = [
            'user_id' => $user['id'],
            'is_show' => CartState::IS_SHOW_TRUE,
        ];
        $dao = new CartDao();
        $cartList = $dao->getListByCondition($condition);

        Db::beginTransaction();
        try {
            // 恢复占用库存
            /** @var StockCartService $stockChangeService */
            $stockChangeService = new StockChangeService(StockChangeService::STOCK_CART);
            foreach ($cartList as $item) {
                $stockChangeService->recovery($user, $item['id']);
            }

            // 删除购物车商品
            $dao->deleteByCondition($condition);

            Db::commit();
            return true;
        } catch (Exception $e) {
            Db::rollBack();
            throw new BadRequestException($e->getMessage());
        }
    }

    /**
     * 购车车结算.
     */
    public function settlement(array $user, array $selectIds)
    {
        $dao = new CartDao();
        return $dao->getListByCondition([
            'user_id' => $user['id'],
            'id' => $selectIds,
        ], $dao->setMapWith()->getMapWith('settlement', self::class));
    }
}