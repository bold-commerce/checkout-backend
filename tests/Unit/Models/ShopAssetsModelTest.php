<?php

namespace Tests\Unit\Models;

use App\Models\ShopAssets;
use Tests\TestCase;

class ShopAssetsModelTest extends TestCase
{
    public function testGetAssetID() {
        $fakeID = fake()->numberBetween(100, 200);
        $shopAssets = ShopAssets::factory()->make(['asset_id' => $fakeID]);
        $result = $shopAssets->getAssetID();
        $this->assertEquals($fakeID, $result);
    }

    public function testSetShopID() {
        $fakeID = fake()->numberBetween(100, 200);
        $shopAssets = new ShopAssets();
        $shopAssets->setShopID($fakeID);
        $this->assertEquals($fakeID, $shopAssets->shop_id);
    }

}