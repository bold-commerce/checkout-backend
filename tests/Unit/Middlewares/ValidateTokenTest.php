<?php

namespace Tests\Unit\Middlewares;

use App\Exceptions\InvalidTokenException;
use App\Exceptions\ShopNotFoundException;
use App\Http\Middleware\ValidateToken;
use App\Models\Shop;
use App\Services\ShopApiTokenService;
use App\Services\ShopService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;
use Mockery as M;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Tests\TestCase;

class ValidateTokenTest extends TestCase
{
    protected ValidateToken $validateTokenMiddleware;

    /** @var Request|M\Mock */
    protected $requestMock;

    /** @var Closure|M\Mock */
    protected $closureMock;

    protected Shop $shopFactory;

    /** @var Shop|M\Mock */
    protected $shopMock;

    /** @var ShopService|M\Mock */
    protected $shopServiceMock;

    /** @var ShopApiTokenService|M\Mock */
    protected $shopApiTokenServiceMock;

    private string $fakeUrl;

    private const TEST_PASSED = 'test passed';
    private const FAKE_API_TOKEN = 'fakeAPItoken';

    public function setUp(): void
    {
        parent::setUp();

        $this->requestMock = M::mock(Request::class);
        $this->closureMock = function () {
            return self::TEST_PASSED;
        };
        $this->shopMock = M::mock(Shop::class);
        $this->shopServiceMock = M::mock(ShopService::class);
        $this->shopApiTokenServiceMock = M::mock(ShopApiTokenService::class);

        $this->validateTokenMiddleware = new ValidateToken();
        $this->fakeUrl = fake()->url();

        App::shouldReceive('make')->with(ShopService::class)->andReturn($this->shopServiceMock);
        App::shouldReceive('make')->with(ShopApiTokenService::class)->andReturn($this->shopApiTokenServiceMock);
    }

    public function testRequestHeaderReturnsEmptyStringForApiToken()
    {
        $exception = new InvalidTokenException();

        $this->requestMock->shouldReceive('header')->with('X-Bold-Api-Token', '')->andReturn('');
        $this->requestMock->shouldReceive('header')->with('X-Bold-Shop-Domain', '')->andReturn($this->fakeUrl);
        $this->shopServiceMock->shouldReceive('getShopByIdentifierOrDomain')->with($this->fakeUrl)->andReturn($this->shopMock);
        $this->shopApiTokenServiceMock->shouldReceive('verifyToken')->with($this->shopMock, '')->andThrow($exception);

        $this->expectException(HttpException::class);
        $this->validateTokenMiddleware->handle($this->requestMock, $this->closureMock);
    }

    public function testRequestHeaderReturnsEmptyStringForShopDomain()
    {
        $exception = new ShopNotFoundException();

        $this->requestMock->shouldReceive('header')->with('X-Bold-Api-Token', '')->andReturn(self::FAKE_API_TOKEN);
        $this->requestMock->shouldReceive('header')->with('X-Bold-Shop-Domain', '')->andReturn('');
        $this->shopServiceMock->shouldReceive('getShopByIdentifierOrDomain')->with('')->andThrow($exception);

        $this->expectException(HttpException::class);
        $this->validateTokenMiddleware->handle($this->requestMock, $this->closureMock);
    }

    public function testVerifyTokenOK()
    {
        $this->requestMock->shouldReceive('header')->with('X-Bold-Api-Token', '')->andReturn(self::FAKE_API_TOKEN);
        $this->requestMock->shouldReceive('header')->with('X-Bold-Shop-Domain', '')->andReturn($this->fakeUrl);
        $this->shopServiceMock->shouldReceive('getShopByIdentifierOrDomain')->with($this->fakeUrl)->andReturn($this->shopMock);
        $this->shopApiTokenServiceMock->shouldReceive('verifyToken')->with($this->shopMock, self::FAKE_API_TOKEN);

        $result = $this->validateTokenMiddleware->handle($this->requestMock, $this->closureMock);
        $this->assertEquals(self::TEST_PASSED, $result);
    }
}
