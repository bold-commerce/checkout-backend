<?php

namespace Tests\Unit\Services;


use App\Constants\API;
use App\Constants\Paths;
use App\Exceptions\ApiCallExceptions\AddAuthenticatedCustomerApiCallException;
use App\Exceptions\ApiCallExceptions\CustomerInfosApiCallException;
use App\Exceptions\ApiCallExceptions\DeleteAddressApiCallException;
use App\Exceptions\ApiCallExceptions\DeleteAuthenticatedCustomerApiCallException;
use App\Exceptions\ApiCallExceptions\InitializeOrderApiCallException;
use App\Exceptions\ApiCallExceptions\InitializeShopifyOrderFromAdminApiCallException;
use App\Exceptions\ApiCallExceptions\ResumeOrderApiCallException;
use App\Exceptions\ApiCallExceptions\ShopInfosApiCallException;
use App\Models\FrontendShop;
use App\Models\Shop;
use App\Services\ApiService;
use App\Services\EndpointService;
use App\Services\ExperienceService;
use App\Services\ShopService;
use Illuminate\Support\Facades\Config;
use Mockery as M;
use Symfony\Component\HttpFoundation\Request;
use Tests\TestCase;

class EndpointServiceTest extends TestCase
{

    protected ApiService $apiServiceMock;
    protected ExperienceService $experienceServiceMock;
    protected ShopService $shopServiceMock;
    protected EndpointService $endpointService;
    protected Shop $shopModel;
    protected FrontendShop $frontendShopModel;

    protected $token = 'test';
    protected $requestParams = [
        'headers' => [
            'authorization' => API::HEADER_AUTHORIZATION_VALUE_BEARER . 'test',
            'Content-Type' => API::HEADER_CONTENT_TYPE_VALUE_JSON
        ],
        'body' => []
    ];

    protected function setUp(): void
    {
        parent::setUp();
        Config::set('services.bold_checkout.api_url', 'https://example.com');
        Config::set('services.bold_checkout.checkout_url', 'https://example.com');
        Config::set('services.bold_checkout.api_path', 'base/path');
        $this->apiServiceMock = M::mock(ApiService::class);
        $this->experienceServiceMock = M::mock(ExperienceService::class);
        $this->shopServiceMock = M::mock(ShopService::class);
        $this->shopModel = M::mock(Shop::class);
        $this->frontendShopModel = M::mock(FrontendShop::class);
        $this->shopServiceMock->allows()->getInstance()->andReturns($this->frontendShopModel);
        $this->frontendShopModel->allows()->getToken()->andReturns($this->token);

        $this->endpointService = new EndpointService($this->apiServiceMock, $this->experienceServiceMock, $this->shopServiceMock);

    }

    public function testInitializeOrderWithoutAnyError() {

        $url = 'https://test.com/init';
        $this->apiServiceMock->allows()->getOrdersApiUrl(Paths::INIT_PATH)->andReturns($url);
        $this->apiServiceMock->allows()->getRequestOptions($this->token, [])->andReturns($this->requestParams);
        $expected = ['code' => 200];
        $this->apiServiceMock->allows()->sendApiRequest(Request::METHOD_POST,$url, $this->requestParams, true )->andReturns($expected);
        $result = $this->endpointService->initializeOrder([]);
        $this->assertEquals($expected, $result);

    }

    public function testInitializeOrderWithError() {

        $url = 'https://test.com/init';
        $this->apiServiceMock->allows()->getOrdersApiUrl(Paths::INIT_PATH)->andReturns($url);
        $expected = ['code' => 404];
        $this->apiServiceMock->allows()->getRequestOptions($this->token, [])->andReturns($this->requestParams);
        $this->expectException(InitializeOrderApiCallException::class);
        $this->apiServiceMock->allows()->sendApiRequest(Request::METHOD_POST,$url, $this->requestParams, true )->andReturns($expected);
        $this->endpointService->initializeOrder([]);
    }

