<?php

namespace Tests\Unit\Services;

use App\Constants\API;
use App\Constants\Fields;
use App\Constants\Paths;
use App\Constants\SupportedPlatforms;
use App\Exceptions\InvalidPlatformException;
use App\Exceptions\ShopInstanceException;
use App\Exceptions\ShopNotFoundException;
use App\Facades\Jwt;
use App\Models\JwtModel;
use App\Services\EndpointService;
use Firebase\JWT\ExpiredException;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Session;
use App\Models\FrontendShop;
use App\Models\Shop;
use App\Services\ExperienceService;
use App\Services\ShopService;
use Tests\TestCase;
use Mockery as M;

class ExperienceServiceTest extends TestCase
{
    /** @var M\Mock|ShopService  */
    protected $shopServiceMock;

    /** @var M\Mock|Shop */
    protected $frontendShopMock;

    protected ExperienceService $experienceService;
    protected string $platform = 'platform';
    protected string $domain = 'mysite.domain.com';
    protected string $resumableURL;

    public function setUp(): void
    {
        parent::setUp();
        $this->shopServiceMock = M::mock(ShopService::class);
        $this->experienceService = new ExperienceService($this->shopServiceMock);
        $this->resumableURL = sprintf('%s/%s/%s/experience/%s',
            config('APP_URL', env('APP_URL')),
            $this->platform,
            $this->domain,
            Paths::RESUME_PATH
        );
    }

    public function testGetResumableOrderUrlFunctionReturnsExpectedValue() {
        $shopMock = M::mock(Shop::class);
        $shopMock->shouldReceive('getPlatformType')->andReturn($this->platform);
        $shopMock->shouldReceive('getPlatformDomain')->andReturn($this->domain);
        $result = $this->experienceService->getResumableOrderUrl($shopMock);
        $this->assertEquals($this->resumableURL, $result);
    }

    /**
     * @dataProvider  getInitializationOrderDataDataProviderFlowIdIsNull
     */
    public function testGetInitializationOrderDataReturnsExpectedValue($params, $expected) {
        $frontendShopMock = M::mock(FrontendShop::class);
        $shopMock = M::mock(Shop::class);
        $this->shopServiceMock->shouldReceive('getInstance')->andReturn($frontendShopMock);
        $frontendShopMock->shouldReceive('getShop')->andReturn($shopMock);
        $frontendShopMock->shouldReceive('getAssets')->andReturn(collect([]));
        $shopMock->shouldReceive('getPlatformType')->andReturn($this->platform);
        $shopMock->shouldReceive('getPlatformDomain')->andReturn($this->domain);

        $result = $this->experienceService->getInitializationOrderData($params);
        $this->assertEquals($expected, $result);
    }

    public function testGetCheckoutUrlFunctionReturnsExpectedValue() {
        Config::set('services.bold_checkout.api_url', 'http://someurl.com/api/v2');
        $result = $this->experienceService->getCheckoutUrl();
        $this->assertEquals('http://someurl.com', $result);
    }

    public function testGetAssetsUrlFunctionReturnsExpectedValue() {
        Config::set('services.bold_checkout.assets_url', 'http://someurl.com/folder_a/folder_b');
        $result = $this->experienceService->getAssetsUrl();
        $this->assertEquals('http://someurl.com/folder_a/folder_b', $result);
    }

    public function testGetFlagFunctionReturnsExpectedCollectionOfFlagsFromString() {
        Config::set('flags', 'LOG,LOADTIME');
        $result = $this->experienceService->getFlags();
        $this->assertEquals(collect(['LOG', 'LOADTIME']), $result);
    }

    /** @dataProvider isCheckoutExperiencePageDataProvider */
    public function testFunctionIsCheckoutExperiencePageReturnsExpectedValue($requestPage, $expected) {
        $result = $this->experienceService->isCheckoutExperiencePage($requestPage);
        $this->assertEquals($expected, $result);
    }

    /** @dataProvider isShippingRequiredDataProvider */
    public function testIsShippingRequiredReturnsExpectedValue($lineItems, $expected) {
        $result = $this->experienceService->isShippingRequired($lineItems);
        $this->assertEquals($expected, $result);
    }

    /** @dataProvider convertVariantListToCartItemsDataProvider */
    public function testConvertVariantListToCartItemsReturnsExpectedArrayOfLineItems($variant, $expected) {
        $result = $this->experienceService->convertVariantListToCartItems($variant);
        $this->assertEquals($expected, $result);
    }

