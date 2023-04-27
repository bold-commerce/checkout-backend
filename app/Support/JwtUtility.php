<?php

declare(strict_types=1);

namespace App\Support;

use App\Models\JwtModel;
use Firebase\JWT\BeforeValidException;
use Firebase\JWT\ExpiredException;
use Firebase\JWT\JWT;
use Firebase\JWT\Key;
use Firebase\JWT\SignatureInvalidException;

class JwtUtility
{
    public static function generateToken(JwtModel $jwtModel): string
    {
        return JWT::encode($jwtModel->toArray(), config('services.bold_checkout.checkout_api_jwt_key', 'localdev'), 'HS256');
    }

    /**
     * @throws \UnexpectedValueException Provided JWT was invalid
     * @throws SignatureInvalidException Provided JWT was invalid because the signature verification failed
     * @throws BeforeValidException      Provided JWT is trying to be used before it's eligible as defined by 'nbf' or before it's been created as defined by 'iat'
     * @throws ExpiredException          Provided JWT has since expired, as defined by the 'exp' claim
     */
    public static function decodeToken(string $jwt): JwtModel
    {
        $jwtObject = JWT::decode($jwt, new Key(config('services.bold_checkout.checkout_api_jwt_key', 'localdev'), 'HS256') );
        $payload = json_decode(json_encode($jwtObject->payload), true);
        $exp = $jwtObject->exp ?? null;
        $nbf = $jwtObject->nbf ?? null;
        $iat = $jwtObject->iat ?? null;

        return new JwtModel($jwtObject->auth_type, $payload, $exp, $nbf, $iat);
    }
}
