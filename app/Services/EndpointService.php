<?php

declare(strict_types=1);

namespace App\Services;

use App\Constants\Errors;
use App\Constants\Fields;
use App\Constants\Paths;
use App\Exceptions\ApiCallExceptions\AddAuthenticatedCustomerApiCallException;
use App\Exceptions\ApiCallExceptions\CustomerInfosApiCallException;
use App\Exceptions\ApiCallExceptions\DeleteAddressApiCallException;
use App\Exceptions\ApiCallExceptions\DeleteAuthenticatedCustomerApiCallException;
use App\Exceptions\ApiCallExceptions\InitializeOrderApiCallException;
use App\Exceptions\ApiCallExceptions\InitializeShopifyOrderFromAdminApiCallException;
use App\Exceptions\ApiCallExceptions\ResumeOrderApiCallException;
use App\Exceptions\ApiCallExceptions\ShopInfosApiCallException;
use GuzzleHttp\Exception\GuzzleException;
use Illuminate\Support\Arr;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class EndpointService
{
    protected ApiService $apiService;
    protected ExperienceService $experienceService;
    protected ShopService $shopService;

    public function __construct(ApiService $apiService, ExperienceService $experienceService, ShopService $shopService)
    {
        $this->apiService = $apiService;
        $this->experienceService = $experienceService;
        $this->shopService = $shopService;
    }

    /**
     * @throws GuzzleException|InitializeOrderApiCallException
     */
    public function initializeOrder(
        array $body
    ): array {
        $shop = $this->shopService->getInstance();
        $options = $this->apiService->getRequestOptions($shop->getToken(), $body);
        $url = $this->apiService->getOrdersApiUrl(Paths::INIT_PATH);

        $response = $this->apiService->sendApiRequest(Request::METHOD_POST, $url, $options, true);
        if ($response[Fields::CODE_IN_RESPONSE] !== Response::HTTP_OK) {
            throw new InitializeOrderApiCallException(Errors::INITIALIZE_ORDER_API,  $response[Fields::CODE_IN_RESPONSE], null , $this->getErrorsFromResponse($response),);
        }

        return $response;
    }

    /**
     * @throws ResumeOrderApiCallException|GuzzleException
     */
    public function resumeOrder(
        string $publicOrderID,
    ): array {
        $shop = $this->shopService->getInstance();
        $body = [
            'public_order_id' => $publicOrderID,
        ];
        $options = $this->apiService->getRequestOptions($shop->getToken(), $body);
        $url = $this->apiService->getOrdersApiUrl(Paths::RESUME_PATH);

        $response = $this->apiService->sendApiRequest(Request::METHOD_POST, $url, $options, true);
        if ($response[Fields::CODE_IN_RESPONSE] !== Response::HTTP_OK) {
            throw new ResumeOrderApiCallException(Errors::RESUME_ORDER_API, $response[Fields::CODE_IN_RESPONSE], null , $this->getErrorsFromResponse($response));
        }

        return $response;
    }

    /**
     * @throws GuzzleException|InitializeShopifyOrderFromAdminApiCallException
     */
    public function initializeShopifyAdminOrder(string $variant, string $resumableLink): array
    {
        $shop = $this->shopService->getInstance();
        $body = [];
        $body['cart_items'] = $this->experienceService->convertVariantListToCartItems($variant);
        if (!empty($resumableLink)) {
            $body['resumable_link'] = $resumableLink;
        }

        $options = $this->apiService->getRequestOptions($shop->getToken(), $body);
        $url = $this->apiService->getOrdersApiUrl(Paths::INIT_PATH);

        $response = $this->apiService->sendApiRequest(Request::METHOD_POST, $url, $options, true);
        if ($response[Fields::CODE_IN_RESPONSE] !== Response::HTTP_OK) {
            throw new InitializeShopifyOrderFromAdminApiCallException(Errors::INITIALIZE_SHOPIFY_ADMIN_ORDER_API, $response[Fields::CODE_IN_RESPONSE], null, $this->getErrorsFromResponse($response));
        }

        return $response;
    }

    /**
     * @throws ShopInfosApiCallException|GuzzleException
     */
    public function shopInfos(string $token): array
    {
        $options = $this->apiService->getRequestOptions($token, []);
        $url = $this->apiService->getShopInfosUrl();
        $response = $this->apiService->sendApiRequest(Request::METHOD_GET, $url, $options);
        if ($response[Fields::CODE_IN_RESPONSE] !== Response::HTTP_OK) {
            throw new ShopInfosApiCallException(Errors::SHOP_INFOS_API, $response[Fields::CODE_IN_RESPONSE], null, $this->getErrorsFromResponse($response));
        }

        return $response;
    }

    /**
     * @throws GuzzleException|CustomerInfosApiCallException
     */
    public function retrieveAuthenticatedCustomerInfos(string $customerIdentifier): array
    {
        $shop = $this->shopService->getInstance();
        $options = $this->apiService->getRequestOptions($shop->getToken(), []);
        $url = $this->apiService->getCustomerInfosUrl($customerIdentifier);
        $response = $this->apiService->sendApiRequest(Request::METHOD_GET, $url, $options);
        if ($response[Fields::CODE_IN_RESPONSE] !== Response::HTTP_OK) {
            throw new CustomerInfosApiCallException(Errors::CUSTOMER_INFOS_API, $response[Fields::CODE_IN_RESPONSE], null, $this->getErrorsFromResponse($response));
        }

        return $response;
    }

    /**
     * @throws GuzzleException|AddAuthenticatedCustomerApiCallException
     */
    public function addAuthenticatedCustomer(string $publicOrderId, array $customer): array
    {
        $shop = $this->shopService->getInstance();
        $options = $this->apiService->getRequestOptions($shop->getToken(), $customer);
        $url = $this->apiService->getAddAuthenticatedCustomerUrl($publicOrderId);
        $response = $this->apiService->sendApiRequest(Request::METHOD_POST, $url, $options, true);
        if ($response[Fields::CODE_IN_RESPONSE] !== Response::HTTP_OK) {
            throw new AddAuthenticatedCustomerApiCallException(Errors::ADD_AUTHENTICATED_CUSTOMER_API, $response[Fields::CODE_IN_RESPONSE], null, $this->getErrorsFromResponse($response));
        }

        return $response;
    }

    /**
     * @throws GuzzleException|DeleteAuthenticatedCustomerApiCallException
     */
    public function deleteCustomer(string $publicOrderID): array
    {
        $shop = $this->shopService->getInstance();
        $options = $this->apiService->getRequestOptions($shop->getToken());
        $url = $this->apiService->getCustomerUrl($publicOrderID);

        $response = $this->apiService->sendApiRequest(Request::METHOD_DELETE, $url, $options, true);
        if ($response[Fields::CODE_IN_RESPONSE] !== Response::HTTP_OK) {
            throw new DeleteAuthenticatedCustomerApiCallException(Errors::DELETE_AUTHENTICATED_CUSTOMER_API, $response[Fields::CODE_IN_RESPONSE], null, $this->getErrorsFromResponse($response));
        }

        return $response;
    }

    /**
     * @throws GuzzleException|DeleteAddressApiCallException
     */
    public function deleteAddress(string $publicOrderID, string $jwtToken, string $fieldToDelete): array
    {
        $options = $this->apiService->getRequestOptions($jwtToken);
        $path = '';
        switch ($fieldToDelete) {
            case Fields::BILLING_IN_RESPONSE:
                $path = Paths::DELETE_BILLING_ADDRESS_PATH;
                break;
            case Fields::SHIPPING_IN_RESPONSE:
            default:
                $path = Paths::DELETE_SHIPPING_ADDRESS_PATH;
                break;
        }
        $url = $this->apiService->getStorefrontApiUrl($path, $publicOrderID);
        $response = $this->apiService->sendApiRequest(Request::METHOD_DELETE, $url, $options, true);

        if ($response[Fields::CODE_IN_RESPONSE] !== Response::HTTP_OK) {
            $message = sprintf('Delete %s Address', $fieldToDelete);
            throw new DeleteAddressApiCallException($message, $response[Fields::CODE_IN_RESPONSE], null, $this->getErrorsFromResponse($response));
        }

        return $response;
    }

    protected function getErrorsFromResponse(array $response): string
    {
        $errorMessagePaths = [
            'content.errors',
            'content.message',
            'content.error',
        ];
        $defaultError = 'Possible network error.';
        foreach ($errorMessagePaths as $path) {
            $error = Arr::get($response, $path, null);
            if (!is_null($error)) {
                break;
            }
        }
        if (is_null($error)) {
            $error = $defaultError;
        }

        return $error;
    }
}
