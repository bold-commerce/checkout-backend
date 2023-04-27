<?php

declare(strict_types=1);

namespace App\Models;

class ShopApiToken extends AbstractExperienceModel
{
    protected $table = 'shop_api_token';
    protected $fillable = [
        'api_token',
    ];
    protected $primaryKey = 'shop_id';
    public $timestamps = false;

    public function getToken(): string
    {
        return $this->api_token;
    }

    public function setToken(string $token): void
    {
        $this->api_token = $token;
    }

    public function setShopID(int $shopID): void
    {
        $this->shop_id = $shopID;
    }
}
