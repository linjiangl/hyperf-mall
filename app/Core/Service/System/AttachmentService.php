<?php

declare(strict_types=1);
/**
 * Multi-user mall
 *
 * @link     https://mall.xcmei.com
 * @document https://mall.xcmei.com
 * @contact  8257796@qq.com
 */
namespace App\Core\Service\System;

use App\Constants\State\System\AttachmentState;
use App\Core\Dao\System\AttachmentDao;
use App\Core\Plugins\Bucket\SamplesBucket;
use App\Core\Service\AbstractService;
use App\Exception\NotFoundException;
use App\Model\Attachment;
use Throwable;

class AttachmentService extends AbstractService
{
    protected string $dao = AttachmentDao::class;

    public function getInfoByEncrypt(string $encrypt): ?Attachment
    {
        try {
            $dao = new AttachmentDao();
            return $dao->getInfoByEncrypt($encrypt);
        } catch (Throwable $e) {
            return null;
        }
    }

    /**
     * 保存上传文件信息.
     */
    public function createUpload(array $fileData, string $hash, string $key, string $system = AttachmentState::SYSTEM_QINIU): int
    {
        $config = config('custom')['attachment'];
        $encrypt = '';
        if ($fileData['size'] <= $config['encrypt_limit_size'] && file_exists($fileData['tmp_file'])) {
            $encrypt = md5_file($fileData['tmp_file']);
        }

        $data = [
            'system' => $system,
            'type' => $fileData['type'],
            'size' => $fileData['size'],
            'hash' => $hash,
            'key' => $key,
            'index' => $this->generateIndex($key),
            'encrypt' => $encrypt,
            'status' => AttachmentState::STATUS_ENABLED,
        ];
        return $this->create($data);
    }

    /**
     * 批量删除附件.
     */
    public function batchDelete(array $ids, string $system = AttachmentState::SYSTEM_QINIU): bool
    {
        $dao = new AttachmentDao();
        $keys = $dao->getColumnByCondition([
            ['id', 'in', $ids],
            ['system', '=', $system],
        ], 'key');

        if (empty($keys)) {
            throw new NotFoundException('资源不存在');
        }

        // 成功删除的资源
        $bucket = (new SamplesBucket())->make($system);
        $result = $bucket->batchDelete($keys);
        if (! empty($result['success'])) {
            $index = [];
            foreach ($result['success'] as $key) {
                $index[] = $this->generateIndex($key);
            }
            $dao->deleteByCondition([
                ['index', 'in', $index],
            ]);
        }
        return true;
    }

    /**
     * 文件失效.
     */
    public function failure(string $key)
    {
        $dao = new AttachmentDao();
        $info = $dao->getInfoByIndex($this->generateIndex($key));
        $info->status = AttachmentState::STATUS_DISABLED;
        $info->save();
    }

    /**
     * 文件批量失效.
     */
    public function batchFailure(array $oldKeys, array $newKeys)
    {
        $diff = array_diff($oldKeys, $newKeys);
        if (! empty($diff)) {
            $diffIndex = [];
            foreach ($diff as $item) {
                $diffIndex[] = $this->generateIndex($item);
            }
            $dao = new AttachmentDao();
            $dao->updateByCondition([
                ['index', 'in', $diffIndex],
            ], [
                'status' => AttachmentState::STATUS_DISABLED,
            ]);
        }
    }

    /**
     * 生成文件查询索引.
     */
    public function generateIndex(string $key): string
    {
        return md5($key);
    }
}
