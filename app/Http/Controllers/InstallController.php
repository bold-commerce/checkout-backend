<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Constants\API;
use App\Constants\Constants;
use App\Constants\Errors;
use App\Constants\Messages;
use App\Exceptions\AbstractCheckoutException;
use App\Exceptions\ApiCallExceptions\SendApiRequestException;
use App\Exceptions\ApiCallExceptions\ShopInfosApiCallException;
use App\Exceptions\InvalidAssetException;
use App\Exceptions\InvalidTokenException;
use App\Exceptions\ParameterEmptyException;
use App\Exceptions\InvalidEnvironmentException;
use App\Exceptions\ShopInstanceException;
use App\Exceptions\ShopNotFoundException;
use App\Exceptions\ShopUrlsMissingException;
use App\Facades\Logging;
use App\Services\ApiService;
use App\Services\EndpointService;
use App\Services\ShopApiTokenService;
use App\Services\ShopAssetsService;
use App\Services\ShopService;
use App\Services\ShopUrlService;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Symfony\Component\HttpFoundation\Response;

class InstallController extends Controller
{
    protected ApiService $apiService;
    protected EndpointService $endpointService;
    protected ShopService $shopService;

    public function __construct(EndpointService $endpointService, ApiService $apiService, ShopService $shopService)
    {
        $this->apiService = $apiService;
        $this->endpointService = $endpointService;
        $this->shopService = $shopService;
    }

    public function getApiEnvironment(): array
    {
        $clientId = env('DEVELOPER_CLIENT_ID');
        $clientSecret = env('DEVELOPER_CLIENT_SECRET');
        $redirectUrl = env('DEVELOPER_REDIRECT_URL');
        $appUrl = env('APP_URL');
        $apiDashUrl = '';
        $oAuthUrl = '';
        if (env('APP_ENV', '') !== Constants::APP_ENV_PRODUCTION) {
            $apiDashUrl = API::API_V2_AUTH_DASH_URL_STAGING;
            $oAuthUrl = API::API_URL_STAGING . '/auth/oauth2/token';
        } else {
            $apiDashUrl = API::API_V2_AUTH_DASH_URL_PRODUCTION;
            $oAuthUrl = API::API_URL_PRODUCTION . '/auth/oauth2/token';
        }

        $response = [
            'APP_URL' => $appUrl,
            'DEVELOPER_CLIENT_ID' =>  $clientId,
            'DEVELOPER_CLIENT_SECRET' => $clientSecret,
            'DEVELOPER_REDIRECT_URL' => $redirectUrl,
            'API_V2_AUTH_DASH_URL' => $apiDashUrl,
            'API_V2_OAUTH_TOKEN_URL' => $oAuthUrl,
            'API_V2_SCOPES' => implode(',', API::API_V2_SCOPES)
        ];

        foreach ($response as $key => $val) {
            if (empty($val)) {
                throw new InvalidEnvironmentException(Errors::EMPTY_ENVIRONMENT_PROPERTIES, Response::HTTP_INTERNAL_SERVER_ERROR, null, [$key]);
            }
        }

        return $response;
    }


    public function init()
    {
        try {
            $apiEnv = $this->getApiEnvironment();

            $urlRedirect = sprintf(
                '%s?client_id=%s&scope=%s&redirect_uri=%s',
                $apiEnv['API_V2_AUTH_DASH_URL'],
                $apiEnv['DEVELOPER_CLIENT_ID'],
                $apiEnv['API_V2_SCOPES'],
                $apiEnv['DEVELOPER_REDIRECT_URL']
            );

            return redirect($urlRedirect);
        } catch (InvalidEnvironmentException $e) {
            $status = Response::HTTP_INTERNAL_SERVER_ERROR;
            return response()->json([
                'message' =>  $e->getMessage(),
            ], $status);
        }
    }

    public function install(Request $request): JsonResponse
    {
        $message = Messages::SHOP_INSTALL_SUCCESSFUL;
        $results = [];
        $status = Response::HTTP_CREATED;
        try {
            $apiEnv = $this->getApiEnvironment();

            $code = $request->query('code');

            $options['form_params'] = [
                'client_id' =>  $apiEnv['DEVELOPER_CLIENT_ID'],
                'client_secret' => $apiEnv['DEVELOPER_CLIENT_SECRET'],
                'code' => $code,
                'grant_type' => 'authorization_code',
            ];

            $authResults = $this->apiService->sendApiRequest(Request::METHOD_POST, $apiEnv['API_V2_OAUTH_TOKEN_URL'], $options);

            $shopData = $this->endpointService->shopInfos($authResults['content']['access_token']);
            if ($shopData['code'] !== Response::HTTP_OK) {
                throw new ShopInfosApiCallException(Errors::SHOP_INFOS_API, Response::HTTP_INTERNAL_SERVER_ERROR);
            }

            $returnToCartLink = $this->shopService->getReturnToCartUrl($shopData['content']['custom_domain'], $shopData['content']['platform_slug']);

            $shopInfos = [
                'shop' => [
                    'platform_domain' => $shopData['content']['shop_domain'],
                    'custom_domain' => $shopData['content']['custom_domain'],
                    'platform_type' => $shopData['content']['platform_slug'],
                    'platform_identifier' => $shopData['content']['shop_identifier'],
                    'shop_name' => $shopData['content']['store_name'],
                    'support_email' => $shopData['content']['admin_email'],
                ],
                'token' => $authResults['content']['access_token'],
                'urls' => [
                    'back_to_cart_url' => $returnToCartLink,
                    'back_to_store_url' => $shopData['content']['custom_domain'],
                    'login_url' => $shopData['content']['custom_domain'] . '/login.php',
                    'logo_url' => 'https://static.boldcommerce.com/images/logo/bold_logo_red.svg',
                    'favicon_url' => 'https://static.boldcommerce.com/images/logo/bold.ico'
                ],
                'asset' => 2,
            ];
            $results['shop'] = ShopService::createShopFromArray($shopInfos['shop'], $shopData['content']);


            ShopApiTokenService::insertToken($results['shop'], $authResults['content']['access_token']);
            $results['token'] = '[hidden]';

            ShopUrlService::insertUrls($results['shop'], $shopInfos['urls']);

            $results['urls'] = $shopInfos['urls'];
            $asset = ShopAssetsService::insertAsset($results['shop'], $shopInfos['asset']);
            $results['asset'] = $asset->toArray();

            return response()->json([
                'message' => $message,
                'results' => $results,
            ], $status);
        } catch (ShopNotFoundException | ShopInstanceException | ShopUrlsMissingException | ParameterEmptyException | InvalidTokenException | InvalidAssetException | InvalidEnvironmentException | ShopInfosApiCallException | SendApiRequestException $e) {
            $message = Messages::SHOP_INSTALL_FAILED;
            $status = Response::HTTP_BAD_REQUEST;

            Logging::exception('Error installing Shop', ['errors' => $e->getMessage()]);
            $results = ['successful_steps' => array_keys($results)];
            return response()->json([
                'message' => $message,
                'results' => $results,
            ], $status);
        }
    }
}
