<?php

namespace Tests\Unit\Services;

use App\Exceptions\AssetNotFoundException;
use App\Services\AssetsService;
use Tests\TestCase;

class AssetsServiceTest extends TestCase
{
    protected AssetsService $assetsService;

    public function setUp(): void
    {
        parent::setUp();

        $this->artisan('migrate');

        $this->assetsService = app()->make(AssetsService::class);
    }

    public function testGetAssetByIdWrongID()
    {
        $this->expectException(AssetNotFoundException::class);
        $asset = $this->assetsService->getAssetByID(100);
    }

    public function testGetAssetByIdCorrectID()
    {
        $asset = $this->assetsService->getAssetByID(1);
        $this->assertNotEmpty($asset);
    }

    public function testGetAssetByNameWrongName()
    {
        $this->expectException(AssetNotFoundException::class);
        $asset = $this->assetsService->getAssetByName('some fake name');
    }

    public function testGetAssetByNameCorrectName()
    {
        $asset = $this->assetsService->getAssetByName('3 Pages template');
        $this->assertNotEmpty($asset);
    }
}
