<?php

namespace Tests\Unit\Services;

use App\Constants\API;
use App\Constants\Constants;
use App\Constants\Fields;
use App\Models\FrontendShop;
use App\Models\Shop;
use App\Services\ApiService;
use App\Services\ShopService;
use GuzzleHttp\Client;
use GuzzleHttp\Psr7\Response;
use Symfony\Component\HttpFoundation\Request;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Config;
use Mockery as M;
use Tests\TestCase;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;


class ApiServiceTest extends TestCase
{
    protected Client $clientDouble;
    protected Shop $shopModelDouble;
    protected FrontendShop $frontendShopModelDouble;
    protected ShopService $shopServiceDouble;
    protected ApiService $apiService;
    protected string $platformIdentifier;

    protected function setUp(): void
    {
        parent::setUp();

        Config::set('services.bold_checkout.api_url', 'https://example.com');
        Config::set('services.bold_checkout.checkout_url', 'https://example.com');
        Config::set('services.bold_checkout.api_path', 'base/path');

        $this->platformIdentifier = 'example-shop';
        $this->clientDouble = M::mock(Client::class);
        $this->shopServiceDouble = M::mock(ShopService::class);
        $this->frontendShopModelDouble = M::mock(FrontendShop::class);
        $this->shopModelDouble = M::mock(Shop::class);

        $this->shopServiceDouble->allows()->getInstance()->andReturns($this->frontendShopModelDouble);
        $this->frontendShopModelDouble->allows()->getShop()->andReturns($this->shopModelDouble);
        $this->shopModelDouble->allows()->getPlatformIdentifier()->andReturns($this->platformIdentifier);

        $this->apiService = new ApiService(
            $this->clientDouble,
            $this->shopServiceDouble,
        );
    }

    protected function tearDown(): void
    {
        parent::tearDown();
        M::close();
    }

    public function testGetRequestOptionsSetsBearerToken()
    {
        $token = 'my-token';

        $options = $this->apiService->getRequestOptions($token, []);

        $this->assertEquals('Bearer my-token', $options['headers']['authorization']);
    }

    public function testGetRequestOptionsReturnsJsonEncodedBody()
    {
        $token = 'my-token';
        $body = ['some' => 'value'];

        $options = $this->apiService->getRequestOptions($token, $body);

        $this->assertEquals(json_encode($body), $options['body']);
    }

    public function testGetShopInfosUsesStagingAsOriginWhenUsingLocalEnvironment()
    {
        $this->givenEnvironmentIsLocal();

        $result = $this->apiService->getShopInfosUrl();

        $this->assertEquals('https://api.staging.boldcommerce.com/shops/v1/info', $result);
    }

    public function testGetShopInfosUsesConfiguredCheckoutUrlAsOriginWhenNotUsingLocalEnvironment()
    {
        $this->givenEnvironmentIsNotLocal();

        $result = $this->apiService->getShopInfosUrl();

        $this->assertEquals('https://example.com/shops/v1/info', $result);
    }

    public function testGetCustomerInfosUrlUsesStagingOriginIfEnvironmentIsLocal()
    {
        $this->givenEnvironmentIsLocal();

        $customerId = '0';
        $result = $this->apiService->getCustomerInfosUrl($customerId);

        $this->assertEquals(
            'https://api.staging.boldcommerce.com/' . 'customers/' . 'v2/shops/' . 'example-shop/' . 'customers/pid/' . '0',
            $result,
        );
    }

    public function testGetCustomerInfosUrlUsesConfiguredCheckoutApiUrlIfEnvironmentNotLocal()
    {
        $this->givenEnvironmentIsNotLocal();

        $customerId = '0';
        $result = $this->apiService->getCustomerInfosUrl($customerId);

        $this->assertEquals(
            'https://example.com/' . 'customers/' . 'v2/shops/' . 'example-shop/' . 'customers/pid/' . '0',
            $result,
        );
    }

    public function testGetStorefrontApiUrlReturnsExpectedUrlWhenPublicOrderIdNotProvided()
    {
        $result = $this->apiService->getStorefrontApiUrl('my/api/path');

        $this->assertEquals(
            'https://example.com/' . 'base/path/' . 'storefront/' . 'example-shop/' . 'my/api/path',
            $result,
        );
    }

    public function testGetStorefrontApiUrlReturnsExpectedUrlWhenPublicOrderIdProvided()
    {
        $publicOrderId = '123';
        $result = $this->apiService->getStorefrontApiUrl('my/api/path', $publicOrderId);

        $this->assertEquals(
            'https://example.com/' . 'base/path/' . 'storefront/' . 'example-shop/' . '123/' . 'my/api/path',
            $result,
        );
    }

    public function testGetOrdersApiUrlReturnsExpectedUrlWhenPublicOrderIdNotProvided()
    {
        $result = $this->apiService->getOrdersApiUrl('my/api/path');

        $this->assertEquals(
            'https://example.com/' . 'base/path/' . 'orders/' . 'example-shop/' . 'my/api/path',
            $result,
        );
    }

