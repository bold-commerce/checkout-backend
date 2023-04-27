<?php

namespace Tests\Unit\Services;

use App\Exceptions\InvalidTokenException;
use App\Exceptions\ShopTokenNotFoundException;
use App\Models\ShopApiToken;
use App\Models\Shop;
use App\Services\ShopApiTokenService;
use Tests\TestCase;
use Illuminate\Contracts\Encryption\DecryptException;
use Illuminate\Contracts\Encryption\EncryptException;
use Illuminate\Support\Facades\Crypt;
use Exception;

class ShopApiTokenServiceTest extends TestCase
{
    protected ShopApiTokenService $shopApiTokenService;

    public function setUp(): void
    {
        parent::setUp();
        $this->shopApiTokenService = app()->make(ShopApiTokenService::class);
    }

    public function testGetApiTokenByShopIdReturnsIfEntryExists()
    {
        $shopId = 1;
        ShopApiToken::factory()->create(['shop_id' => $shopId]);
        $apiTokenModel = $this->shopApiTokenService->getApiTokenByShopID($shopId);
        $this->assertNotEmpty($apiTokenModel);
    }

    public function testGetApiTokenByShopIdErrorsIfEntryDoesNotExist()
    {
        $this->expectException(ShopTokenNotFoundException::class);
        $fakeShopId = fake()->randomNumber(6);
        $this->shopApiTokenService->getApiTokenByShopID($fakeShopId);
    }

    public function testGetApiTokenByShopIdErrorsIfDecryptingTokenFails()
    {
        $shopId = 1;
        $model = ShopApiToken::factory()->create(['shop_id' => $shopId]);

        Crypt::shouldReceive('decryptString')
            ->once()
            ->andThrow(new DecryptException);

        $this->expectException(ShopTokenNotFoundException::class);

        $this->shopApiTokenService->getApiTokenByShopID($shopId);
    }

    public function testVerifyTokenShouldErrorIfNoShopApiTokenFoundForShop()
    {
        $shop = Shop::factory()->create();
        $token = 'some-token';
        // Note: we are not creating a ShopApiToken, so this should fail.

        $this->expectException(InvalidTokenException::class);

        $this->shopApiTokenService->verifyToken($shop, $token);
    }

    public function testVerifyTokenShouldErrorIfRetrievedTokenIsEmpty()
    {
        $shopId = 1;
        $shop = Shop::factory()->create(['id' => $shopId]);
        $apiToken = ShopApiToken::factory()->create([
            'shop_id' => $shopId,
            'api_token' => '',
        ]);
        $token = 'some-token';

        $this->expectException(InvalidTokenException::class);

        $this->shopApiTokenService->verifyToken($shop, $token);
    }

    public function testVerifyTokenShouldErrorIfDecryptionFails()
    {
        $shopId = 1;
        $shop = Shop::factory()->create(['id' => $shopId]);
        $apiToken = ShopApiToken::factory()->create([
            'shop_id' => $shopId,
            'api_token' => 'not-a-valid-token',
        ]);
        $token = 'different-token';
        Crypt::shouldReceive('decryptString')
            ->once()
            ->with('not-a-valid-token')
            ->andThrow(new DecryptException);

        $this->expectException(InvalidTokenException::class);

        $this->shopApiTokenService->verifyToken($shop, $token);
    }

    public function testInsertTokenWithEmptyTokenThrowsError()
    {
        $shop = Shop::factory()->create();
        $token = '';

        $this->expectException(InvalidTokenException::class);

        $this->shopApiTokenService->insertToken($shop, $token);
    }

    public function testInsertTokenCreatesNewShopApiTokenIfNotExists()
    {
        $shopId = 1;
        $shop = Shop::factory()->create(['id' => $shopId]);
        $token = 'some_token';

        $this->assertEquals(ShopApiToken::where('shop_id', $shopId)->count(), 0);
        $this->shopApiTokenService->insertToken($shop, $token);
        $this->assertEquals(ShopApiToken::where('shop_id', $shopId)->count(), 1);
    }

    public function testInsertTokenUpdatesExistingShopApiTokenIfExists()
    {
        $shopId = 1;
        $shop = Shop::factory()->create(['id' => $shopId]);
        $token = 'some_token';
        $shopApiToken = ShopApiToken::factory()->create([
            'shop_id' => $shopId,
            'api_token' => '123',
        ]);

        $this->shopApiTokenService->insertToken($shop, $token);

        $this->assertNotEquals(
            ShopApiToken::where('shop_id', $shopId)->first()->getToken(),
            '123',
        );
    }

    public function testInsertTokenThrowsInvalidTokenExceptionIfEncryptionFails()
    {
        $shop = Shop::factory()->create();
        $token = 'some_token';
        Crypt::shouldReceive('encryptString')
            ->once()
            ->andThrow(new EncryptException);

        $this->expectException(InvalidTokenException::class);

        $this->shopApiTokenService->insertToken($shop, $token);
    }

    public function testVerifyTokenShouldSucceedIfSameValueWasPassedToInsertToken()
    {
        $shopId = 1;
        $shop = Shop::factory()->create(['id' => $shopId]);
        $token = 'some_token';
        $this->shopApiTokenService->insertToken($shop, $token);
        $tok = ShopApiToken::where('shop_id', $shopId)->first()->getToken();

        $ex = null;
        try {
            $this->shopApiTokenService->verifyToken($shop, $token);
        } catch (Exception $e) {
            $ex = $e;
        }

        $this->assertNull($ex);
    }
}
