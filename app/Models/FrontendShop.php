<?php

declare(strict_types=1);

namespace App\Models;

use App\Constants\Errors;
use App\Exceptions\ResourceMissingException;
use App\Exceptions\ShopNotFoundException;
use App\Exceptions\ShopTokenNotFoundException;
use App\Exceptions\ShopUrlsMissingException;
use App\Services\ShopApiTokenService;
use App\Services\ShopAssetsService;
use App\Services\ShopService;
use App\Services\ShopUrlService;
use Database\Factories\FrontendShopFactory;
use Illuminate\Contracts\Container\BindingResolutionException;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Support\Collection;

class FrontendShop
{
    use hasFactory;

    protected Shop $shop;
    protected ShopApiToken $shopApiToken;

    protected Collection $shopAssets;
    protected ShopUrl $shopUrls;

    protected string $returnToCheckoutAfterLoginUrl;

    protected ShopService $shopService;
    protected ShopApiTokenService $shopApiTokenService;
    protected ShopAssetsService $shopAssetsService;
    protected ShopUrlService $shopUrlService;

    /**
     * @throws BindingResolutionException
     * @throws ShopNotFoundException
     */
    public function __construct()
    {
        $this->shopService = app()->make(ShopService::class);
        $this->shopApiTokenService = app()->make(ShopApiTokenService::class);
        $this->shopAssetsService = app()->make(ShopAssetsService::class);
        $this->shopUrlService = app()->make(ShopUrlService::class);
        $this->returnToCheckoutAfterLoginUrl = '';
    }

    public function populate(Shop|int|string $shop): FrontendShop
    {
        try {
            if (is_int($shop)) {
                $this->shop = $this->shopService->getShop($shop);
            } elseif (is_string($shop)) {
                $this->shop = $this->shopService->getShopByIdentifierOrDomain($shop);
            } else {
                $this->shop = $shop;
            }

            $this->shopUrls = $this->shopUrlService->getUrlsByShopID($this->shop->getID());
            $this->shopApiToken = $this->shopApiTokenService->getApiTokenByShopID($this->shop->getID());
            $this->shopAssets = $this->shopAssetsService->getAssetsByShopID($this->shop->getID());
        } catch (ShopNotFoundException|ShopUrlsMissingException|ShopTokenNotFoundException|ResourceMissingException $e) {
            throw new ShopNotFoundException(Errors::CONSTRUCTION_ERROR);
        }
        return $this;
    }

    public function getToken($asString = true): string|ShopApiToken
    {
        if ($asString) {
            return $this->shopApiToken->getToken();
        } else {
            return $this->shopApiToken;
        }
    }

    public function getAssets(): Collection
    {
        return $this->shopAssets;
    }

    public function getUrls(): ShopUrl
    {
        return $this->shopUrls;
    }

    public function getShop(): Shop
    {
        return $this->shop;
    }

    public function getID(): int
    {
        return $this->getShop()->getID();
    }

    public function toArray(): array
    {
        return [
            'shop' => $this->shop->toArray(),
            'shopAssets' => $this->shopAssets->toArray(),
            'shopApiToken' => $this->shopApiToken->toArray(),
            'shopUrl' => $this->shopUrls->toArray(),
        ];
    }

    public function getReturnToCheckoutAfterLogin(): string
    {
        return $this->returnToCheckoutAfterLoginUrl;
    }

    public function setReturnToCheckoutAfterLogin(string $url): void
    {
        if (!empty($url)) {
            $this->returnToCheckoutAfterLoginUrl = $url;
        }
    }

    public function getStylesheetUrl(): string
    {
        $url = sprintf(
            '%s/shop/%s/%s/styles.css',
            config('services.bold_checkout.checkout_url'),
            $this->getShop()->getPlatformType(),
            $this->getShop()->getPlatformDomain()
        );

        try {
            $contentCSS = file_get_contents($url);
            return empty($contentCSS) ? '' : $url;
        } catch (\Exception $e) {
            return '';
        }

    }

    public function setShop(Shop $shop): void
    {
        $this->shop = $shop;
    }

    public function setShopApiToken(ShopApiToken $shopApiToken): void
    {
        $this->shopApiToken = $shopApiToken;
    }

    public function setShopUrls(ShopUrl $shopUrls): void
    {
        $this->shopUrls = $shopUrls;
    }
}
