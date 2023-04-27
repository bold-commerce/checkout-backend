<?php


use App\Constants\Constants;
use App\Facades\Logging;
use App\Facades\Session as CheckoutSession;
use App\Http\Controllers\AbstractController;
use App\Http\Controllers\ExperienceController;
use App\Models\ApiResponseContent;
use App\Models\FrontendShop;
use App\Models\Shop;
use App\Models\ShopUrl;
use App\Services\EndpointService;
use App\Services\EventsService;
use App\Services\ExperienceService;
use App\Services\ShopApiTokenService;
use App\Services\ShopAssetsService;
use App\Services\ShopService;
use App\Services\ShopUrlService;
use App\Services\UserService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Config;
use Illuminate\View\View;
use Mockery as M;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Tests\TestCase;


class ExperienceControllerTest extends TestCase
{
    protected Request $requestMock;
    protected ExperienceService $mockExperienceService;
    protected EndpointService $mockEndpointService;
    protected ShopService $mockShopService;
    protected ShopApiTokenService $mockShopApiTokenService;
    protected ShopAssetsService $mockShopAssetsService;
    protected ShopUrlService $mockShopUrlService;
    protected EventsService $mockEventsService;
    protected UserService $mockUserService;
    protected AbstractController $abstractController;
    protected FrontendShop $mockFrontendShop;
    protected ExperienceController $experienceController;
    protected ApiResponseContent $mockApiResponseContent;
    protected Shop $mockShop;

    public function setUp(): void
    {
        parent::setUp();

        Config::set('services.bold_checkout.api_url', 'https://example.com');
        Config::set('services.bold_checkout.checkout_url', 'https://example.com');
        Config::set('services.bold_checkout.api_path', 'base/path');
        Config::set('FLAGS', Constants::FLAG_LOADTIME);

        Carbon::setTestNow(Carbon::createFromFormat('Y-m-d H:i:s.u', '2023-03-20 15:15:15.151515'));

        $this->requestMock = M::mock(Request::class);
        $this->mockExperienceService = M::mock(ExperienceService::class);
        $this->mockEndpointService = M::mock(EndpointService::class);
        $this->mockShopService = M::mock(ShopService::class);
        $this->mockShopApiTokenService = M::mock(ShopApiTokenService::class);
        $this->mockShopUrlService = M::mock(ShopUrlService::class);
        $this->mockShopAssetsService = M::mock(ShopAssetsService::class);
        $this->mockEventsService = M::mock(EventsService::class);
        $this->mockUserService = M::mock(UserService::class);
        $this->mockFrontendShop = M::mock(FrontendShop::class);
        $this->mockApiResponseContent = M::mock(ApiResponseContent::class);
        $this->mockShop = M::mock(Shop::class);
        $this->experienceController = new ExperienceController(
            $this->mockExperienceService,
            $this->mockEndpointService,
            $this->mockShopService,
            $this->mockShopApiTokenService,
            $this->mockShopAssetsService,
            $this->mockShopUrlService,
            $this->mockEventsService,
            $this->mockUserService
        );

        $this->mockFrontendShop->shouldReceive('getID')->andReturn('123');
        $this->mockEventsService->shouldReceive('createEvent');
        $this->mockEventsService->shouldReceive('registerEventsList');
    }

    protected function tearDown(): void
    {
        parent::tearDown();
        M::close();
    }


