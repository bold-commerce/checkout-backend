<?php

namespace Tests\Unit\Controllers;

use App\Http\Controllers\InstallController;
use App\Constants\API;
use App\Constants\Errors;
use App\Exceptions\AbstractCheckoutException;
use App\Exceptions\ApiCallExceptions\SendApiRequestException;
use App\Exceptions\InvalidEnvironmentException;
use App\Exceptions\ResourceMissingException;
use App\Models\Shop;
use App\Services\ApiService;
use App\Services\EndpointService;
use App\Services\ShopApiTokenService;
use App\Services\ShopAssetsService;
use App\Services\ShopService;
use App\Services\ShopUrlService;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Mockery as M;
use Tests\TestCase;
use Tests\Mocks\ApiMockData;

class InstallControllerTest extends TestCase
{
    protected InstallController $installController;

    /** @var Request|M\Mock */
    protected $requestMock;
    protected $mockApiService;
    protected $mockEndpointService;
    protected $mockResourceMissingException;
    protected $mockAbstractCheckoutException;
    protected $mockInstallController;
    protected $mockShopService;
    protected $mockShopApiTokenService;
    protected $mockShopUrlService;
    protected $mockShopAssetsService;
    protected Shop $shopFactory;

    public function setUp(): void
    {
        parent::setUp();
        $this->requestMock = M::mock(Request::class);
        $this->mockApiService = M::mock(ApiService::class);
        $this->mockEndpointService = M::mock(EndpointService::class);
        $this->mockResourceMissingException = M::mock(ResourceMissingException::class);
        $this->mockAbstractCheckoutException = M::mock(AbstractCheckoutException::class);

        $this->mockShopService = M::mock(ShopService::class);
        $this->mockShopApiTokenService = M::mock(ShopApiTokenService::class);
        $this->mockShopUrlService = M::mock(ShopUrlService::class);
        $this->mockShopAssetsService = M::mock(ShopAssetsService::class);
        $this->mockInstallController = M::mock(InstallController::class, [
            $this->mockEndpointService,
            $this->mockApiService,
            $this->mockShopService
        ])->makePartial();
        $this->installController = new InstallController($this->mockEndpointService, $this->mockApiService, $this->mockShopService);
    }

    public function testInitWithMissingApiEnvironments()
    {
        $expected = [
            'statusCode' => Response::HTTP_INTERNAL_SERVER_ERROR
        ];

        $exceptionMock = new InvalidEnvironmentException('Error');

        $this->mockInstallController->shouldReceive('getApiEnvironment')
        ->andThrow($exceptionMock);

        $response = $this->mockInstallController->init();

        $result = [
            'statusCode' => $response->getStatusCode()
        ];

        $this->assertEquals(
            $expected,
            $result
        );
    }

    public function testInitWithApiEnvironments()
    {
        $expected = [
            'statusCode' => RESPONSE::HTTP_FOUND
        ];

        $apiEnv = [
            'APP_URL' => 'exampleapp.com',
            'DEVELOPER_CLIENT_ID' =>  'test_client_id',
            'DEVELOPER_CLIENT_SECRET' => 'test_client_secret',
            'DEVELOPER_REDIRECT_URL' => 'test_redirect_url',
            'API_V2_AUTH_DASH_URL' => 'test_api_dash_url',
            'API_V2_OAUTH_TOKEN_URL' => 'test_oauth_url',
            'API_V2_SCOPES' => implode(',', API::API_V2_SCOPES),
        ];

        $this->mockInstallController->shouldReceive('getApiEnvironment')
            ->once()
            ->andReturn($apiEnv);


        $response = $this->mockInstallController->init();

        $result = [
            'statusCode' => $response->getStatusCode()
        ];

        $this->assertEquals(
            $expected,
            $result
        );
    }