    /** @dataProvider shouldClearOrderDataProviderTokenIsNull */
    public function testShouldClearOrderTokenIsNullReturnsExpectedValue($publicOrderID, $requestData, $expected) {
        $result = $this->experienceService->shouldClearOrder($publicOrderID, $requestData);
        $this->assertEquals($expected, $result);
    }

    public function testShouldClearOrderJwtDecodeThrowError() {
        $publicOrderID = 'somePUBLICorderID';
        $requestData = [
            'token' => 'some token',
        ];
        Jwt::shouldReceive('decodeToken')->with($requestData['token'])->andThrow(ExpiredException::class);
        $this->expectException(\Exception::class);
        $this->experienceService->shouldClearOrder($publicOrderID, $requestData);
    }

    public function testShouldClearOrderBothPublicOrderIdAreDifferent() {
        $publicOrderID = 'somePUBLICorderID';
        $requestData = ['token' => 'some token'];
        $payload = new JwtModel('authType', ['public_order_id' => 'someOTHERpublicORDERid']);

        Jwt::shouldReceive('decodeToken')->with($requestData['token'])->andReturn($payload);
        $result = $this->experienceService->shouldClearOrder($publicOrderID, $requestData);
        $this->assertTrue($result);
    }

    public function testShouldClearOrderCachedTokenValueIsPending() {
        $publicOrderID = 'somePUBLICorderID';
        $requestData = ['token' => 'some token'];
        $payload = new JwtModel('authType', ['public_order_id' => 'somePUBLICorderID']);
        $cachedToken = 'pending';
        $payloadPublicOrderID = $payload->toArray()['payload']['public_order_id'];

        Jwt::shouldReceive('decodeToken')->with($requestData['token'])->andReturn($payload);
        Cache::shouldReceive('pull')->with('headless::'.$payloadPublicOrderID)->andReturn($cachedToken);
        $result = $this->experienceService->shouldClearOrder($publicOrderID, $requestData);
        $this->assertFalse($result);
    }

    public function testShouldClearOrderCachedTokenValueIsDifferentPending() {
        $publicOrderID = 'somePUBLICorderID';
        $requestData = ['token' => 'some token'];
        $payload = new JwtModel('authType', ['public_order_id' => 'somePUBLICorderID']);
        $cachedToken = 'some other state';
        $payloadPublicOrderID = $payload->toArray()['payload']['public_order_id'];

        Jwt::shouldReceive('decodeToken')->with($requestData['token'])->andReturn($payload);
        Cache::shouldReceive('pull')->with('headless::'.$payloadPublicOrderID)->andReturn($cachedToken);
        $this->expectException(\Exception::class);
        $this->experienceService->shouldClearOrder($publicOrderID, $requestData);
    }

    /** @dataProvider cleanOrderDataProvider */
    public function testCleanOrder($publicOrderID, $response, $expected) {
        $endpointServiceMock = M::mock(EndpointService::class);
        $this->app->instance(EndpointService::class, $endpointServiceMock);
        App::shouldReceive('make')->andReturn($endpointServiceMock);
        $jwt = $response['content']['data']['jwt_token'];

        $endpointServiceMock->expects('deleteCustomer')->with($publicOrderID)->times($expected['deleteCustomer']);
        $endpointServiceMock->expects('deleteAddress')
            ->with($publicOrderID, $jwt, Fields::SHIPPING_IN_RESPONSE)
            ->times($expected['deleteShipping']);
        $endpointServiceMock->expects('deleteAddress')
            ->with($publicOrderID, $jwt, Fields::BILLING_IN_RESPONSE)
            ->times($expected['deleteBilling']);

        $this->experienceService->cleanOrder($publicOrderID, $response);
    }

    /** @dataProvider getReturnToCheckoutUrlCorrectPathsDataProvider */
    public function testGetReturnToCheckoutUrlCorrectData($cartID, $publicOrderID, $cartParams, $platform, $expected) {
        $frontendShopMock = M::mock(FrontendShop::class);
        $this->shopServiceMock->shouldReceive('getInstance')->andReturn($frontendShopMock);
        $frontendShopMock->shouldReceive('getShop->getPlatformType')->andReturn($platform);
        $frontendShopMock->shouldReceive('getShop->getPlatformDomain')->andReturn($this->domain);
        $frontendShopMock->shouldReceive('getUrls->getBackToCartUrl')->andReturn($this->domain.'/cart');
        $frontendShopMock->shouldReceive('getUrls->getBackToStoreUrl')->andReturn($this->domain.'/store');

        $result = $this->experienceService->getReturnToCheckoutUrl($cartID, $publicOrderID, $cartParams);
        $this->assertEquals($expected, $result);
    }

