<?php

namespace Tests\Unit\Models;

use App\Models\Assets;
use App\Services\ExperienceService;
use Mockery as M;
use Tests\TestCase;

class AssetsModelTest extends TestCase
{
    public function setUp(): void
    {
        parent::setUp();
    }

    public function testFunctionGetID()
    {
        $asset = Assets::factory()->make(['id' => 60]);
        $this->assertEquals(60, $asset->getID());
    }

    public function testFunctionGetName()
    {
        $asset = Assets::factory()->make(['asset_name' => 'some name']);
        $this->assertEquals('some name', $asset->getName());
    }

    public function testFunctionGetURL()
    {
        $fakeURL = fake()->url();
        $asset = Assets::factory()->make(['asset_url' => $fakeURL]);
        $this->assertEquals($fakeURL, $asset->getUrl());
    }

    public function testFunctionGetPosition()
    {
        $asset = Assets::factory()->make();
        $this->assertEquals(1, $asset->getPosition());
    }

    public function testFunctionGetAssetType()
    {
        $asset = Assets::factory()->make();
        $this->assertEquals('js', $asset->getAssetType());
    }

    public function testFunctionGetFlowID()
    {
        $asset = Assets::factory()->make(['flow_id' => 'some flow_id']);
        $this->assertEquals('some flow_id', $asset->getFlowID());
    }

    public function testFunctionisAsynchronous()
    {
        $asset = Assets::factory()->make();
        $this->assertFalse($asset->isAsynchronous());
    }

    public function testSetCompleteAssetURL()
    {
        $assetURL = '{{assets_url}}/asset.js';
        $fakeURL = fake()->url();
        $replacedAssetURL = str_replace('{{assets_url}}', $fakeURL, $assetURL);

        $experienceServiceMock = M::mock();
        $this->app->instance(ExperienceService::class, $experienceServiceMock);
        $experienceServiceMock->shouldReceive('getAssetsUrl')->andReturn($fakeURL);

        $asset = Assets::factory()->make(['asset_url' => $assetURL]);
        $asset->setCompleteAssetUrl();
        $this->assertEquals($replacedAssetURL, $asset->getURL());
    }
}
