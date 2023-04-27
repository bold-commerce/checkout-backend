<?php

declare(strict_types=1);

namespace App\Services;

use App\Constants\Errors;
use App\Exceptions\ParameterEmptyException;
use App\Exceptions\ShopUrlsMissingException;
use App\Models\Shop;
use App\Models\ShopUrl;

class ShopUrlService
{
    /**
     * @throws ShopUrlsMissingException
     */
    public function getUrlsByShopID(int $shopID): ShopUrl
    {
        $urls = ShopUrl::where('shop_id', '=', $shopID)->first();
        if (empty($urls)) {
            throw new ShopUrlsMissingException(sprintf(Errors::UNDEFINED_URLS, $shopID));
        }

        return $urls;
    }

    /**
     * @throws ShopUrlsMissingException
     * @throws ParameterEmptyException
     */
    public static function insertUrls(Shop $shop, array $shopUrls): ShopUrl
    {
        $urls = ShopUrl::find($shop->getID());
        if (empty($urls)) {
            $urls = new ShopUrl();
            $urls->setShopID($shop->getID());
        }
        if (ShopUrl::parametersListIsComplete($shopUrls)) {
            $urls->populateFromArray($shopUrls);
            $urls->save();

            return $urls;
        } else {
            throw new ShopUrlsMissingException(Errors::MANDATORY_PARAMETERS_REQUIREMENT_NOT_MET);
        }
    }
}