    public function testGetReturnToCheckoutUrlUnsupportedPlatform() {
        $cartID = 'WCcartID';
        $publicOrderID = 'WCpublicORDERid';
        $cartParams = [];

        $frontendShopMock = M::mock(FrontendShop::class);
        $this->shopServiceMock->shouldReceive('getInstance')->andReturn($frontendShopMock);
        $frontendShopMock->shouldReceive('getShop->getPlatformType')->andReturn($this->platform);
        $frontendShopMock->shouldReceive('getShop->getPlatformDomain')->andReturn($this->domain);
        $frontendShopMock->shouldReceive('getUrls->getBackToCartUrl')->andReturn($this->domain.'/cart');
        $frontendShopMock->shouldReceive('getUrls->getBackToStoreUrl')->andReturn($this->domain.'/store');

        $this->expectException(InvalidPlatformException::class);
        $this->experienceService->getReturnToCheckoutUrl($cartID, $publicOrderID, $cartParams);
    }

    public function testGetReturnToCheckoutUrlUnsupportedPlatformCartParamsNotEmpty() {
        $cartID = 'SHOPIFYcartID';
        $publicOrderID = 'SHOPIFYpublicORDERid';
        $cartParams = ['key' => 'value'];

        $frontendShopMock = M::mock(FrontendShop::class);
        $this->shopServiceMock->shouldReceive('getInstance')->andReturn($frontendShopMock);
        $frontendShopMock->shouldReceive('getShop->getPlatformType')->andReturn($this->platform);
        $frontendShopMock->shouldReceive('getShop->getPlatformDomain')->andReturn($this->domain);
        $frontendShopMock->shouldReceive('getUrls->getBackToCartUrl')->andReturn($this->domain.'/cart');
        $frontendShopMock->shouldReceive('getUrls->getBackToStoreUrl')->andReturn($this->domain.'/store');

        $this->expectException(InvalidPlatformException::class);
        $this->experienceService->getReturnToCheckoutUrl($cartID, $publicOrderID, $cartParams);
    }

