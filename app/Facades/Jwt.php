<?php

namespace App\Facades;

use App\Models\JwtModel;
use App\Support\JwtUtility;
use Illuminate\Support\Facades\Facade;

/**
 * @method static string   generateToken(JwtModel $jwtModel)
 * @method static JwtModel decodeToken($token)
 */
class Jwt extends Facade
{
    protected static function getFacadeAccessor()
    {
        return JwtUtility::class;
    }
}
