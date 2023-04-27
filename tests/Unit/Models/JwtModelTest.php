<?php

namespace Tests\Unit\Models;

use App\Models\JwtModel;
use Tests\TestCase;

class JwtModelTest extends TestCase
{
    public function testGetAuthTypeFunction() {
        $jwtToken = new JwtModel('auth_type', ['key' => 'value']);
        $this->assertEquals('auth_type', $jwtToken->getAuthType());
    }

    public function testToArrayFunction() {
        $jwtToken = new JwtModel('auth_type', ['key' => 'value'], 1000, 2000, 3000);
        $expected = [
            'auth_type' => 'auth_type',
            'payload' => ['key' => 'value'],
            'exp' => 1000,
            'nbf' => 2000,
            'iat' => 3000,
        ];
        $this->assertEquals($expected, $jwtToken->toArray());
    }
}