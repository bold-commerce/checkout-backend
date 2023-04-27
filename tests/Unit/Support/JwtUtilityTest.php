<?php

namespace Tests\Unit\Services;

use App\Models\JwtModel;
use App\Support\JwtUtility;
use Illuminate\Support\Facades\Config;
use Mockery as M;
use Tests\TestCase;
use Firebase\JWT\JWT;

class JwtUtilityTest extends TestCase
{
    protected $jwtModelDataMock = [
        'auth_type' => 'test_type',
        'payload' => [],
    ];

    public function setUp(): void{
        parent::setUp();
        Config::set('services.bold_checkout.checkout_api_jwt_key', 'localdev');
    }

    public function testGenerateToken() {

        $jwtModelMock = M::mock(JwtModel::class);
        $jwtModelMock->shouldReceive('toArray')
            ->once()
            ->andReturns($this->jwtModelDataMock);

        $expected = JWT::encode($this->jwtModelDataMock, 'localdev', 'HS256');

        $result = JwtUtility::generateToken($jwtModelMock);
        $this->assertEquals($expected, $result);
    }

    public function testDecodeToken() {

        $jwtModelMock = M::mock(JwtModel::class);
        $jwtModelMock->shouldReceive('toArray')
            ->once()
            ->andReturns($this->jwtModelDataMock);
        $expectedJwtModel = new JwtModel('test_type', [], null, null, null);

        $jwt = JwtUtility::generateToken($jwtModelMock);
        $result = JwtUtility::decodeToken($jwt);

        $this->assertInstanceOf(JwtModel::class, $result);
        $this->assertEquals($expectedJwtModel, $result);
    }
}