    public function testResumeOrderWithoutAnyError() {
        $publicOrderId = '1234';
        $url = 'https://test.com/resume';
        $this->apiServiceMock->allows()->getOrdersApiUrl(Paths::RESUME_PATH)->andReturns($url);
        $this->apiServiceMock->allows()->getRequestOptions($this->token, ['public_order_id' => $publicOrderId])->andReturns($this->requestParams);
        $expected = ['code' => 200];
        $this->apiServiceMock->allows()->sendApiRequest(Request::METHOD_POST,$url, $this->requestParams, true )->andReturns($expected);
        $result = $this->endpointService->resumeOrder($publicOrderId);
        $this->assertEquals($expected, $result);
    }

    public function testResumeOrderWithError() {
        $publicOrderId = '1234';
        $url = 'https://test.com/resume';
        $this->apiServiceMock->allows()->getOrdersApiUrl(Paths::RESUME_PATH)->andReturns($url);
        $this->apiServiceMock->allows()->getRequestOptions($this->token, ['public_order_id' => $publicOrderId])->andReturns($this->requestParams);
        $expected = ['code' => 404];
        $this->expectException(ResumeOrderApiCallException::class);
        $this->apiServiceMock->allows()->sendApiRequest(Request::METHOD_POST,$url, $this->requestParams, true )->andReturns($expected);
        $this->endpointService->resumeOrder($publicOrderId);
    }

    public function testInitializeShopifyAdminOrderWithEmptyResumableLinkAndWithoutAnyError() {
        $variant = 0;
        $url = 'https://test.com/init';
        $this->experienceServiceMock->allows()->convertVariantListToCartItems($variant)->andReturns([]);
        $this->apiServiceMock->allows()->getRequestOptions($this->token, ['cart_items' => []])->andReturns($this->requestParams);
        $this->apiServiceMock->allows()->getOrdersApiUrl(Paths::INIT_PATH)->andReturns($url);
        $expected = ['code' => 200];
        $this->apiServiceMock->allows()->sendApiRequest(Request::METHOD_POST,$url, $this->requestParams, true )->andReturns($expected);
        $result = $this->endpointService->initializeShopifyAdminOrder($variant, '');
        $this->assertEquals($expected, $result);
    }

    public function testInitializeShopifyAdminOrderWithResumableLinkAndWithoutAnyError() {
        $variant = 0;
        $reumableLink = 'https://test-link.com';
        $url = 'https://test.com/init';
        $this->experienceServiceMock->allows()->convertVariantListToCartItems($variant)->andReturns([]);
        $this->apiServiceMock->allows()->getRequestOptions($this->token, ['cart_items' => [] , 'resumable_link' => $reumableLink ])->andReturns($this->requestParams);
        $this->apiServiceMock->allows()->getOrdersApiUrl(Paths::INIT_PATH)->andReturns($url);
        $expected = ['code' => 200];
        $this->apiServiceMock->allows()->sendApiRequest(Request::METHOD_POST,$url, $this->requestParams, true )->andReturns($expected);
        $result = $this->endpointService->initializeShopifyAdminOrder($variant, $reumableLink) ;
        $this->assertEquals($expected, $result);
    }

    public function testInitializeShopifyAdminOrderWithError() {
        $variant = 0;
        $reumableLink = 'https://test-link.com';
        $url = 'https://test.com/init';
        $this->experienceServiceMock->allows()->convertVariantListToCartItems($variant)->andReturns([]);
        $this->apiServiceMock->allows()->getRequestOptions($this->token, ['cart_items' => [] , 'resumable_link' => $reumableLink ])->andReturns($this->requestParams);
        $this->apiServiceMock->allows()->getOrdersApiUrl(Paths::INIT_PATH)->andReturns($url);
        $expected = ['code' => 404];
        $this->apiServiceMock->allows()->sendApiRequest(Request::METHOD_POST,$url, $this->requestParams, true )->andReturns($expected);
        $this->expectException(InitializeShopifyOrderFromAdminApiCallException::class);
        $this->endpointService->initializeShopifyAdminOrder($variant, $reumableLink) ;
    }

    public function testShopInfosWithoutAnyError() {
        $token = '1234';
        $url = 'https://test.com/info';
        $this->apiServiceMock->allows()->getRequestOptions($token, [])->andReturns($this->requestParams);
        $this->apiServiceMock->allows()->getShopInfosUrl()->andReturns($url);
        $expected = ['code' => 200];
        $this->apiServiceMock->allows()->sendApiRequest(Request::METHOD_GET, $url, $this->requestParams )->andReturns($expected);
        $result = $this->endpointService->shopInfos($token);
        $this->assertEquals($expected, $result);
    }

