<?php

namespace Tests\Unit\Services;

use App\Constants\Assets as AssetsConstants;
use App\Exceptions\InvalidAssetException;
use App\Exceptions\ResourceMissingException;
use App\Models\Assets;
use App\Models\Shop;
use App\Models\ShopAssets;
use App\Services\ShopAssetsService;
use Tests\TestCase;

class ShopAssetsServiceTest extends TestCase
{
    protected ShopAssetsService $shopAssetsService;

    public function setUp(): void
    {
        parent::setUp();
        $this->shopAssetsService = app()->make(ShopAssetsService::class);
    }

    public function testGetAssetsByShopIdErrorsWhenShopDoesNotExist()
    {
        $idForNonexistantShop = 123;
        $this->expectException(ResourceMissingException::class);
        $this->shopAssetsService->getAssetsByShopID($idForNonexistantShop);
    }

    public function testGetAssetsByShopIdErrorsWhenShopAssetsNotFoundForShop()
    {
        $shopId = 1;
        Shop::factory()->create(['id' => $shopId]);
        // Note: no assets have been associated with this shop.
        $this->expectException(ResourceMissingException::class);
        $this->shopAssetsService->getAssetsByShopID($shopId);
    }

    public function testGetAssetsByShopIdErrorsWhenTemplateNotFoundForShopAssets()
    {
        $shopId = 1;
        Shop::factory()->create(['id' => $shopId]);
        ShopAssets::factory()->create(['shop_id' => $shopId]);
        // Note: no assets have been associated with this shop.
        $this->expectException(ResourceMissingException::class);
        $this->shopAssetsService->getAssetsByShopID($shopId);
    }

    public function testGetAssetsByShopIdReturnsTemplateIfExists()
    {
        $shopId = 1;
        Shop::factory()->create(['id' => $shopId]);
        $shopAssetId = 20;
        $shopAssets = ShopAssets::factory()->create(['shop_id' => $shopId, 'asset_id' => $shopAssetId]);
        $asset = Assets::factory()->create(['id' => $shopAssetId]);

        $assets = $this->shopAssetsService->getAssetsByShopID($shopId);

        $this->assertNotEmpty($assets['template']);
    }

    public function testGetAssetsByShopIdReturnsChildAssetsIfExist()
    {
        $shopId = 1;
        Shop::factory()->create(['id' => $shopId]);
        $shopAssetsId = 27;
        $shopAssets = ShopAssets::factory()->create(['asset_id' => $shopAssetsId, 'shop_id' => $shopId]);
        $template = Assets::factory()->create(['id' => $shopAssetsId]);
        $numChildren = 3;
        Assets::factory()->count($numChildren)->create(['parent_id' => $shopAssetsId]);

        $assets = $this->shopAssetsService->getAssetsByShopID($shopId);
        $children = $assets['children'];
        $allChildren = $children['header']
            ->concat($children['body'])
            ->concat($children['footer']);

        $this->assertEquals($allChildren->count(), $numChildren);
    }

    public function testGetAssetsByShopIdCorrectlyCategorizesChildAssets()
    {
        $shopId = 1;
        Shop::factory()->create(['id' => $shopId]);
        $assetId = 33;
        $shopAssets = ShopAssets::factory()->create(['shop_id' => $shopId, 'asset_id' => $assetId]);
        $template = Assets::factory()->create(['id' => $assetId]);
        $headerChildId = 100;
        $headerChild = Assets::factory()->create([
            'id' => $headerChildId,
            'parent_id' => $assetId,
            'position' => AssetsConstants::ASSET_POSITION_HEADER,
        ]);
        $bodyChildId = 101;
        $bodyChild = Assets::factory()->create([
            'id' => $bodyChildId,
            'parent_id' => $assetId,
            'position' => AssetsConstants::ASSET_POSITION_BODY,
        ]);
        $footerChildId = 102;
        $footerChild = Assets::factory()->create([
            'id' => $footerChildId,
            'parent_id' => $assetId,
            'position' => AssetsConstants::ASSET_POSITION_FOOTER,
        ]);

        $assets = $this->shopAssetsService->getAssetsByShopID($shopId);

        $this->assertEquals($assets['children']['header']->count(), 1);
        $this->assertEquals($assets['children']['header']->first()->getID(), $headerChildId);

        $this->assertEquals($assets['children']['body']->count(), 1);
        $this->assertEquals($assets['children']['body']->first()->getID(), $bodyChildId);

        $this->assertEquals($assets['children']['footer']->count(), 1);
        $this->assertEquals($assets['children']['footer']->first()->getID(), $footerChildId);
    }

    public function testGetAssetsByShopIdDoesNotIncludeUnrelatedAssets()
    {
        $shopId = 1;
        Shop::factory()->create(['id' => $shopId]);
        $shopAssetId = 44;
        $shopAssets = ShopAssets::factory()->create(['shop_id' => $shopId, 'asset_id' => $shopAssetId]);
        $template = Assets::factory()->create(['id' => $shopAssetId]);

        // Unrelated asset
        Assets::factory()->create();

        $assets = $this->shopAssetsService->getAssetsByShopID($shopId);
        $children = $assets['children'];
        $allChildren = $children['header']
            ->concat($children['body'])
            ->concat($children['footer']);

        $this->assertEquals($allChildren->count(), 0);
    }

    public function testInsertAssetErrorsIfAssetKeyEmpty()
    {
        $shop = Shop::factory()->create();
        $asset = '';

        $this->expectException(InvalidAssetException::class);

        $this->shopAssetsService->insertAsset($shop, $asset);
    }

    public function testInsertAssetErrorsIfNoAssetFoundForName() {
        $shop = Shop::factory()->create();
        $assetName = 'key-for-nonexistant-asset';

        $this->expectException(InvalidAssetException::class);

        $this->shopAssetsService->insertAsset($shop, $assetName);
    }

    public function testInsertAssetCorrectlyInsertsAssetByName() {
        $assetName = 'example-asset-name';
        $shopId = 1;
        $shop = Shop::factory()->create(['id' => $shopId]);
        $assetId = 123;
        $asset = Assets::factory()->create(['id' => $assetId, 'asset_name' => $assetName]);

        $this->shopAssetsService->insertAsset($shop, $assetName);

        $assetCount = ShopAssets::where('shop_id', $shopId)
            ->where('asset_id', $assetId)
            ->count();

        $this->assertEquals($assetCount, 1);
    }

    public function testInsertAssetCorrectlyInsertsAssetById() {
        $shopId = 1;
        $shop = Shop::factory()->create(['id' => $shopId]);
        $assetId = 123;
        $asset = Assets::factory()->create(['id' => $assetId]);

        $returnedAsset = $this->shopAssetsService->insertAsset($shop, $assetId);

        $assetCount = ShopAssets::where('shop_id', $shopId)
            ->where('asset_id', $assetId)
            ->count();

        $this->assertEquals($assetCount, 1);
    }
}
