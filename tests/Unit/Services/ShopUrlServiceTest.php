<?php

namespace Tests\Unit\Services;

use App\Exceptions\ShopUrlsMissingException;
use App\Models\Shop;
use App\Models\ShopUrl;
use App\Services\ShopUrlService;
use Tests\TestCase;

class ShopUrlServiceTest extends TestCase
{
    protected ShopUrlService $shopUrlService;

    public function setUp(): void
    {
        parent::setUp();
        $this->shopUrlService = app()->make(ShopUrlService::class);
    }

    public function testGetUrlsByShopIdThrowsErrorIfShopNotFound()
    {
        $this->expectException(ShopUrlsMissingException::class);
        $this->shopUrlService->getUrlsByShopID(0);
    }

    public function testGetUrlsByShopIdReturnsUrlsForShopIdIfShopExists()
    {
        $shopId = 1;
        $shop = Shop::factory()->create(['id' => $shopId]);
        $insertedUrls = ShopUrl::factory()->create(['shop_id' => $shopId]);

        $returnedUrls = $this->shopUrlService->getUrlsByShopID($shopId);

        $this->assertEquals($returnedUrls->getBackToCartUrl(), $insertedUrls->getBackToCartUrl());
        $this->assertEquals($returnedUrls->getBackToStoreUrl(), $insertedUrls->getBackToStoreUrl());
        $this->assertEquals($returnedUrls->getLoginUrl(), $insertedUrls->getLoginUrl());
        $this->assertEquals($returnedUrls->getLogoUrl(), $insertedUrls->getLogoUrl());
        $this->assertEquals($returnedUrls->getFaviconUrl(), $insertedUrls->getFaviconUrl());
    }

    /**
     * @dataProvider insertUrlsErrorsIfRequiredUrlMissingDataProvider
     */
    public function testInsertUrlsErrorsIfRequiredUrlMissing($shopUrlsToInclude)
    {
        $shop = Shop::factory()->create();
        $shopUrls = collect($this->createRequiredShopUrls())
            ->filter(function ($value, $key) use ($shopUrlsToInclude) {
                return in_array($value, $shopUrlsToInclude);
            })
            ->toArray();

        $this->expectException(ShopUrlsMissingException::class);

        $this->shopUrlService->insertUrls($shop, $shopUrls);
    }

    /**
     * @dataProvider insertUrlsErrorsIfRequiredUrlMissingDataProvider
     */
    public function testInsertUrlsDoesNotInsertIfRequiredUrlMissing($shopUrlsToInclude)
    {
        $shopId = 1;
        $shop = Shop::factory()->create(['id' => $shopId]);
        $shopUrls = collect($this->createRequiredShopUrls())
            ->filter(function ($value, $key) use ($shopUrlsToInclude) {
                return in_array($value, $shopUrlsToInclude);
            })
            ->toArray();

        $this->expectException(ShopUrlsMissingException::class);
        $this->shopUrlService->insertUrls($shop, $shopUrls);

        $this->assertEquals(ShopUrl::find($shopId)->count(), 0);
    }

    /**
     * @dataProvider insertUrlsErrorsIfRequiredUrlMissingDataProvider
     */
    public function testInsertUrlsDoesNotUpdateIfRequiredUrlMissing($shopUrlsToInclude)
    {
        $shopId = 1;
        $shop = Shop::factory()->create(['id' => $shopId]);
        $existingUrls = ShopUrl::factory()->create(['shop_id' => $shopId]);
        $urlUpdatesToAttempt = collect($this->createRequiredShopUrls())
            ->filter(function ($value, $key) use ($shopUrlsToInclude) {
                return in_array($value, $shopUrlsToInclude);
            })
            ->toArray();

        $this->expectException(ShopUrlsMissingException::class);
        $this->shopUrlService->insertUrls($shop, $urlUpdatesToAttempt);
        $resultUrls = ShopUrl::find($$shopId)->first()->getUrls();

        $this->assertEquals(
            $existingUrls->getUrls()->toArray(),
            $resultUrls->getUrls()->toArray(),
        );
    }

    public function testInsertUrlsCreatesShopUrlsEntryIfNotAlreadyPresent()
    {
        $shopUrls = $this->createRequiredShopUrls();
        $shopId = 1;
        $shop = Shop::factory()->create(['id' => $shopId]);

        $this->shopUrlService->insertUrls($shop, $shopUrls);

        $this->assertEquals(ShopUrl::find($shopId)->count(), 1);
    }

    public function testInsertUrlsUpdatesExistingUrlsIfAlreadyPresent()
    {
        $shopId = 1;
        $shop = Shop::factory()->create(['id' => $shopId]);
        $existingUrls = ShopUrl::factory()->create(['shop_id' => $shopId]);
        $urlUpdatesToApply = array_merge($this->createRequiredShopUrls(), $this->createOptionalShopUrls());

        $this->shopUrlService->insertUrls($shop, $urlUpdatesToApply);
        $resultUrls = ShopUrl::find($shopId)->first()->getUrls();

        $this->assertEquals($resultUrls['backToCart'], $urlUpdatesToApply['back_to_cart_url']);
        $this->assertEquals($resultUrls['backToStore'], $urlUpdatesToApply['back_to_store_url']);
        $this->assertEquals($resultUrls['login'], $urlUpdatesToApply['login_url']);
        $this->assertEquals($resultUrls['logo'], $urlUpdatesToApply['logo_url']);
        $this->assertEquals($resultUrls['favicon'], $urlUpdatesToApply['favicon_url']);
    }

    private function createRequiredShopUrls()
    {
        return [
            'back_to_cart_url' => fake()->url(),
            'back_to_store_url' => fake()->url(),
            'login_url' => fake()->url(),
        ];
    }

    private function createOptionalShopUrls()
    {
        return [
            'logo_url' => fake()->url(),
            'favicon_url' => fake()->url(),
        ];
    }

    private function insertUrlsErrorsIfRequiredUrlMissingDataProvider(): array
    {
        return [
            'Missing required parameter back_to_cart_url' => [
                [
                    'back_to_store_url',
                    'login_url',
                ],
            ],
            'Missing required parameter back_to_store_url' => [
                [
                    'back_to_cart_url',
                    'login_url',
                ],
            ],
            'Missing required parameter login_url' => [
                [
                    'back_to_cart_url',
                    'back_to_store_url',
                ],
            ],
        ];
    }
}
