<?php

namespace App\Services;

use App\Constants\API;
use App\Constants\Constants;
use App\Constants\Errors;
use App\Constants\Fields;
use App\Constants\Paths;
use App\Exceptions\ApiCallExceptions\SendApiRequestException;
use Exception;
use GuzzleHttp\Client;
use GuzzleHttp\ClientInterface;
use GuzzleHttp\Exception\GuzzleException;
use Illuminate\Support\Facades\App;

class ApiService
{
    private ClientInterface $client;
    protected ShopService $shopService;

    public function __construct(Client $client, ShopService $shopService)
    {
        $this->client = $client;
        $this->shopService = $shopService;
    }

    public function getRequestOptions(string $token, array $bodyArray = []): array
    {
        $headers[API::HEADER_AUTHORIZATION] = API::HEADER_AUTHORIZATION_VALUE_BEARER . $token;
        $headers[API::HEADER_CONTENT_TYPE] = API::HEADER_CONTENT_TYPE_VALUE_JSON;
        $body = json_encode($bodyArray);

        return [
            'headers' => $headers,
            'body' => $body,
        ];
    }

    public function getShopInfosUrl(): string
    {
        if (App::environment(Constants::APP_ENV_LOCAL)) {
            return API::API_URL_STAGING . '/' . Paths::SHOP_INFOS_PATH;
        }

        return config('services.bold_checkout.checkout_url') . '/' . Paths::SHOP_INFOS_PATH;
    }

    public function getCustomerInfosUrl(string $customerID): string
    {
        $shop = $this->shopService->getInstance();

        if (App::environment(Constants::APP_ENV_LOCAL)) {
            return sprintf(
                API::API_URL_STAGING . '/' . Paths::CUSTOMER_INFOS_PATH,
                $shop->getShop()->getPlatformIdentifier(),
                $customerID
            );
        }

        return sprintf(
            config('services.bold_checkout.api_url') . '/' . Paths::CUSTOMER_INFOS_PATH,
            $shop->getShop()->getPlatformIdentifier(),
            $customerID
        );
    }

    public function getCustomerUrl(string $publicOrderID): string
    {
        return $this->getOrdersApiUrl(Paths::CUSTOMER_PATH, $publicOrderID);
    }

    public function getAddAuthenticatedCustomerUrl(string $publicOrderID): string
    {
        return $this->getOrdersApiUrl(Paths::ADD_AUTHENTICATED_USER_PATH, $publicOrderID);
    }

    public function getStorefrontApiUrl(string $apiPath, string $publicOrderId = null): string
    {
        $shop = $this->shopService->getInstance();
        $baseUrl = config('services.bold_checkout.api_url') . '/' . config('services.bold_checkout.api_path') . '/' . Paths::CHECKOUT_STOREFRONT_PATH . '/' . $shop->getShop()->getPlatformIdentifier();
        if ($publicOrderId !== null) {
            $baseUrl = $baseUrl . '/' . $publicOrderId;
        }

        return $baseUrl . '/' . $apiPath;
    }

    public function getOrdersApiUrl(
        string $apiPath,
        string $publicOrderID = null
    ): string {
        $shop = $this->shopService->getInstance();
        $baseURL = config('services.bold_checkout.api_url') . '/' . config('services.bold_checkout.api_path') . '/' . Paths::ORDERS_PATH . '/' . $shop->getShop()->getPlatformIdentifier();
        if (!empty($publicOrderID)) {
            $baseURL .= '/' . $publicOrderID;
        }

        return $baseURL . '/' . $apiPath;
    }

    /**
     * @throws GuzzleException
     * @throws Exception
     */
    public function sendApiRequest(string $method, string $url, array $options, $isLocal = false): array
    {
        $shop = $this->shopService->getInstance();
        if (App::environment(Constants::APP_ENV_LOCAL) && $isLocal && !empty($shop)) {
            $options['headers']['authorization'] = sprintf('Bearer %s', $shop->getToken());
            $options['headers']['X-Bold-Proxy-Auth-Key'] = config('services.bold_checkout.x_bold_proxy_auth_key');
            $options['headers']['X-Bold-Proxy-Shop-Identifier'] = $shop->getShop()->getPlatformIdentifier();
            $options['headers']['X-Authenticated-Scope'] = implode(' ', API::API_V2_SCOPES);
        }
        $response = $this->client->request($method, $url, $options);
        $responseContent = json_decode($response->getBody()->getContents(), true);
        $responseCode = $response->getStatusCode();

        return [
            Fields::CODE_IN_RESPONSE => $responseCode,
            Fields::CONTENT_IN_RESPONSE => $responseContent,
        ];
    }
}
