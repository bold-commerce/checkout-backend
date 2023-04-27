<?php

namespace Tests\Unit\Models;

use App\Models\Shop;
use Tests\TestCase;

class ShopModelTest extends TestCase
{
    /** @dataProvider gettersDataProvider */
    public function testGetters($factoryData, $getter, $expected) {
        $shop = Shop::factory()->make($factoryData);
        $result = $shop->$getter();
        $this->assertEquals($expected, $result);
    }

    /** @dataProvider compareInfosEmptyParameterDataProvider */
    public function testCompareInfosEmptyParameter(&$parameters, $keyToRemove) {
        unset($parameters[$keyToRemove]);
        $infos = $this->getShopInfosMock();
        $result = Shop::compareInfos($parameters, $infos);
        $this->assertFalse($result);
    }

    /** @dataProvider compareInfosEmptyInfoDataProvider */
    public function testCompareInfosEmptyInfo(&$infos, $keyToRemove) {
        unset($infos[$keyToRemove]);
        $parameters = $this->getShopInfosMock(false);
        $result = Shop::compareInfos($parameters, $infos);
        $this->assertFalse($result);
    }

    /** @dataProvider compareInfosEqualities */
    public function testCompareInfosTestEqualities($key) {
        $infos = $this->getShopInfosMock();
        $parameters = $this->getShopInfosMock(false);
        $infos[$key] = fake()->text(20);
        $result = Shop::compareInfos($parameters, $infos);
        $this->assertFalse($result);
    }

    private function compareInfosEqualities(): array {
        return [
            'platform domains different' => [
                'shop_domain',
            ],
            'platform types different' => [
                'platform_slug',
            ],
            'platform identifiers different' => [
                'shop_identifier',
            ],
        ];
    }

    private function compareInfosEmptyParameterDataProvider(): array
    {
        return [
            'Parameters - Platform Domain is empty' => [
                $this->getShopInfosMock(false),
                'platform_domain',
            ],
            'Parameters - Platform Type is empty' => [
                $this->getShopInfosMock(false),
                'platform_type',
            ],
            'Parameters - Platform Identifier is empty' => [
                $this->getShopInfosMock(false),
                'platform_identifier',
            ],
        ];
    }

    private function compareInfosEmptyInfoDataProvider(): array {
        return [
            'Infos - Shop Domain is empty' => [
                $this->getShopInfosMock(),
                'shop_domain',
            ],
            'Infos - Platform Slug is empty' => [
                $this->getShopInfosMock(),
                'platform_slug',
            ],
            'Infos - Shop Identifier is empty' => [
                $this->getShopInfosMock(),
                'shop_identifier',
            ],
        ];
    }

    private function gettersDataProvider(): array {
        return [
            'id' => [
                ['id' => 1234],
                'getID',
                1234,
            ],
            'platform_domain' => [
                ['platform_domain' => 'myplatform.something.org'],
                'getPlatformDomain',
                'myplatform.something.org',
            ],
            'platform_type' => [
                ['platform_type' => 'some_platform'],
                'getPlatformType',
                'some_platform',
            ],
            'platform_identifier' => [
                ['platform_identifier' => 'store-1234'],
                'getPlatformIdentifier',
                'store-1234',
            ],
            'shop_name' => [
                ['shop_name' => 'My Shop Name'],
                'getShopName',
                'My Shop Name',
            ],
            'support_email' => [
                ['support_email' => 'some.email@example.com'],
                'getSupportEmail',
                'some.email@example.com',
            ],
        ];
    }

    private function getShopInfosMock($isInfos = true, $random = false): array {
        if ($isInfos) {
            return [
                'shop_domain' => $random ? fake()->url() : 'some platform domain',
                'platform_slug' => $random ? fake()->text(10) : 'some platform slug',
                'shop_identifier' => $random ? fake()->text(10) : 'some shop identifier',
            ];
        } else {
            return [
                'platform_domain' => $random ? fake()->url() : 'some platform domain',
                'platform_type' => $random ? fake()->text(12) : 'some platform slug',
                'platform_identifier' => $random ? fake()->text(10) : 'some shop identifier',
            ];
        }
    }
}