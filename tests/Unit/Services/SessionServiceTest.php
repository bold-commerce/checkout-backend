<?php

namespace Tests\Unit\Services;

use Illuminate\Support\Facades\Session;
use App\Models\FrontendShop;
use App\Models\Shop;
use App\Services\SessionService;
use App\Services\ShopService;
use Tests\TestCase;
use Mockery as M;

class SessionServiceTest extends TestCase
{
    /** @var M\Mock|ShopService  */
    protected $shopServiceMock;

    /** @var M\Mock|Shop */
    protected $frontendShopMock;

    protected SessionService $sessionService;
    protected string $platform = 'platform';
    protected string $domain = 'mysite.domain.com';
    protected string $key = 'somekey';

    public function setUp(): void
    {
        parent::setUp();
        $this->shopServiceMock = M::mock(ShopService::class);
        $this->sessionService = new SessionService($this->shopServiceMock);

        $this->frontendShopMock = M::mock(FrontendShop::class);
        $this->shopServiceMock->shouldReceive('getInstance')->andReturn($this->frontendShopMock);
        $this->frontendShopMock->shouldReceive('getShop->getPlatformType')->andReturn($this->platform);
        $this->frontendShopMock->shouldReceive('getShop->getPlatformDomain')->andReturn($this->domain);
    }

    public function testPutFunctionIsCalled()
    {
        $key = 'platform.mysite-domain-com.somekey';
        $value = 'some value';
        Session::expects('put')->with($key, $value);
        $this->sessionService->put($this->key, $value);
    }

    public function testAllReturnsExpectedArray()
    {
        $key = 'platform.mysite-domain-com';
        $expected = [
            'string' => 'string',
            'integer' => 123,
            'boolean' => false,
        ];

        Session::shouldReceive('get')
            ->with($key, [])
            ->andReturn($expected);
        $result = $this->sessionService->all();
        $this->assertEquals($expected, $result);
    }

    /**
     * @dataProvider getPullFunctionsWithKeyAndExpected
     */
    public function testGetPullWithKeyNoDefaultValueReturnsExpectedValue($key, $expected, $method)
    {
        Session::shouldReceive($method)
            ->with('platform.mysite-domain-com.'.$key, null)
            ->andReturn($expected);
        $result = $this->sessionService->$method($key);
        $this->assertEquals($expected, $result);
    }

    /**
     * @dataProvider getPullFunctionsWithKeyAndDefault
     */
    public function testGetWithKeyNotExistingWithDefaultValueSpecifiedReturnsDefault($key, $default, $method)
    {
        Session::shouldReceive($method)
            ->with('platform.mysite-domain-com.'.$key, $default)
            ->andReturn($default);
        $result = $this->sessionService->$method($key, $default);
        $this->assertEquals($default, $result);
    }

    /**
     * @dataProvider getPullFunctionsWithKeyDefaultAndExpected
     */
    public function testGetWithExistingKeyWithDefaultValueSpecifiedReturnsValue($key, $default, $expected, $method)
    {
        Session::shouldReceive($method)
            ->with('platform.mysite-domain-com.'.$key, $default)
            ->andReturn($expected);
        $result = $this->sessionService->$method($key, $default);
        $this->assertEquals($expected, $result);
    }

    /**
     * @dataProvider functionHasDataProvider
     */
    public function testHasExistingKeyReturnsTrue($key, $expected)
    {
        Session::shouldReceive('has')
            ->with('platform.mysite-domain-com.'.$key)
            ->andReturn($expected);
        $result = $this->sessionService->has($key);
        $this->assertEquals($expected, $result);
    }

    public function testForgetFunctionIsCalled()
    {
        $key = 'somekey';
        Session::expects('forget')
            ->with('platform.mysite-domain-com.'.$key);
        $this->sessionService->forget($key);
    }

    public function testFlushFunctionIsCalled()
    {
        $key = 'somekey';
        Session::expects('forget')
            ->with('platform.mysite-domain-com');
        Session::expects('save');
        $this->sessionService->flush($key);
    }

    private function getPullFunctionsWithKeyAndExpected(): array {
        return [
            'get_key_not_existing_returns_null' => [
                'key' => 'notexistingkey',
                'expected' => null,
                'method' => 'get',
            ],
            'get_key_existing_returns_value' => [
                'key' => 'existingkey',
                'expected' => 'some value',
                'method' => 'get',
            ],
            'pull_key_not_existing_returns_null' => [
                'key' => 'notexistingkey',
                'expected' => null,
                'method' => 'pull',
            ],
            'pull_key_existing_returns_value' => [
                'key' => 'existingkey',
                'expected' => 'some value',
                'method' => 'pull',
            ],
        ];
    }

    private function getPullFunctionsWithKeyAndDefault(): array {
        return [
            'get_key_not_existing_returns_null' => [
                'key' => 'notexistingkey',
                'default' => 'default value',
                'method' => 'get',
            ],
            'pull_key_not_existing_returns_null' => [
                'key' => 'notexistingkey',
                'default' => 'default value',
                'method' => 'pull',
            ],
        ];
    }

    private function getPullFunctionsWithKeyDefaultAndExpected(): array {
        return [
            'get_key_not_existing_returns_null' => [
                'key' => 'notexistingkey',
                'default' => 'default value',
                'expected' => 'expected value',
                'method' => 'get',
            ],
            'pull_key_not_existing_returns_null' => [
                'key' => 'notexistingkey',
                'default' => 'default value',
                'expected' => 'expected value',
                'method' => 'pull',
            ],
        ];
    }

    private function functionHasDataProvider(): array
    {
        return [
            'has_function_return_true' => [
                'key' => 'notexistingkey',
                'expected' => false,
            ],
            'has_function_return_false' => [
                'key' => 'existingkey',
                'expected' => true,
            ],
        ];
    }
}