    /*
     * DATA PROVIDERS
     */
    private function getReturnToCheckoutUrlCorrectPathsDataProvider(): array {
        return [
            'Woo_Commerce' => [
                'cartID' => 'WCcartID',
                'public_order_id' => 'WCpublicORDERid',
                'cartParams' => [],
                'platform' => SupportedPlatforms::WOOCOMMERCE_PLATFORM_TYPE,
                'expected' => '',
            ],
            'Commerce_Tools' => [
                'cartID' => 'CTcartID',
                'public_order_id' => 'CTpublicORDERid',
                'cartParams' => [],
                'platform' => SupportedPlatforms::COMMERCETOOLS_PLATFORM_TYPE,
                'expected' => 'mysite.domain.com/store/boldplatform/proxy/begin-checkout?shop=mysite.domain.com&cart_id=CTcartID&return_url=mysite.domain.com%2Fcart&platform=commercetools&public_order_id=CTpublicORDERid',
            ],
            'Bold_Platform' => [
                'cartID' => 'BPcartID',
                'public_order_id' => 'BPpublicORDERid',
                'cartParams' => [],
                'platform' => SupportedPlatforms::BOLD_PLATFORM_TYPE,
                'expected' => 'mysite.domain.com/store/boldplatform/proxy/begin-checkout?shop=mysite.domain.com&cart_id=BPcartID&return_url=mysite.domain.com%2Fcart&platform=bold_platform&public_order_id=BPpublicORDERid',
            ],
            'Big_Commerce' => [
                'cartID' => 'BCcartID',
                'public_order_id' => 'BCpublicORDERid',
                'cartParams' => [],
                'platform' => SupportedPlatforms::BIGCOMMERCE_PLATFORM_TYPE,
                'expected' => 'mysite.domain.com/store/boldplatform/proxy/begin-checkout?shop=mysite.domain.com&cart_id=BCcartID&return_url=mysite.domain.com%2Fcart&platform=bigcommerce&public_order_id=BCpublicORDERid',
            ],
            'Shopify' => [
                'cartID' => 'SHOPIFYcartID',
                'public_order_id' => 'SHOPIFYpublicORDERid',
                'cartParams' => [],
                'platform' => SupportedPlatforms::SHOPIFY_PLATFORM_TYPE,
                'expected' => '/apps/checkout/begin-checkout?shop=mysite.domain.com&cart_id=SHOPIFYcartID&return_url=mysite.domain.com%2Fcart&platform=shopify&public_order_id=SHOPIFYpublicORDERid',
            ],
            'Woo_Commerce cart_params' => [
                'cartID' => 'WCcartID',
                'public_order_id' => 'WCpublicORDERid',
                'cartParams' => ['key' => 'value'],
                'platform' => SupportedPlatforms::WOOCOMMERCE_PLATFORM_TYPE,
                'expected' => '',
            ],
            'Commerce_Tools cart_params' => [
                'cartID' => 'CTcartID',
                'public_order_id' => 'CTpublicORDERid',
                'cartParams' => ['key' => 'value'],
                'platform' => SupportedPlatforms::COMMERCETOOLS_PLATFORM_TYPE,
                'expected' => 'mysite.domain.com/store/boldplatform/proxy/begin-checkout?shop=mysite.domain.com&cart_id=CTcartID&return_url=mysite.domain.com%2Fcart&platform=commercetools&public_order_id=CTpublicORDERid&cart_params%5Bkey%5D=value',
            ],
            'Bold_Platform cart_params' => [
                'cartID' => 'BPcartID',
                'public_order_id' => 'BPpublicORDERid',
                'cartParams' => ['key' => 'value'],
                'platform' => SupportedPlatforms::BOLD_PLATFORM_TYPE,
                'expected' => 'mysite.domain.com/store/boldplatform/proxy/begin-checkout?shop=mysite.domain.com&cart_id=BPcartID&return_url=mysite.domain.com%2Fcart&platform=bold_platform&public_order_id=BPpublicORDERid&cart_params%5Bkey%5D=value',
            ],
            'Big_Commerce cart_params' => [
                'cartID' => 'BCcartID',
                'public_order_id' => 'BCpublicORDERid',
                'cartParams' => ['key' => 'value'],
                'platform' => SupportedPlatforms::BIGCOMMERCE_PLATFORM_TYPE,
                'expected' => 'mysite.domain.com/store/boldplatform/proxy/begin-checkout?shop=mysite.domain.com&cart_id=BCcartID&return_url=mysite.domain.com%2Fcart&platform=bigcommerce&public_order_id=BCpublicORDERid&cart_params%5Bkey%5D=value',
            ],
            'Shopify with cart_params' => [
                'cartID' => 'SHOPIFYcartID',
                'public_order_id' => 'SHOPIFYpublicORDERid',
                'cartParams' => ['key' => 'value'],
                'platform' => SupportedPlatforms::SHOPIFY_PLATFORM_TYPE,
                'expected' => '/apps/checkout/begin-checkout?shop=mysite.domain.com&cart_id=SHOPIFYcartID&return_url=mysite.domain.com%2Fcart&platform=shopify&public_order_id=SHOPIFYpublicORDERid&cart_params%5Bkey%5D=value',
            ],
        ];
    }