    public function testShopInfosWithError() {
        $token = '1234';
        $url = 'https://test.com/info';
        $this->apiServiceMock->allows()->getRequestOptions($token, [])->andReturns($this->requestParams);
        $this->apiServiceMock->allows()->getShopInfosUrl()->andReturns($url);
        $expected = ['code' => 404];
        $this->apiServiceMock->allows()->sendApiRequest(Request::METHOD_GET, $url, $this->requestParams )->andReturns($expected);
        $this->expectException(ShopInfosApiCallException::class);
        $this->endpointService->shopInfos($token);
    }

    public function testRetrieveAuthenticatedCustomerInfosWithoutAnyError() {
        $customerIdentifier = 'test';
        $url = 'https://test.com/info';
        $this->apiServiceMock->allows()->getRequestOptions($this->token, [])->andReturns($this->requestParams);
        $this->apiServiceMock->allows()->getCustomerInfosUrl($customerIdentifier)->andReturns($url);
        $expected = ['code' => 200];
        $this->apiServiceMock->allows()->sendApiRequest(Request::METHOD_GET, $url, $this->requestParams )->andReturns($expected);
        $result = $this->endpointService->retrieveAuthenticatedCustomerInfos($customerIdentifier);
        $this->assertEquals($expected, $result);
    }

    public function testRetrieveAuthenticatedCustomerInfosWithError() {
        $customerIdentifier = 'test';
        $url = 'https://test.com/info';
        $this->apiServiceMock->allows()->getRequestOptions($this->token, [])->andReturns($this->requestParams);
        $this->apiServiceMock->allows()->getCustomerInfosUrl($customerIdentifier)->andReturns($url);
        $expected = ['code' => 404];
        $this->apiServiceMock->allows()->sendApiRequest(Request::METHOD_GET, $url, $this->requestParams )->andReturns($expected);
        $this->expectException(CustomerInfosApiCallException::class);
        $this->endpointService->retrieveAuthenticatedCustomerInfos($customerIdentifier);
    }

    public function testAddAuthenticatedCustomerWithoutAnyError() {
        $publicOrderId = 'test';
        $customer = ['customer_id' => '1'];
        $url = 'https://test.com/info';
        $this->apiServiceMock->allows()->getRequestOptions($this->token, $customer)->andReturns($this->requestParams);
        $this->apiServiceMock->allows()->getAddAuthenticatedCustomerUrl($publicOrderId)->andReturns($url);
        $expected = ['code' => 200];
        $this->apiServiceMock->allows()->sendApiRequest(Request::METHOD_POST, $url, $this->requestParams, true )->andReturns($expected);
        $result = $this->endpointService->addAuthenticatedCustomer($publicOrderId, $customer);
        $this->assertEquals($expected, $result);
    }

    public function testAddAuthenticatedCustomerWithError() {
        $publicOrderId = 'test';
        $customer = ['customer_id' => '1'];
        $url = 'https://test.com/authenticated';
        $this->apiServiceMock->allows()->getRequestOptions($this->token, $customer)->andReturns($this->requestParams);
        $this->apiServiceMock->allows()->getAddAuthenticatedCustomerUrl($publicOrderId)->andReturns($url);
        $expected = ['code' => 404];
        $this->apiServiceMock->allows()->sendApiRequest(Request::METHOD_POST, $url, $this->requestParams, true )->andReturns($expected);
        $this->expectException(AddAuthenticatedCustomerApiCallException::class);
        $this->endpointService->addAuthenticatedCustomer($publicOrderId, $customer);
    }

    public function testDeleteCustomerWithoutAnyError() {
        $publicOrderId = 'test';
        $url = 'https://test.com/deleteCustomer';
        $this->apiServiceMock->allows()->getRequestOptions($this->token)->andReturns($this->requestParams);
        $this->apiServiceMock->allows()->getCustomerUrl($publicOrderId)->andReturns($url);
        $expected = ['code' => 200];
        $this->apiServiceMock->allows()->sendApiRequest(Request::METHOD_DELETE, $url, $this->requestParams, true )->andReturns($expected);
        $result = $this->endpointService->deleteCustomer($publicOrderId);
        $this->assertEquals($expected, $result);
    }

