<?php

namespace App\Models;

class ShopAssets extends AbstractExperienceModel
{
    protected $table = 'shop_assets';
    protected $fillable = [
        'asset_id',
    ];

    protected $primaryKey = 'shop_id';
    public $incrementing = false;
    public $timestamps = false;

    public function getAssetID(): int
    {
        return $this->asset_id;
    }

    public function setShopID(int $shopID): void
    {
        $this->shop_id = $shopID;
    }
}