    public function testInstallWithSuccess()
    {
        $expected = [
            'statusCode' => Response::HTTP_CREATED,
            'message' => 'Shop install successful'
        ];

        $apiEnv = [
            'APP_URL' => 'exampleapp.com',
            'DEVELOPER_CLIENT_ID' =>  'test_client_id',
            'DEVELOPER_CLIENT_SECRET' => 'test_client_secret',
            'DEVELOPER_REDIRECT_URL' => 'test_redirect_url',
            'API_V2_AUTH_DASH_URL' => 'test_api_dash_url',
            'API_V2_OAUTH_TOKEN_URL' => 'test_oauth_url',
            'API_V2_SCOPES' => implode(',', API::API_V2_SCOPES),
        ];


        $options['form_params'] = [
            'client_id' =>  $apiEnv['DEVELOPER_CLIENT_ID'],
            'client_secret' => $apiEnv['DEVELOPER_CLIENT_SECRET'],
            'code' => 'test_code',
            'grant_type' => 'authorization_code',
        ];

        $shopInfos = [
            'shop' => [
                'platform_domain' => ApiMockData::SHOPS_V1_INFO['shop_domain'],
                'custom_domain' => ApiMockData::SHOPS_V1_INFO['custom_domain'],
                'platform_type' => ApiMockData::SHOPS_V1_INFO['platform_slug'],
                'platform_identifier' => ApiMockData::SHOPS_V1_INFO['shop_identifier'],
                'shop_name' => ApiMockData::SHOPS_V1_INFO['store_name'],
                'support_email' => ApiMockData::SHOPS_V1_INFO['admin_email'],
            ],
            'token' => 'test_token',
            'urls' => [
                'back_to_cart_url' => ApiMockData::SHOPS_V1_INFO['custom_domain'],
                'back_to_store_url' => ApiMockData::SHOPS_V1_INFO['custom_domain'],
                'login_url' => ApiMockData::SHOPS_V1_INFO['custom_domain'] . '/login.php',
                'logo_url' => 'https://static.boldcommerce.com/images/logo/bold_logo_red.svg',
                'favicon_url' => 'https://static.boldcommerce.com/images/logo/bold.ico'
            ],
            'asset' => '3 pages template',
        ];

        $results = [];

        $this->mockInstallController->shouldReceive('getApiEnvironment')
            ->once()
            ->andReturn($apiEnv);

        $this->requestMock->shouldReceive('query')
            ->once()
            ->andReturn(['code' => 'test_code']);

        $this->mockApiService->shouldReceive('sendApiRequest')
            ->once()
            ->andReturn(['content' => ['access_token' => 'test_token']]);

        $this->mockEndpointService->shouldReceive('shopInfos')
            ->with('test_token')
            ->once()
            ->andReturn(['code' => Response::HTTP_OK, 'content' => ApiMockData::SHOPS_V1_INFO]);

        $results['shop'] = $this->mockShopService->shouldReceive('createShopFromArray')
             ->with($shopInfos['shop'], ApiMockData::SHOPS_V1_INFO)
             ->andReturnSelf();
        $results['token'] = '[hidden]';

        $this->mockShopApiTokenService->shouldReceive('insertToken')
            ->with($results['shop'], 'test_token')
            ->andReturnSelf();

        $this->mockShopUrlService->shouldReceive('insertUrls')
            ->with($results['shop'], $shopInfos['urls'])
            ->andReturnSelf();

        $this->mockShopAssetsService->shouldReceive('insertAsset')
            ->with($results['shop'], $shopInfos['asset'])
            ->andReturnSelf();

        $this->mockShopService->shouldReceive('getReturnToCartUrl')
            ->with('jake-123456.bolddemos.ninja', 'bigcommerce')
            ->andReturnSelf();

        $response = $this->mockInstallController->install($this->requestMock);
        $result = [
            'message' => $response->getData()->message,
            'statusCode' => $response->getStatusCode()
        ];
        $this->assertEquals(
            $expected,
            $result
        );
    }
}
