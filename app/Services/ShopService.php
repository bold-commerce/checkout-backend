<?php

declare(strict_types=1);

namespace App\Services;

use App\Constants\Errors;
use App\Constants\Fields;
use App\Constants\SupportedPlatforms;
use App\Exceptions\InvalidPlatformException;
use App\Exceptions\ShopInstanceException;
use App\Exceptions\ShopNotFoundException;
use App\Models\FrontendShop;
use App\Models\Shop;
use Exception;
use Illuminate\Support\Facades\App;

class ShopService
{
    public function getInstance(): FrontendShop
    {
        return App::make(FrontendShop::class);
    }

    public function getShop(int $shopID): Shop
    {
        $shop = Shop::find($shopID);
        if (empty($shop)) {
            throw new ShopNotFoundException(sprintf(Errors::INVALID_ID, Fields::SHOP, $shopID));
        }

        return $shop;
    }

    public function getShopByDomain(string $shopDomain): ?Shop
    {
        $shop = Shop::where('platform_domain', '=', $shopDomain)->get();
        if ($shop->count() !== 1) {
            throw new ShopNotFoundException(sprintf(Errors::INVALID_DOMAIN, $shopDomain));
        }

        return $shop->first();
    }

    public function getShopByIdentifier(string $identifier): ?Shop
    {
        $shop = Shop::where('platform_identifier', '=', $identifier)->get();
        if ($shop->count() !== 1) {
            throw new ShopNotFoundException(sprintf(Errors::INVALID_IDENTIFIER, $identifier));
        }

        return $shop->first();
    }

    public function getShopByIdentifierOrDomain(string $identifier): ?Shop
    {
        $shop = Shop::where('platform_identifier', '=', $identifier)->orWhere('platform_domain', '=', $identifier)->get();
        if ($shop->count() !== 1) {
            throw new ShopNotFoundException(sprintf(Errors::INVALID_DOMAIN_IDENTIFIER, $identifier));
        }

        return $shop->first();
    }

    /**
     * @throws ShopNotFoundException|ShopInstanceException
     */
    public static function createShopFromArray(?array $shopParameters, array $shopInfos): Shop
    {
        if (empty($shopParameters)) {
            throw new ShopNotFoundException(Errors::EMPTY_PARAMETERS_LIST);
        }
        if (empty($shopInfos)) {
            throw new ShopInstanceException(Errors::EMPTY_SHOP_INFOS);
        }

        if (Shop::parametersListIsComplete($shopParameters) && Shop::compareInfos($shopParameters, $shopInfos)) {
            try {
                $findParameters = [
                    'platform_domain' => $shopParameters['platform_domain'],
                    'platform_type' => $shopParameters['platform_type'],
                    'platform_identifier' => $shopParameters['platform_identifier'],
                ];

                return Shop::updateOrCreate($findParameters, $shopParameters);
            } catch (Exception $e) {
                throw new ShopInstanceException(Errors::INVALID_PARAMETERS);
            }
        }

        throw new ShopNotFoundException(Errors::MANDATORY_PARAMETERS_REQUIREMENT_NOT_MET);
    }

    /**
     * @throws InvalidPlatformException
     */
    public function getReturnToCartUrl(string $shopDomain, $platformType): string
    {
        switch ($platformType) {
            case SupportedPlatforms::BIGCOMMERCE_PLATFORM_TYPE:
                $cartUrl = "https://$shopDomain/cart.php";
                break;
            case SupportedPlatforms::WOOCOMMERCE_PLATFORM_TYPE:
            case SupportedPlatforms::SHOPIFY_PLATFORM_TYPE:
            case SupportedPlatforms::COMMERCETOOLS_PLATFORM_TYPE:
            case SupportedPlatforms::BOLD_PLATFORM_TYPE:
                $cartUrl = "https://$shopDomain/cart";
                break;
            default:
                throw new InvalidPlatformException('Platform $platformType was not found or not support');
        }

        return $cartUrl;
    }
}
