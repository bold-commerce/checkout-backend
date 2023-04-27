<?php

namespace Tests\Unit\Models;

use App\Models\ShopUrl;
use Illuminate\Support\Collection;
use Tests\TestCase;

class ShopUrlsModelTest extends TestCase
{
    /** @dataProvider getterMethodsDataProvider */
    public function testGetters($factoryData, $getter, $expected) {
        $shopURL = ShopUrl::factory()->make($factoryData);
        $result = $shopURL->$getter();
        $this->assertEquals($expected, $result);
    }

    public function testSetShopID() {
        $fakeID = fake()->numberBetween(100, 200);
        $shopUrls = new ShopUrl();
        $shopUrls->setShopID($fakeID);
        $this->assertEquals($fakeID, $shopUrls->shop_id);
    }

    public function testGetUrls() {
        $shopUrls = ShopUrl::factory()->make($this->getArrayOfUrls());
        $result = $shopUrls->getUrls();
        $this->assertEquals(collect($this->getArrayOfUrls(true)), $result);
    }

    private function getterMethodsDataProvider(): array {
        return [
            'back_to_cart URL getter' => [
                ['back_to_cart_url' => 'http://some.website.url/cart/'],
                'getBackToCartUrl',
                'http://some.website.url/cart/',
            ],
            'back_to_store URL getter' => [
                ['back_to_store_url' => 'http://some.website.url/store/'],
                'getBackToStoreUrl',
                'http://some.website.url/store/',
            ],
            'login URL getter' => [
                ['login_url' => 'http://some.website.url/login/'],
                'getLoginUrl',
                'http://some.website.url/login/',
            ],
            'logo URL getter' => [
                ['logo_url' => 'http://some.website.url/logo.jpg'],
                'getLogoUrl',
                'http://some.website.url/logo.jpg',
            ],
            'favicon URL getter' => [
                ['favicon_url' => 'http://some.website.url/favicon.ico'],
                'getFaviconUrl',
                'http://some.website.url/favicon.ico',
            ],
        ];
    }

    private function getArrayOfUrls($asCollection = false): array|Collection {
        if ($asCollection) {
            return collect([
                'backToCart' => 'http://some.website.url/cart/',
                'backToStore' => 'http://some.website.url/store/',
                'login' => 'http://some.website.url/login/',
                'logo' => 'http://some.website.url/logo.jpg',
                'favicon' => 'http://some.website.url/favicon.ico',
            ]);
        } else {
            return [
                'back_to_cart_url' => 'http://some.website.url/cart/',
                'back_to_store_url' => 'http://some.website.url/store/',
                'login_url' => 'http://some.website.url/login/',
                'logo_url' => 'http://some.website.url/logo.jpg',
                'favicon_url' => 'http://some.website.url/favicon.ico',
            ];
        }
    }
}