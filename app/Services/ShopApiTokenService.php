<?php

declare(strict_types=1);

namespace App\Services;

use App\Constants\Errors;
use App\Constants\Fields;
use App\Exceptions\InvalidTokenException;
use App\Exceptions\ParameterEmptyException;
use App\Exceptions\ShopTokenNotFoundException;
use App\Models\Shop;
use App\Models\ShopApiToken;
use Illuminate\Contracts\Encryption\DecryptException;
use Illuminate\Contracts\Encryption\EncryptException;
use Illuminate\Support\Facades\Crypt;

class ShopApiTokenService
{
    /**
     * @throws ShopTokenNotFoundException
     */
    public function getApiTokenByShopID(int $shopID): ?ShopApiToken
    {
        $apiToken = ShopApiToken::where('shop_id', '=', $shopID);
        if ($apiToken->count() !== 1) {
            throw new ShopTokenNotFoundException(sprintf(Errors::NOT_FOUND, Fields::TOKEN));
        }

        $token = $apiToken->first();
        try {
            $decryptedToken = Crypt::decryptString($token->getToken());
            $token->setToken($decryptedToken);

            return $token;
        } catch (DecryptException $e) {
            throw new ShopTokenNotFoundException(sprintf(Errors::NOT_FOUND, Fields::TOKEN));
        }
    }

    /**
     * @throws InvalidTokenException
     */
    public function verifyToken(Shop $shop, string $token): void
    {
        try {
            $apiToken = ShopApiToken::find($shop->getID());
            if (empty($apiToken)) {
                throw new InvalidTokenException(Errors::INVALID_SHOP_OR_TOKEN_VERIFICATION);
            }

            $encryptedToken = $apiToken->getToken();
            if (empty($encryptedToken)) {
                throw new InvalidTokenException(Errors::INVALID_SHOP_OR_TOKEN_VERIFICATION);
            }

            $decryptedToken = Crypt::decryptString($encryptedToken);
            if ($decryptedToken !== $token) {
                throw new InvalidTokenException(Errors::INVALID_SHOP_OR_TOKEN_VERIFICATION);
            }
        } catch (DecryptException $e) {
            throw new InvalidTokenException(Errors::ERROR_ENCRYPT_TOKEN);
        }
    }

    /**
     * @throws InvalidTokenException
     * @throws ParameterEmptyException
     */
    public static function insertToken(Shop $shop, string $shopApiToken): void
    {
        if (empty($shopApiToken)) {
            throw new InvalidTokenException(Errors::EMPTY_TOKEN);
        }

        $token = ShopApiToken::find($shop->getID());
        if (empty($token)) {
            $token = new ShopApiToken();
            $token->setShopID($shop->getID());
        }

        try {
            $encryptedToken = Crypt::encryptString($shopApiToken);
            $token->populateFromArray(['api_token' => $encryptedToken]);
            $token->save();
        } catch (EncryptException $e) {
            throw new InvalidTokenException(Errors::ERROR_ENCRYPT_TOKEN);
        }
    }
}