    public function testDeleteCustomerWithError() {
        $publicOrderId = 'test';
        $url = 'https://test.com/deleteCustomer';
        $this->apiServiceMock->allows()->getRequestOptions($this->token)->andReturns($this->requestParams);
        $this->apiServiceMock->allows()->getCustomerUrl($publicOrderId)->andReturns($url);
        $expected = ['code' => 404];
        $this->apiServiceMock->allows()->sendApiRequest(Request::METHOD_DELETE, $url, $this->requestParams, true )->andReturns($expected);
        $this->expectException(DeleteAuthenticatedCustomerApiCallException::class);
        $this->endpointService->deleteCustomer($publicOrderId);
    }

    public function testDeleteShippingAddressWithoutAnyError() {
        $publicOrderId = 'test';
        $fieldToDelete = 'shipping';
        $jwtToken = 'jwt-test';
        $url = 'https://test.com/deleteAddress';
        $this->apiServiceMock->allows()->getRequestOptions($jwtToken)->andReturns($this->requestParams);
        $this->apiServiceMock->allows()->getStorefrontApiUrl(Paths::DELETE_SHIPPING_ADDRESS_PATH, $publicOrderId)->andReturns($url);
        $expected = ['code' => 200];
        $this->apiServiceMock->allows()->sendApiRequest(Request::METHOD_DELETE, $url, $this->requestParams, true )->andReturns($expected);
        $result = $this->endpointService->deleteAddress($publicOrderId, $jwtToken, $fieldToDelete);
        $this->assertEquals($expected, $result);
    }

    public function testDeleteShippingAddressWithError() {
        $publicOrderId = 'test';
        $fieldToDelete = 'shipping';
        $jwtToken = 'jwt-test';
        $url = 'https://test.com/deleteAddress';
        $this->apiServiceMock->allows()->getRequestOptions($jwtToken)->andReturns($this->requestParams);
        $this->apiServiceMock->allows()->getStorefrontApiUrl(Paths::DELETE_SHIPPING_ADDRESS_PATH, $publicOrderId)->andReturns($url);
        $expected = ['code' => 404];
        $this->apiServiceMock->allows()->sendApiRequest(Request::METHOD_DELETE, $url, $this->requestParams, true )->andReturns($expected);
        $this->expectException(DeleteAddressApiCallException::class);
        $this->endpointService->deleteAddress($publicOrderId, $jwtToken, $fieldToDelete);
    }

    public function testDeleteBillingAddressWithoutAnyError() {
        $publicOrderId = 'test';
        $fieldToDelete = 'billing';
        $jwtToken = 'jwt-test';
        $url = 'https://test.com/deleteAddress';
        $this->apiServiceMock->allows()->getRequestOptions($jwtToken)->andReturns($this->requestParams);
        $this->apiServiceMock->allows()->getStorefrontApiUrl(Paths::DELETE_BILLING_ADDRESS_PATH, $publicOrderId)->andReturns($url);
        $expected = ['code' => 200];
        $this->apiServiceMock->allows()->sendApiRequest(Request::METHOD_DELETE, $url, $this->requestParams, true )->andReturns($expected);
        $result = $this->endpointService->deleteAddress($publicOrderId, $jwtToken, $fieldToDelete);
        $this->assertEquals($expected, $result);
    }

    public function testDeleteBillingAddressWithError() {
        $publicOrderId = 'test';
        $fieldToDelete = 'billing';
        $jwtToken = 'jwt-test';
        $url = 'https://test.com/deleteAddress';
        $this->apiServiceMock->allows()->getRequestOptions($jwtToken)->andReturns($this->requestParams);
        $this->apiServiceMock->allows()->getStorefrontApiUrl(Paths::DELETE_BILLING_ADDRESS_PATH, $publicOrderId)->andReturns($url);
        $expected = ['code' => 404, 'content' => ['message' => 'error occurred']];
        $this->apiServiceMock->allows()->sendApiRequest(Request::METHOD_DELETE, $url, $this->requestParams, true )->andReturns($expected);
        $this->expectException(DeleteAddressApiCallException::class);
        $this->endpointService->deleteAddress($publicOrderId, $jwtToken, $fieldToDelete);
    }
}
