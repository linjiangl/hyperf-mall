<?php

declare(strict_types=1);
/**
 * Multi-user mall
 *
 * @link     https://www.doubi.site
 * @document https://doc.doubi.site
 * @contact  8257796@qq.com
 */

namespace HyperfTest\Cases;

use App\Model\User;
use App\Service\LoginService;
use HyperfTest\HttpTestCase;
use PHPUnit\Framework\TestCase;
use Hyperf\Testing\Client;

/**
 * @internal
 * @coversNothing
 */
class LoginTest extends HttpTestCase
{
	/**
	 * @var Client
	 */
	protected $client;

	public function __construct($name = null, array $data = [], $dataName = '')
	{
		parent::__construct($name, $data, $dataName);
		$this->client = make(Client::class);
	}

	public function testExample()
	{
		$this->assertTrue(true);

//		$res = $this->client->get('/');
//
//
//		$this->assertSame('Hello Hyperf.', $res['message']);
//		$this->assertSame('GET', $res['method']);

		redis()->select(0);
		redis()->set('aa', 11, 120);
		$value = redis()->get('aa');
		var_dump($value);

//		$res = $this->client->get('/', ['user' => 'developer']);
//
//		$this->assertSame(0, $res['code']);
//		$this->assertSame('developer', $res['data']['user']);
//
//		$res = $this->client->post('/', [
//			'user' => 'developer',
//		]);
//		$this->assertSame('Hello Hyperf.', $res['data']['message']);
//		$this->assertSame('POST', $res['data']['method']);
//		$this->assertSame('developer', $res['data']['user']);
//
//		$res = $this->client->json('/', [
//			'user' => 'developer',
//		]);
//		$this->assertSame('Hello Hyperf.', $res['data']['message']);
//		$this->assertSame('POST', $res['data']['method']);
//		$this->assertSame('developer', $res['data']['user']);
//
//		$res = $this->client->file('/', ['name' => 'file', 'file' => BASE_PATH . '/README.md']);
//
//		$this->assertSame('Hello Hyperf.', $res['data']['message']);
//		$this->assertSame('POST', $res['data']['method']);
//		$this->assertSame('README.md', $res['data']['file']);
	}
}
