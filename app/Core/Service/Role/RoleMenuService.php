<?php

declare(strict_types=1);
/**
 * Multi-user mall
 *
 * @link     https://store.yii.red
 * @document https://document.store.yii.red
 * @contact  8257796@qq.com
 */
namespace App\Core\Service\Role;

use App\Core\Dao\MenuDao;
use App\Core\Dao\Role\RoleDao;
use App\Core\Dao\Role\RoleMenuDao;
use App\Core\Service\AbstractService;
use App\Exception\InternalException;

class RoleMenuService extends AbstractService
{
    protected $dao = RoleMenuDao::class;

    /**
     * 更新权限菜单
     * @param int $roleId
     * @param array $menuIds
     */
    public function saveRoleMenus(int $roleId, array $menuIds): void
    {
        // 选中菜单
        $roleDao = new RoleDao();
        $role = $roleDao->info($roleId);

        $menuDao = new MenuDao();
        $menuCount = $menuDao->getCountByCondition([['id', 'in', $menuIds]]);
        if ($menuCount != count($menuIds)) {
            throw new InternalException('菜单数据有误');
        }

        // 菜单数据处理
        $dao = new RoleMenuDao();
        $roleMenus = $dao->getListByCondition([['role_id', '=', $role->id]]);
        $deleteMenuIds = [];
        $installMenuIds = $menuIds;
        if (! empty($roleMenus)) {
            $oldMenuIds = array_column($roleMenus, 'menu_id');
            $deleteMenuIds = array_diff($oldMenuIds, $menuIds);
            $installMenuIds = array_diff($menuIds, $oldMenuIds);
        }

        // 删除未选中的历史数据
        if (! empty($deleteMenuIds)) {
            $dao->deleteMenusByRoleId($role->id, $deleteMenuIds);
        }

        // 增加选中的数据
        if (! empty($installMenuIds)) {
            sort($installMenuIds);
            $dao->saveRoleMenus($roleId, $installMenuIds);
        }
    }
}
