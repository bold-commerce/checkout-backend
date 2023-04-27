<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Support\Collection;

class ShopUrl extends AbstractExperienceModel
{
    protected $table = 'shop_url';
    protected $fillable = [
        'back_to_cart_url',
        'back_to_store_url',
        'login_url',
        'logo_url',
        'favicon_url',
    ];
    protected $primaryKey = 'shop_id';
    protected array $empty = ['logo_url', 'favicon_url'];
    public $timestamps = false;

    public function getUrls(): Collection
    {
        $urls = [
            'backToCart' => $this->getBackToCartUrl(),
            'backToStore' => $this->getBackToStoreUrl(),
            'login' => $this->getLoginUrl(),
            'logo' => $this->getLogoUrl(),
            'favicon' => $this->getFaviconUrl(),
        ];

        return collect($urls);
    }

    public function getBackToCartUrl(): string
    {
        return $this->back_to_cart_url;
    }

    public function getBackToStoreUrl(): string
    {
        return $this->back_to_store_url;
    }

    public function getLoginUrl(): string
    {
        return $this->login_url;
    }

    public function getLogoUrl(): string
    {
        return $this->logo_url;
    }

    public function getFaviconUrl(): string
    {
        return $this->favicon_url;
    }

    public function setShopID(int $shopID): void
    {
        $this->shop_id = $shopID;
    }
}
