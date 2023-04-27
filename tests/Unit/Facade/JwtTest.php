<?php

namespace Tests\Unit\Facade;

use App\Facades\Jwt;
use App\Support\JwtUtility;
use Tests\TestCase;

class JwtTest extends TestCase
{
    public function testGetFacadeAccessor()
    {
        $jwt = Jwt::getFacadeRoot();
        $this->assertInstanceOf(JwtUtility::class, $jwt);
    }
}