    private function cleanOrderDataProvider(): array {
        return [
            'no customer, no billing & shipping address' => [
                'public_order_id' => 'somePUBLICorderID',
                'response' => [
                    'content' => [
                        'data' => [
                            'application_state' => [
                                'customer' => [],
                                'addresses' => [],
                            ],
                            'jwt_token' => 'someJWTtoken',
                        ],
                    ],
                ],
                'expected' => [
                    'deleteCustomer' => 0,
                    'deleteBilling' => 0,
                    'deleteShipping' => 0,
                ],
            ],
            'Customer, no billing & shipping address' => [
                'public_order_id' => 'somePUBLICorderID',
                'response' => [
                    'content' => [
                        'data' => [
                            'application_state' => [
                                'customer' => [
                                    'email_address' => 'some.email@example.com',
                                ],
                                'addresses' => [],
                            ],
                            'jwt_token' => 'someJWTtoken',
                        ],
                    ],
                ],
                'expected' => [
                    'deleteCustomer' => 1,
                    'deleteBilling' => 0,
                    'deleteShipping' => 0,
                ],
            ],
            'Billing address, no customer & shipping address' => [
                'public_order_id' => 'somePUBLICorderID',
                'response' => [
                    'content' => [
                        'data' => [
                            'application_state' => [
                                'customer' => [],
                                'addresses' => [
                                    'billing' => [
                                        'key' => 'value',
                                    ],
                                ],
                            ],
                            'jwt_token' => 'someJWTtoken',
                        ],
                    ],
                ],
                'expected' => [
                    'deleteCustomer' => 0,
                    'deleteBilling' => 1,
                    'deleteShipping' => 0,
                ],
            ],
            'Shipping address, no customer & billing address' => [
                'public_order_id' => 'somePUBLICorderID',
                'response' => [
                    'content' => [
                        'data' => [
                            'application_state' => [
                                'customer' => [],
                                'addresses' => [
                                    'shipping' => [
                                        'key' => 'value',
                                    ],
                                ],
                            ],
                            'jwt_token' => 'someJWTtoken',
                        ],
                    ],
                ],
                'expected' => [
                    'deleteCustomer' => 0,
                    'deleteBilling' => 0,
                    'deleteShipping' => 1,
                ],
            ],
            'Billing & Shipping address, no customer' => [
                'public_order_id' => 'somePUBLICorderID',
                'response' => [
                    'content' => [
                        'data' => [
                            'application_state' => [
                                'customer' => [],
                                'addresses' => [
                                    'billing' => [
                                        'key' => 'value',
                                    ],
                                    'shipping' => [
                                        'key' => 'value',
                                    ],
                                ],
                            ],
                            'jwt_token' => 'someJWTtoken',
                        ],
                    ],
                ],
                'expected' => [
                    'deleteCustomer' => 0,
                    'deleteBilling' => 1,
                    'deleteShipping' => 1,
                ],
            ],
            'Customer & Shipping address, no Billing address' => [
                'public_order_id' => 'somePUBLICorderID',
                'response' => [
                    'content' => [
                        'data' => [
                            'application_state' => [
                                'customer' => [
                                    'email_address' => 'some.email@example.com',
                                ],
                                'addresses' => [
                                    'shipping' => [
                                        'key' => 'value',
                                    ],
                                ],
                            ],
                            'jwt_token' => 'someJWTtoken',
                        ],
                    ],
                ],
                'expected' => [
                    'deleteCustomer' => 1,
                    'deleteBilling' => 0,
                    'deleteShipping' => 1,
                ],
            ],
            'Customer & Billing address, No Shipping address' => [
                'public_order_id' => 'somePUBLICorderID',
                'response' => [
                    'content' => [
                        'data' => [
                            'application_state' => [
                                'customer' => [
                                    'email_address' => 'some.email@example.com',
                                ],
                                'addresses' => [
                                    'billing' => [
                                        'key' => 'value',
                                    ],
                                ],
                            ],
                            'jwt_token' => 'someJWTtoken',
                        ],
                    ],
                ],
                'expected' => [
                    'deleteCustomer' => 1,
                    'deleteBilling' => 1,
                    'deleteShipping' => 0,
                ],
            ],
            'Customer, Shipping & Billing address' => [
                'public_order_id' => 'somePUBLICorderID',
                'response' => [
                    'content' => [
                        'data' => [
                            'application_state' => [
                                'customer' => [
                                    'email_address' => 'some.email@example.com',
                                ],
                                'addresses' => [
                                    'billing' => [
                                        'key' => 'value',
                                    ],
                                    'shipping' => [
                                        'key' => 'value',
                                    ],
                                ],
                            ],
                            'jwt_token' => 'someJWTtoken',
                        ],
                    ],
                ],
                'expected' => [
                    'deleteCustomer' => 1,
                    'deleteBilling' => 1,
                    'deleteShipping' => 1,
                ],
            ],
        ];
}

    private function shouldClearOrderDataProviderTokenIsNull(): array {
        return [
            'requestData token key not existing' => [
                'publicOrderID' => 'somePUBLICorderID',
                'requestData' => [],
                'expected' => true,
            ],
            'requestData token is null' => [
                'publicOrderID' => 'somePUBLICorderID',
                'requestData' => [
                    'token' => null,
                ],
                'expected' => true,
            ],
        ];
    }

    private function convertVariantListToCartItemsDataProvider(): array {
        return [
            'variant is empty string' => [
                'variant' => '',
                'expected' => [],
            ],
            'unique variant' => [
                'variant' => 'platformID:1',
                'expected' => [
                    0 => [
                        'platform_id' => 'platformID',
                        'quantity' => 1,
                        'line_item_key' => 'item0',
                    ],
                ],
            ],
            'multiple variants' => [
                'variant' => 'platformID_1:4,variantID_4:3',
                'expected' => [
                    0 => [
                        'platform_id' => 'platformID_1',
                        'quantity' => 4,
                        'line_item_key' => 'item0',
                    ],
                    1 => [
                        'platform_id' => 'variantID_4',
                        'quantity' => 3,
                        'line_item_key' => 'item1',
                    ],
                ],
            ],
        ];
    }

