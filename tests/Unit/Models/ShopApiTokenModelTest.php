<?php

namespace Tests\Unit\Models;

use App\Models\ShopApiToken;
use Tests\TestCase;

class ShopApiTokenModelTest extends TestCase
{
    public function testGetToken() {
        $fakeToken = fake()->text(64);
        $shopApiToken = ShopApiToken::factory()->make(['api_token' => $fakeToken]);
        $result = $shopApiToken->getToken();
        $this->assertEquals($fakeToken, $result);
    }

    public function testSetToken() {
        $fakeToken = fake()->text(64);
        $shopApiToken = new ShopApiToken();
        $shopApiToken->setToken($fakeToken);
        $this->assertEquals($fakeToken, $shopApiToken->getToken());
    }

    public function testSetShopID() {
        $fakeID = fake()->numberBetween(100, 200);
        $shopApiToken = new ShopApiToken();
        $shopApiToken->setShopID($fakeID);
        $this->assertEquals($fakeID, $shopApiToken->shop_id);
    }

}