    public function testInitWithEmptyParams(){

        $params = [''];
        $shopUrl = ShopUrl::factory()->create();
        $this->requestMock->shouldReceive('all')->times(1)->andReturn($params);
        $mockResponse = ['content' => [ 'data' => [ 'public_order_id' =>'test_id']]];
        $this->mockShopService->shouldReceive('getInstance')
            ->with()
            ->once()
            ->andReturns($this->mockFrontendShop);

        CheckoutSession::shouldReceive('put')->times(2);

        $this->mockExperienceService->shouldReceive('getInitializationOrderData')
            ->with($params)
            ->once()
            ->andReturns([]);

        $this->mockExperienceService->shouldReceive('getReturnToCheckoutUrl')
            ->with('test_id', '')
            ->once()
            ->andReturns('https://test.com');


        $this->mockEndpointService->shouldReceive('initializeOrder')
            ->with([])
            ->once()
            ->andReturns($mockResponse);

        $this->mockFrontendShop->shouldReceive('setReturnToCheckoutAfterLogin')->once()->andReturnSelf();
        $this->mockFrontendShop->shouldReceive('getShop')->times(2)->andReturn($this->mockShop);
        $this->mockFrontendShop->shouldReceive('getAssets')->once()->andReturn(collect([]));
        $this->mockFrontendShop->shouldReceive('getReturnToCheckoutAfterLogin')->once()->andReturn('');
        $this->mockFrontendShop->shouldReceive('getUrls')->twice()->andReturn($shopUrl);
        $this->mockFrontendShop->shouldReceive('getStylesheetUrl')->once()->andReturn('');
        $this->mockShop->shouldReceive('getPlatformType')->andReturn('bold_platform');
        $this->mockFrontendShop->shouldReceive('getUrls->getBackToCartUrl')->with()->andReturn('https://test.com');
        $this->mockApiResponseContent->shouldReceive('setApiResponseContent')->with($mockResponse);
        $this->mockApiResponseContent->shouldReceive('getPublicOrderID')->andReturn('test_id');
        $this->mockApiResponseContent->shouldReceive('getContent')->andReturn([ 'public_order_id' =>'test_id']);


        $this->mockExperienceService->shouldReceive('getFlags')
            ->with()
            ->once()
            ->andReturns(collect([]));

        $result = $this->experienceController->init($this->requestMock);
        $expected = $this->getDataForInitWithEmptyParamsView($this->mockShop, $shopUrl);
        $this->assertEquals($expected, $result);
    }

    public function testInitWithProperParamsAndPublicOrderId(){

        $params = [
            'public_order_id'=> 'test_id',
            'cart_id' => '123',
            'checkout_from_admin' => false,
            'variants' => ['property1' => 'value'],
            'customer_id' => '2',
            'checkout_local_time' => '234522',
            'return_url' => 'https://example.com'
        ];

        $shopUrl = ShopUrl::factory()->create();
        $this->requestMock->shouldReceive('all')->times(1)->andReturn($params);
        $dataResponse = ['data' => [ 'public_order_id' =>'test_id']];
        $mockResponse = ['content' => $dataResponse];
        $this->mockShopService->shouldReceive('getInstance')
            ->with()
            ->once()
            ->andReturns($this->mockFrontendShop);

        CheckoutSession::shouldReceive('put')->times(3);



        $this->mockEndpointService->shouldReceive('resumeOrder')->with($params['public_order_id']);

        $this->mockExperienceService->shouldReceive('getReturnToCheckoutUrl')
            ->with($params['public_order_id'], $params['cart_id'])
            ->once()
            ->andReturns('https://test.com');



        $this->mockFrontendShop->shouldReceive('setReturnToCheckoutAfterLogin')->andReturnSelf();
        $this->mockFrontendShop->shouldReceive('getShop')->times(2)->andReturn($this->mockShop);
        $this->mockFrontendShop->shouldReceive('getAssets')->andReturn(collect([]));
        $this->mockFrontendShop->shouldReceive('getReturnToCheckoutAfterLogin')->andReturn('');
        $this->mockFrontendShop->shouldReceive('getUrls')->andReturn($shopUrl);
        $this->mockFrontendShop->shouldReceive('getStylesheetUrl')->andReturn('');
        $this->mockShop->shouldReceive('getPlatformType')->andReturn('bold_platform');
        $this->mockFrontendShop->shouldReceive('getUrls->getBackToCartUrl')->with()->andReturn('https://test.com');
        $this->mockApiResponseContent->shouldReceive('setApiResponseContent')->with($mockResponse);

        $this->mockApiResponseContent->shouldReceive('getApiResponseContent')->andReturn(collect($mockResponse));

        $this->mockUserService->shouldReceive('addAuthenticatedUser')->with($params['customer_id'], [])->andReturn([ 'public_order_id' =>'test_id']);
        $this->mockApiResponseContent->shouldReceive('setApplicationState')->andReturnSelf();


        $this->mockExperienceService->shouldReceive('getFlags')
            ->with()
            ->once()
            ->andReturns(collect([]));

        $result = $this->experienceController->init($this->requestMock);
        $expected = $this->getDataForInitWithProperParamsAndPublicOrderId($this->mockShop, $shopUrl);
        $this->assertEquals($expected, $result);
    }