    private function isShippingRequiredDataProvider(): array {
        return [
            'line item empty' => [
                'lineItems' => [],
                'expected' => false,
            ],
            'line item null' => [
                'lineItems' => [
                    0 => null,
                ],
                'expected' => false,
            ],
            'product data null' => [
                'lineItems' => [
                    0 => [
                        'product_data' => null,
                    ],
                ],
                'expected' => false,
            ],
            'product data empty' => [
                'lineItems' => [
                    0 => [
                        'product_data' => [],
                    ],
                ],
                'expected' => false,
            ],
            'line item no requiring shipping key' => [
                'lineItems' => [
                    0 => [
                        'product_data' => [
                            'some key' => 'some value',
                        ],
                    ],
                ],
                'expected' => false,
            ],
            'line item requiring shipping key set to null' => [
                'lineItems' => [
                    0 => [
                        'product_data' => [
                            'some key' => 'some value',
                            'requires_shipping' => null,
                        ],
                    ],
                ],
                'expected' => false,
            ],
            'line item requiring shipping key set to empty' => [
                'lineItems' => [
                    0 => [
                        'product_data' => [
                            'some key' => 'some value',
                            'requires_shipping' => '',
                        ],
                    ],
                ],
                'expected' => false,
            ],
            'line item requiring shipping key set to false' => [
                'lineItems' => [
                    0 => [
                        'product_data' => [
                            'some key' => 'some value',
                            'requires_shipping' => false,
                        ],
                    ],
                ],
                'expected' => false,
            ],
            'line item requiring shipping key set to true' => [
                'lineItems' => [
                    0 => [
                        'product_data' => [
                            'some key' => 'some value',
                            'requires_shipping' => true,
                        ],
                    ],
                ],
                'expected' => true,
            ],
            'multiple line items - 1 requiring shipping key set to false' => [
                'lineItems' => [
                    0 => [
                        'product_data' => [
                            'some key' => 'some value',
                            'requires_shipping' => true,
                        ],
                    ],
                    1 => [
                        'product_data' => [
                            'some key' => 'some other value',
                            'requires_shipping' => false,
                        ],
                    ],
                ],
                'expected' => true,
            ],
        ];
    }

    private function isCheckoutExperiencePageDataProvider(): array {
        return [
            'prequestPageage value is not Experience' => [
                'requestPage' => 'some-page',
                'expected' => false,
            ],
            'requestPage value is in Experience' => [
                'requestPage' => Paths::PAYMENT_PATH,
                'expected' => true,
            ],
        ];
    }

    private function getInitializationOrderDataDataProviderFlowIdIsNull(): array {
        $resumableURL = 'http://test.experience.boldapps.net/platform/mysite.domain.com/experience/resume';
        return [
            'userAccessToken empty' => [
                'params' => [
                    'userAccessToken' => null,
                    'cart_id' => 'some-cart-ID',
                ],
                'expected' => [
                    'cart_id' => 'some-cart-ID',
                    'resumable_link' => $resumableURL,
                    'flow_id' => 'Bold Flow',
                ],
            ],
            'cartID empty' => [
                'params' => [
                    'userAccessToken' => 'userACCESStoken',
                    'cart_id' => null,
                ],
                'expected' => [
                    'access_token' => 'userACCESStoken',
                    'resumable_link' => $resumableURL,
                    'flow_id' => 'Bold Flow',
                ],
            ],
            'userAccessToken AND cartID empty' => [
                'params' => [
                    'userAccessToken' => null,
                    'cart_id' => null,
                ],
                'expected' => [
                    'resumable_link' => $resumableURL,
                    'flow_id' => 'Bold Flow',
                ],
            ],
            'userAccessToken AND cartID populated' => [
                'params' => [
                    'userAccessToken' => 'userACCESStoken',
                    'cart_id' => 'some-cart-ID',
                ],
                'expected' => [
                    'cart_id' => 'some-cart-ID',
                    'access_token' => 'userACCESStoken',
                    'resumable_link' => $resumableURL,
                    'flow_id' => 'Bold Flow',
                ],
            ],
        ];
    }
}