    public function testGetOrdersApiUrlReturnsExpectedUrlWhenPublicOrderIdProvided()
    {
        $publicOrderId = '123';
        $result = $this->apiService->getOrdersApiUrl('my/api/path', $publicOrderId);

        $this->assertEquals(
            'https://example.com/' . 'base/path/' . 'orders/' . 'example-shop/' . '123/' . 'my/api/path',
            $result,
        );
    }

    public function testGetCustomerUrlReturnsExpectedValue()
    {
        $publicOrderId = '123';
        $result = $this->apiService->getCustomerUrl($publicOrderId);

        $this->assertEquals(
            'https://example.com/' . 'base/path/' . 'orders/' . 'example-shop/' . '123/' . 'customer',
            $result,
        );
    }

    public function testGetAddAuthenticatedCustomerUrlReturnsExpectedValue()
    {
        $publicOrderId = '123';
        $result = $this->apiService->getAddAuthenticatedCustomerUrl($publicOrderId);

        $this->assertEquals(
            'https://example.com/' . 'base/path/' . 'orders/' . 'example-shop/' . '123/' . 'customer/authenticated',
            $result,
        );
    }

    public function testSendApiRequestMakesApiRequestWithProvidedMethodUrlAndOptions()
    {
        $method = Request::METHOD_GET;
        $url = 'https://www.example.com';
        $options = ['headers' => ['example' => 'header']];

        $this->clientDouble
            ->shouldReceive('request')
            ->with($method, $url, $options)
            ->once()
            ->andReturn(new Response());

        $this->apiService->sendApiRequest($method, $url, $options);
    }

    public function testSendApiRequestSetsAuthorizationHeaderIfEnvIsLocalAndShopIsSet()
    {
        $token = fake()->text();

        $this->givenEnvironmentIsLocal();
        $this->givenShopHasToken($token);

        $this->expectHttpClientToReceiveRequestWithHeader('authorization', 'Bearer ' . $token);

        $this->apiService->sendApiRequest(Request::METHOD_GET, 'https://www.example.com', [], true);
    }

    public function testSendApiRequestSetsXBoldProxyAuthkeyHeaderIfEnvIsLocalAndShopIsSet()
    {
        $xBoldProxyAuthKey = fake()->text();

        $this->givenEnvironmentIsLocal();
        $this->givenConfigurationHasXBoldProxyAuthKey($xBoldProxyAuthKey);
        $this->givenShopHasToken();

        $this->expectHttpClientToReceiveRequestWithHeader('X-Bold-Proxy-Auth-Key', $xBoldProxyAuthKey);

        $this->apiService->sendApiRequest(Request::METHOD_GET, 'https://www.example.com', [], true);
    }

    public function testSendApiRequestSetsXBoldProxyShopIdentifierHeaderIfEnvIsLocalAndShopIsSet()
    {
        $this->givenEnvironmentIsLocal();
        $this->givenShopHasToken();

        $this->expectHttpClientToReceiveRequestWithHeader('X-Bold-Proxy-Shop-Identifier', $this->platformIdentifier);

        $this->apiService->sendApiRequest(Request::METHOD_GET, 'https://www.example.com', [], true);
    }

    public function testSendApiRequestDecodesResponseBodiesAsJson()
    {
        $this->givenHttpClientWillReturnResponse(new Response(200, [], '{"some": "value"}'));

        $result = $this->apiService->sendApiRequest(Request::METHOD_GET, 'https://www.example.com', []);

        $this->assertEquals(['some' => 'value'], $result[Fields::CONTENT_IN_RESPONSE]);
    }

    public function testSendApiRequestForwardsResponseCodeToCaller()
    {
        $responseCode = 200;
        $this->givenHttpClientWillReturnResponse(new Response($responseCode));

        $result = $this->apiService->sendApiRequest(Request::METHOD_GET, 'https://www.example.com', []);

        $this->assertEquals($responseCode, $result[Fields::CODE_IN_RESPONSE]);
    }

    private function givenEnvironmentIsLocal()
    {
        App::shouldReceive('environment')->with(Constants::APP_ENV_LOCAL)->once()->andReturns(true);
    }

    private function givenEnvironmentIsNotLocal()
    {
        App::shouldReceive('environment')->with(Constants::APP_ENV_LOCAL)->once()->andReturns(false);
    }

    private function givenShopHasToken(string $token = null)
    {
        $this->frontendShopModelDouble->allows('getToken')->andReturn($token ?? fake()->text());
    }

    private function givenConfigurationHasXBoldProxyAuthKey($xBoldProxyAuthKey = null)
    {
        Config::set('services.bold_checkout.x_bold_proxy_auth_key', $xBoldProxyAuthKey ?? fake()->text());
    }

    private function givenHttpClientWillReturnResponse(Response $response)
    {
        $this->clientDouble
            ->shouldReceive('request')
            ->once()
            ->andReturn($response);
    }

    private function expectHttpClientToReceiveRequestWithHeader(string $headerName, string $headerValue)
    {
        $this->clientDouble->shouldReceive('request')
            ->withArgs(function ($method, $url, $options) use ($headerName, $headerValue) {
                return $options['headers'][$headerName] === $headerValue;
            })
            ->once()
            ->andReturn(new Response());
    }
}
