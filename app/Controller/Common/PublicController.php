<?php

declare(strict_types=1);
/**
 * Multi-user mall
 *
 * @link     https://www.doubi.site
 * @document https://doc.doubi.site
 * @contact  8257796@qq.com
 */
namespace App\Controller\Common;

use App\Controller\AbstractController;
use App\Exception\HttpException;
use App\Service\ThirdParty\CaptchaService;
use Hyperf\HttpServer\Contract\RequestInterface;

class PublicController extends AbstractController
{
    public function config()
    {
        return [
            'system' => 'h-mall'
        ];
    }

    public function captcha()
    {
        try {
            $service = new CaptchaService();
            return $service->generate();
        } catch (\Exception $e) {
            throw new HttpException($e->getMessage(), $e->getCode());
        }
    }

    public function upload(RequestInterface $request)
    {
        $file = $request->file('file');
        return $file->toArray();
    }
}