    public function testInitWithAdmin(){

        $params = [
            'public_order_id'=> '',
            'cart_id' => '123',
            'checkout_from_admin' => true,
            'variants' => 'property1',
            'customer_id' => '',
            'checkout_local_time' => '234522',
            'return_url' => 'https://example.com'
        ];

        $shopUrl = ShopUrl::factory()->create();
        $this->requestMock->shouldReceive('all')->times(1)->andReturn($params);
        $dataResponse = ['data' => [ 'public_order_id' =>'test_id']];
        $mockResponse = ['content' => $dataResponse];
        $this->mockShopService->shouldReceive('getInstance')
            ->with()
            ->once()
            ->andReturns($this->mockFrontendShop);

        CheckoutSession::shouldReceive('put')->times(3);


        $this->mockExperienceService->shouldReceive('getResumableOrderUrl')
            ->once()
            ->andReturns($params['return_url']);

        $this->mockEndpointService->shouldReceive('initializeShopifyAdminOrder')
            ->with($params['variants'], $params['return_url'])
            ->andReturn($mockResponse);

        $this->mockExperienceService->shouldReceive('getReturnToCheckoutUrl')
            ->with($params['public_order_id'], $params['cart_id'])
            ->once()
            ->andReturns('https://test.com');


        $this->mockFrontendShop->shouldReceive('getPlatformType')->andReturn('shopify');
        $this->mockFrontendShop->shouldReceive('setReturnToCheckoutAfterLogin')->andReturnSelf();
        $this->mockFrontendShop->shouldReceive('getShop')->times(3)->andReturn($this->mockShop);
        $this->mockFrontendShop->shouldReceive('getAssets')->andReturn(collect([]));
        $this->mockFrontendShop->shouldReceive('getReturnToCheckoutAfterLogin')->andReturn('');
        $this->mockFrontendShop->shouldReceive('getUrls')->andReturn($shopUrl);
        $this->mockFrontendShop->shouldReceive('getStylesheetUrl')->andReturn('');
        $this->mockShop->shouldReceive('getPlatformType')->with()->andReturn('shopify');
        $this->mockFrontendShop->shouldReceive('getUrls->getBackToStoreUrl')->with()->andReturn('https://test.com');
        $this->mockApiResponseContent->shouldReceive('setApiResponseContent')->with($mockResponse);

        $this->mockApiResponseContent->shouldReceive('getApiResponseContent')->andReturn(collect($mockResponse));

        $this->mockExperienceService->shouldReceive('getFlags')
            ->with()
            ->once()
            ->andReturns(collect([]));

        $result = $this->experienceController->init($this->requestMock);
        $expected = $this->getDataForInitFromAdmin($this->mockShop, $shopUrl);
        $this->assertEquals($expected, $result);
    }

    public function testInitWithHandledException(){

        $params = [
            'public_order_id'=> '',
            'cart_id' => '123',
            'checkout_from_admin' => true,
            'variants' => 'property1',
            'customer_id' => '',
            'checkout_local_time' => '234522',
            'return_url' => 'https://example.com'
        ];
        App::shouldReceive('environment')->with(Constants::APP_ENV_LOCAL)->once()->andReturns(true);
        $this->requestMock->shouldReceive('all')->times(1)->andReturn($params);
        $this->mockShopService->shouldReceive('getInstance')->once()->andReturns($this->mockFrontendShop);
        CheckoutSession::shouldReceive('put')->times(2);

        $this->mockFrontendShop->shouldReceive('getPlatformType')->andReturn('shopify');
        $this->mockFrontendShop->shouldReceive('getShop')->times(1)->andReturn($this->mockShop);

        $this->mockExperienceService->shouldReceive('getResumableOrderUrl')
            ->once()
            ->andReturns($params['return_url']);

        $this->mockEndpointService->shouldReceive('initializeShopifyAdminOrder')
            ->with($params['variants'], $params['return_url'])
            ->andThrow(new \GuzzleHttp\Exception\InvalidArgumentException('test error'));

        Logging::shouldReceive('exception')->once();
        $this->expectExceptionMessage('test error');
        $this->experienceController->init($this->requestMock);
    }

    public function testInitWithUnhandledException(){

        $params = [
            'public_order_id'=> '',
            'cart_id' => '123',
            'checkout_from_admin' => true,
            'variants' => 'property1',
            'customer_id' => '',
            'checkout_local_time' => '234522',
            'return_url' => 'https://example.com'
        ];


        App::shouldReceive('environment')->with(Constants::APP_ENV_LOCAL)->once()->andReturns(true);
        $this->requestMock->shouldReceive('all')->times(1)->andReturn($params);
        $this->mockShopService->shouldReceive('getInstance')->once()->andReturns($this->mockFrontendShop);
        CheckoutSession::shouldReceive('put')->times(2);

        $this->mockFrontendShop->shouldReceive('getPlatformType')->andReturn('shopify');
        $this->mockFrontendShop->shouldReceive('getShop')->times(1)->andReturn($this->mockShop);

        $this->mockExperienceService->shouldReceive('getResumableOrderUrl')
            ->once()
            ->andReturns($params['return_url']);

        $this->mockEndpointService->shouldReceive('initializeShopifyAdminOrder')
            ->with($params['variants'], $params['return_url'])
            ->andThrow(new Exception('test error'));

        Logging::shouldReceive('exception')->once();
        $this->expectExceptionMessage('test error');
        $this->experienceController->init($this->requestMock);
    }


