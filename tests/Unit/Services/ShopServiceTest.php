<?php

namespace Tests\Unit\Services;

use App\Exceptions\ShopInstanceException;
use App\Exceptions\ShopNotFoundException;
use App\Models\FrontendShop;
use App\Models\Shop;
use App\Services\ShopService;
use Tests\TestCase;

class ShopServiceTest extends TestCase
{
    protected ShopService $shopService;

    public function setUp(): void
    {
        parent::setUp();
        $this->shopService = app()->make(ShopService::class);
    }

    private function makeShopParameters()
    {
        return $this->makeShopParametersForShop(Shop::factory()->make());
    }

    private function makeShopParametersForShop($shop)
    {
        $shopParameters = [
            'platform_domain' => $shop->getPlatformDomain(),
            'platform_type' => $shop->getPlatformType(),
            'platform_identifier' => $shop->getPlatformIdentifier(),
            'shop_name' => $shop->getShopName(),
            'support_email' => $shop->getSupportEmail(),
        ];
        return $shopParameters;
    }

    private function makeShopInfo()
    {
        return $this->makeShopInfoForShop(Shop::factory()->make());
    }

    private function makeShopInfoForShop($shop)
    {
        $shopInfo = [
            'shop_domain' => $shop->getPlatformDomain(),
            'platform_slug' => $shop->getPlatformType(),
            'shop_identifier' => $shop->getPlatformIdentifier(),
        ];
        return $shopInfo;
    }

    public function testGetInstanceReturnsFrontendShop()
    {
        $instance = $this->shopService->getInstance();
        $this->assertInstanceOf(FrontendShop::class, $instance);
    }

    public function testGetShopErrorsIfShopNotFound()
    {
        $shopId = fake()->randomNumber();
        $this->expectException(ShopNotFoundException::class);
        $this->shopService->getShop($shopId);
    }

    public function testGetShopReturnsCorrectShopIfExists()
    {
        $shopId = 1;
        $shop = Shop::factory()->create(['id' => $shopId]);
        $returnedShop = $this->shopService->getShop($shopId);
        $this->assertEquals(
            $shop->toArray(),
            $returnedShop->toArray(),
        );
    }

    public function testGetShopByDomainErrorsIfNoShopFoundForDomain()
    {
        $nonExistantDomain = 'mydomain.example.com';
        $this->expectException(ShopNotFoundException::class);
        $this->shopService->getShopByDomain($nonExistantDomain);
    }

    public function testGetShopByDomainReturnsExpectedShopIfExists()
    {
        $shop = Shop::factory()->create();
        $returnedShop = $this->shopService->getShopByDomain($shop->getPlatformDomain());
        $this->assertEquals(
            $shop->toArray(),
            $returnedShop->toArray(),
        );
    }

    public function testGetShopByIdentifierErrorsIfShopNotFound()
    {
        $nonExistantDomain = fake()->text(10);
        $this->expectException(ShopNotFoundException::class);
        $this->shopService->getShopByIdentifier($nonExistantDomain);
    }

    public function testGetShopByIdentifierReturnsShopIfExists()
    {
        $shop = Shop::factory()->create();
        $returnedShop = $this->shopService->getShopByIdentifier($shop->getPlatformIdentifier());
        $this->assertEquals(
            $shop->toArray(),
            $returnedShop->toArray(),
        );
    }

    public function testGetShopByIdentifierOrDomainErrorsIfShopNotExistsForEitherDomainOrIdentifier()
    {
        $nonExistantIdentifierOrDomain = fake()->text(10);
        $this->expectException(ShopNotFoundException::class);
        $this->shopService->getShopByIdentifierOrDomain($nonExistantIdentifierOrDomain);
    }

    public function testGetShopByIdentifierOrDomainReturnsCorrectShopIfIdentifierMatches()
    {
        $shop = Shop::factory()->create();
        $returnedShop = $this->shopService->getShopByIdentifierOrDomain($shop->getPlatformIdentifier());
        $this->assertEquals(
            $shop->toArray(),
            $returnedShop->toArray(),
        );
    }

    public function testGetShopByIdentifierOrDomainReturnsCorrectShopIfDomainMatches()
    {
        $shop = Shop::factory()->create();
        $returnedShop = $this->shopService->getShopByIdentifierOrDomain($shop->getPlatformDomain());
        $this->assertEquals(
            $shop->toArray(),
            $returnedShop->toArray(),
        );
    }

    public function testCreateShopFromArrayErrorsIfNoParametersProvided()
    {
        $shopParameters = [];
        $shopInfo = ['shop_domain' => fake()->url()];
        $this->expectException(ShopNotFoundException::class);
        $this->shopService->createShopFromArray($shopParameters, $shopInfo);
    }

    public function testCreateShopFromArrayErrorsIfNoInfosProvided()
    {
        $shopParameters = ['platform_domain' => fake()->url()];
        $shopInfo = [];
        $this->expectException(ShopInstanceException::class);
        $this->shopService->createShopFromArray($shopParameters, $shopInfo);
    }

    public function testCreateShopFromArrayErrorsIfParametersListIncomplete()
    {
        $shop = Shop::factory()->make();
        $shopParameters = [
            'platform_domain' => $shop->getPlatformDomain(),
            'platform_type' => $shop->getPlatformType(),
        ];
        $shopInfo = $this->makeShopInfoForShop($shop);

        $this->expectException(ShopNotFoundException::class);

        $this->shopService->createShopFromArray($shopParameters, $shopInfo);
    }

    public function testCreateShopFromArrayErrorsIfInfosAndParametersDoNotMatch()
    {
        $shopParameters = $this->makeShopParameters();
        $shopInfo = $this->makeShopInfo();

        $this->expectException(ShopNotFoundException::class);

        $this->shopService->createShopFromArray($shopParameters, $shopInfo);
    }

    public function testCreateShopFromArrayCreatesShopIfNotExists()
    {
        $shop = Shop::factory()->make();
        $shopParameters = $this->makeShopParametersForShop($shop);
        $shopInfo = $this->makeShopInfoForShop($shop);

        $this->shopService->createShopFromArray($shopParameters, $shopInfo);

        $shopExists = Shop::where('platform_domain', $shopParameters['platform_domain'])
            ->where('platform_type', $shopParameters['platform_type'])
            ->where('platform_identifier', $shopParameters['platform_identifier'])
            ->count() === 1;
        $this->assertEquals($shopExists, true);
    }
}
