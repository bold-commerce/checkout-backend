<?php

namespace App\Services;

use App\Constants\API;
use App\Constants\Errors;
use App\Constants\Fields;
use App\Constants\Paths;
use App\Constants\SupportedPlatforms;
use App\Exceptions\InvalidPlatformException;
use App\Facades\Jwt;
use App\Models\Shop;
use Exception;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Throwable;

class ExperienceService
{
    protected ShopService $shopService;

    public function __construct(ShopService $shopService)
    {
        $this->shopService = $shopService;
    }

    public function getResumableOrderUrl(Shop $shop): string
    {
        return route(
            'experience.resume',
            ['platformType' => $shop->getPlatformType(),
                'shopDomain' => $shop->getPlatformDomain(),
                'requestPage' => Paths::RESUME_PATH,
            ]
        );
    }

    public function getInitializationOrderData(array $params): array
    {
        $shop = $this->shopService->getInstance();
        $resumableUrl = $this->getResumableOrderUrl($shop->getShop());
        $userAccessToken = $params['userAccessToken'] ?? '';
        $cartID = $params['cart_id'] ?? '';

        $body = [
            'flow_id' => $shop->getAssets()->get('template')->flow_id ?? 'Bold Flow',
            'resumable_link' => $resumableUrl
        ];

        if (!empty($cartID)) {
            $body['cart_id'] = $cartID;
        }
        if (!empty($userAccessToken)) {
            $body['access_token'] = $userAccessToken;
        }

        return $body;
    }

    public function getCheckoutUrl(): string
    {
        $checkoutApiUrl = config('services.bold_checkout.api_url');

        return str_replace('/api/v2', '', $checkoutApiUrl);
    }

    public function getAssetsUrl(): string
    {
        return config('services.bold_checkout.assets_url');
    }

    public function getFlags(): Collection
    {
        $flags = config('flags', '');

        return collect(explode(',', $flags));
    }

    /**
     * @param string $cartID
     * @param string $publicOrderId
     * @param array $cartParams
     * @return string
     * @throws InvalidPlatformException
     */
    public function getReturnToCheckoutUrl(
        string $cartID,
        string $publicOrderId,
        array $cartParams = []
    ): string {
        $shop = $this->shopService->getInstance();
        $platformType = $shop->getShop()->getPlatformType();
        $query['shop'] = $shop->getShop()->getPlatformDomain();
        $query['cart_id'] = $cartID;
        $query['return_url'] = $shop->getUrls()->getBackToCartUrl();
        $query['platform'] = $platformType;
        $query['public_order_id'] = $publicOrderId;
        if (!empty($cartParams)) {
            $query['cart_params'] = $cartParams;
        }
        $checkoutUrl = '';

        switch ($platformType) {
            case SupportedPlatforms::WOOCOMMERCE_PLATFORM_TYPE:
                // checkout link not needed
                break;
            case SupportedPlatforms::COMMERCETOOLS_PLATFORM_TYPE:
            case SupportedPlatforms::BOLD_PLATFORM_TYPE:
            case SupportedPlatforms::BIGCOMMERCE_PLATFORM_TYPE:
                $appUrl = $shop->getUrls()->getBacktoStoreUrl();
                $checkoutUrl = $appUrl.'/boldplatform/proxy/begin-checkout?'.http_build_query($query);
                break;
            case SupportedPlatforms::SHOPIFY_PLATFORM_TYPE:
                $checkoutUrl = '/apps/checkout/begin-checkout?'.http_build_query($query);
                break;
            default:
                throw new InvalidPlatformException(sprintf(Errors::INVALID_PLATFORM, $platformType));
        }

        return $checkoutUrl;
    }

    public function convertVariantListToCartItems(string $variant): array
    {
        if (!empty($variant)) {
            $variantList = explode(',', $variant);

            return array_map(function ($variant, $key) {
                return [
                    'platform_id' => explode(':', $variant)[0],
                    'quantity' => intval(explode(':', $variant)[1]),
                    'line_item_key' => 'item'.$key,
                ];
            }, $variantList, array_keys($variantList));
        } else {
            return [];
        }
    }

    public function isShippingRequired(array $lineItems): bool
    {
        $requiresShipping = false;
        foreach ($lineItems as $lineItem) {
            if (!empty($lineItem['product_data']['requires_shipping'])) {
                $requiresShipping = true;
                break;
            }
        }

        return $requiresShipping;
    }

    public function isCheckoutExperiencePage($requestPage): bool
    {
        return in_array($requestPage, [
            Paths::RESUME_PATH,
            Paths::SHIPPING_PATH,
            Paths::PAYMENT_PATH,
            Paths::THANK_YOU_PATH,
            Paths::OUT_OF_STOCK_PATH,
            Paths::SESSION_EXPIRED_PATH,
        ]);
    }

    public function cleanOrder(string $publicOrderID, array $response): void
    {
        $endpointService = app()->make(EndpointService::class);

        $data = $response[Fields::CONTENT_IN_RESPONSE][Fields::DATA_IN_RESPONSE];
        $jwtToken = $data[Fields::JWT_TOKEN_IN_RESPONSE];
        $applicationState = $data[Fields::APPLICATION_STATE_IN_RESPONSE];
        $customer = $applicationState[Fields::CUSTOMER_IN_RESPONSE];
        if (!empty($customer[Fields::EMAIL_ADDRESS_IN_RESPONSE])) {
            $endpointService->deleteCustomer($publicOrderID);
        }

        $addresses = $applicationState[Fields::ADDRESSES_IN_RESPONSE];
        if (!empty($addresses[Fields::SHIPPING_IN_RESPONSE])) {
            $endpointService->deleteAddress($publicOrderID, $jwtToken, Fields::SHIPPING_IN_RESPONSE);
        }

        if (!empty($addresses[Fields::BILLING_IN_RESPONSE])) {
            $endpointService->deleteAddress($publicOrderID, $jwtToken, Fields::BILLING_IN_RESPONSE);
        }
    }

    public function shouldClearOrder(string $paramPublicOrderId, array $requestData): bool
    {
        $skipOrderCleaning = true;
        try {
            if (isset($requestData['token'])) {
                $token = $requestData['token'];
                $decodedPayload = Jwt::decodeToken($token);
                $payload = $decodedPayload->toArray()['payload'];
                $payloadPublicOrder = $payload['public_order_id'];
                if ($payload['public_order_id'] === $paramPublicOrderId) {
                    $cachedToken = Cache::pull('headless::'.$payloadPublicOrder);
                    if ($cachedToken !== 'pending') {
                        abort(401);
                    }
                    $skipOrderCleaning = false;
                }
            }
        } catch (Throwable $e) {
            abort(401);
        }

        return $skipOrderCleaning;
    }
}