    public function testResumeWithEmptyParams(){

        $params = [''];
        $this->requestMock->shouldReceive('all')->times(1)->andReturn($params);
        $this->mockShopService->shouldReceive('getInstance')
            ->with()
            ->once()
            ->andReturns($this->mockFrontendShop);

        CheckoutSession::shouldReceive('get')->andReturn('');
        CheckoutSession::shouldReceive('flush')->times(1);

        $this->mockExperienceService->shouldReceive('isCheckoutExperiencePage')
            ->with('resume')
            ->andReturn(true);

        $this->expectException(Exception::class);

        $this->experienceController->resume($this->requestMock, '', '' , 'resume');
    }

    public function testResumeWithPublicOrderId(){

        Config::set('FLAGS', [Constants::FLAG_LOADTIME]);
        $params = ['cart_id' => '23', 'public_order_id' => 'test_id'];
        $dataResponse = ['data' => [ 'public_order_id' =>'test_id', 'application_state' => ['is_processed' => false]]];
        $mockResponse = ['content' => $dataResponse];
        $shopUrl = ShopUrl::factory()->create();
        $this->requestMock->shouldReceive('all')->times(1)->andReturn($params);
        $this->mockShopService->shouldReceive('getInstance')
            ->with()
            ->once()
            ->andReturns($this->mockFrontendShop);

        CheckoutSession::shouldReceive('get')->andReturn('');
        CheckoutSession::shouldReceive('flush')->times(0);
        CheckoutSession::shouldReceive('put')->times(1);

        $this->mockExperienceService->shouldReceive('isCheckoutExperiencePage')
            ->with('resume')
            ->andReturn(true);

        $this->mockEndpointService->shouldReceive('resumeOrder')
            ->with($params['public_order_id'])
            ->andReturn($mockResponse);

        $this->mockExperienceService->shouldReceive('shouldClearOrder')
            ->with($params['public_order_id'], $params)
            ->once()
            ->andReturn(true);

        $this->mockExperienceService->shouldReceive('cleanOrder')
            ->with($params['public_order_id'], $mockResponse)
            ->once();

        $this->mockExperienceService->shouldReceive('getReturnToCheckoutUrl')
            ->with($params['cart_id'], $params['public_order_id'])
            ->once()
        ->andReturn('https://example.com/cart');

        $this->mockFrontendShop->shouldReceive('setReturnToCheckoutAfterLogin')
            ->with('https://example.com/cart')
            ->once();

        $this->mockFrontendShop->shouldReceive('getPlatformType')->andReturn('shopify');
        $this->mockFrontendShop->shouldReceive('setReturnToCheckoutAfterLogin')->andReturnSelf();
        $this->mockFrontendShop->shouldReceive('getShop')->times(2)->andReturn($this->mockShop);
        $this->mockFrontendShop->shouldReceive('getAssets')->andReturn(collect([]));
        $this->mockFrontendShop->shouldReceive('getReturnToCheckoutAfterLogin')->andReturn('');
        $this->mockFrontendShop->shouldReceive('getUrls')->andReturn($shopUrl);
        $this->mockFrontendShop->shouldReceive('getStylesheetUrl')->andReturn('');
        $this->mockShop->shouldReceive('getPlatformType')->with()->andReturn('shopify');
        $this->mockFrontendShop->shouldReceive('getUrls->getBackToStoreUrl')->with()->andReturn('https://test.com');
        $this->mockExperienceService->shouldReceive('getFlags')->with()->andReturn(collect([]));

        $expected = $this->getDataForResume($this->mockShop, $shopUrl);
        $result = $this->experienceController->resume($this->requestMock, '', '' , 'resume');

        $this->assertEquals($expected, $result);
    }

    public function testResumeWithWrongRequestPage(){

        $params = [''];
        $this->requestMock->shouldReceive('all')->times(0)->andReturn($params);
        $this->mockShopService->shouldReceive('getInstance')
            ->with()
            ->once()
            ->andReturns($this->mockFrontendShop);

        $this->mockExperienceService->shouldReceive('isCheckoutExperiencePage')
            ->with('resume')
            ->andReturn(false);

        $this->expectException(NotFoundHttpException::class);

        $this->experienceController->resume($this->requestMock, '', '' , 'resume');
    }

    public function testResumeWithAlreadyProcessOrder(){

        $sessionPublicOrderId = 'test_id';
        Config::set('FLAGS', [Constants::FLAG_LOADTIME]);
        $params = ['cart_id' => '23', 'public_order_id' => ''];
        $dataResponse = ['data' => [ 'public_order_id' =>'', 'application_state' => ['is_processed' => true]]];
        $mockResponse = ['content' => $dataResponse];
        $shopUrl = ShopUrl::factory()->create();
        $this->requestMock->shouldReceive('all')->times(1)->andReturn($params);
        $this->mockShopService->shouldReceive('getInstance')
            ->with()
            ->once()
            ->andReturns($this->mockFrontendShop);

        CheckoutSession::shouldReceive('get')->andReturn($sessionPublicOrderId);
        CheckoutSession::shouldReceive('flush')->times(1);
        CheckoutSession::shouldReceive('put')->times(0);

        $this->mockExperienceService->shouldReceive('isCheckoutExperiencePage')
            ->with('resume')
            ->andReturn(true);

        $this->mockEndpointService->shouldReceive('resumeOrder')
            ->with($sessionPublicOrderId)
            ->andReturn($mockResponse);

        $this->mockExperienceService->shouldReceive('shouldClearOrder')
            ->with($sessionPublicOrderId, $params)
            ->once()
            ->andReturn(true);

        $this->mockExperienceService->shouldReceive('getReturnToCheckoutUrl')
            ->with($params['cart_id'], $sessionPublicOrderId)
            ->once()
            ->andReturn('https://example.com/cart');

        $this->mockFrontendShop->shouldReceive('setReturnToCheckoutAfterLogin')
            ->with('https://example.com/cart')
            ->once();

        $this->mockFrontendShop->shouldReceive('getPlatformType')->andReturn('shopify');
        $this->mockFrontendShop->shouldReceive('setReturnToCheckoutAfterLogin')->andReturnSelf();
        $this->mockFrontendShop->shouldReceive('getShop')->times(2)->andReturn($this->mockShop);
        $this->mockFrontendShop->shouldReceive('getAssets')->andReturn(collect([]));
        $this->mockFrontendShop->shouldReceive('getReturnToCheckoutAfterLogin')->andReturn('');
        $this->mockFrontendShop->shouldReceive('getUrls')->andReturn($shopUrl);
        $this->mockFrontendShop->shouldReceive('getStylesheetUrl')->andReturn('');
        $this->mockShop->shouldReceive('getPlatformType')->with()->andReturn('shopify');
        $this->mockFrontendShop->shouldReceive('getUrls->getBackToStoreUrl')->with()->andReturn('https://test.com');
        $this->mockExperienceService->shouldReceive('getFlags')->with()->andReturn(collect([]));

        $expected = $this->getDataForResumeWithOrderProcessed($this->mockShop, $shopUrl);
        $result = $this->experienceController->resume($this->requestMock, '', '' , 'resume');

        $this->assertEquals($expected, $result);
    }

    public function testResumeWithGuzzleException(){

        $params = ['public_order_id' => 'test_id'];
        $this->requestMock->shouldReceive('all')->times(1)->andReturn($params);
        $this->mockShopService->shouldReceive('getInstance')
            ->with()
            ->once()
            ->andReturns($this->mockFrontendShop);

        App::shouldReceive('environment')->with(Constants::APP_ENV_LOCAL)->once()->andReturns(true);

        $this->mockExperienceService->shouldReceive('isCheckoutExperiencePage')
            ->with('resume')
            ->andReturn(true);


        $this->mockEndpointService->shouldReceive('resumeOrder')
            ->with($params['public_order_id'])
            ->andThrow(new \GuzzleHttp\Exception\InvalidArgumentException('test error'));

        Logging::shouldReceive('exception')->once();
        $this->expectExceptionMessage('test error');

        $this->experienceController->resume($this->requestMock, '', '' , 'resume');
    }


    private function getDataForInitWithEmptyParamsView($shop, $shopUrl): View
    {
        return view('experience/init',
            [
                'shop' => $shop,
                'shopAssets' => collect([]),
                'shopUrls' => [
                    'shopUrls' => $shopUrl,
                    'returnToCheckoutUrl' => '',
                    'returnToCart' => $shopUrl->getBackToStoreUrl(),
                ],
                'flags' => collect([]),
                'cartID' => '',
                'publicOrderID' => 'test_id',
                'initResponse' => [ 'data' => [ 'public_order_id' =>'test_id']],
                'keys' => [
                    'bugsnagApiKey' => '',
                ],
                'indicators' => [
                    'environment' => [
                        'type' => '',
                        'path' => 'base/path',
                        'url' => 'https://example.com',
                    ],
                    'enableConsole' => false,
                    'loadTimes' => [],
                ],
                'stylesheet' => '',
            ]);
    }

    private function getDataForInitWithProperParamsAndPublicOrderId($shop, $shopUrl): View
    {
        return view('experience/init',
            [
                'shop' => $shop,
                'shopAssets' => collect([]),
                'shopUrls' => [
                    'shopUrls' => $shopUrl,
                    'returnToCheckoutUrl' => '',
                    'returnToCart' => $shopUrl->getBackToStoreUrl(),
                ],
                'flags' => collect([]),
                'cartID' => '123',
                'publicOrderID' => '',
                'initResponse' => [ 'data' => [ 'application_state' => ['public_order_id' => 'test_id']]],
                'keys' => [
                    'bugsnagApiKey' => '',
                ],
                'indicators' => [
                    'environment' => [
                        'type' => '',
                        'path' => 'base/path',
                        'url' => 'https://example.com',
                    ],
                    'enableConsole' => false,
                    'loadTimes' => [],
                ],
                'stylesheet' => '',
            ]);
    }

    private function getDataForInitFromAdmin($shop, $shopUrl): View
    {
        return view('experience/init',
            [
                'shop' => $shop,
                'shopAssets' => collect([]),
                'shopUrls' => [
                    'shopUrls' => $shopUrl,
                    'returnToCheckoutUrl' => '',
                    'returnToCart' => $shopUrl->getBackToCartUrl(),
                ],
                'flags' => collect([]),
                'cartID' => '123',
                'publicOrderID' => 'test_id',
                'initResponse' => [ 'data' => ['public_order_id' => 'test_id']],
                'keys' => [
                    'bugsnagApiKey' => '',
                ],
                'indicators' => [
                    'environment' => [
                        'type' => '',
                        'path' => 'base/path',
                        'url' => 'https://example.com',
                    ],
                    'enableConsole' => false,
                    'loadTimes' => [],
                ],
                'stylesheet' => '',
            ]);
    }

    private function getDataForResume($shop, $shopUrl): View
    {
        return view('experience/init',
            [
                'shop' => $shop,
                'shopAssets' => collect([]),
                'shopUrls' => [
                    'shopUrls' => $shopUrl,
                    'returnToCheckoutUrl' => '',
                    'returnToCart' => $shopUrl->getBackToCartUrl(),
                ],
                'flags' => collect([]),
                'cartID' => '23',
                'publicOrderID' => 'test_id',
                'initResponse' =>['data' => ['public_order_id' =>'test_id', 'application_state' => ['is_processed' => false]]],
                'keys' => [
                    'bugsnagApiKey' => '',
                ],
                'indicators' => [
                    'environment' => [
                        'type' => '',
                        'path' => 'base/path',
                        'url' => 'https://example.com',
                    ],
                    'enableConsole' => false,
                    'loadTimes' => [],
                ],
                'stylesheet' => '',
            ]);
    }

    private function getDataForResumeWithOrderProcessed($shop, $shopUrl): View
    {
        return view('experience/init',
            [
                'shop' => $shop,
                'shopAssets' => collect([]),
                'shopUrls' => [
                    'shopUrls' => $shopUrl,
                    'returnToCheckoutUrl' => '',
                    'returnToCart' => $shopUrl->getBackToCartUrl(),
                ],
                'flags' => collect([]),
                'cartID' => '23',
                'publicOrderID' => '',
                'initResponse' =>['data' => ['public_order_id' =>'', 'jwt_token' => 'invalid' ,'application_state' => ['is_processed' => true]]],
                'keys' => [
                    'bugsnagApiKey' => '',
                ],
                'indicators' => [
                    'environment' => [
                        'type' => '',
                        'path' => 'base/path',
                        'url' => 'https://example.com',
                    ],
                    'enableConsole' => false,
                    'loadTimes' => [],
                ],
                'stylesheet' => '',
            ]);
    }

}
