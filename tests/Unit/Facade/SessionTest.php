<?php

namespace Tests\Unit\Facade;

use App\Facades\Session;
use App\Services\SessionService;
use Tests\TestCase;

class SessionTest extends TestCase
{
    public function testGetFacadeAccessor()
    {
        $jwt = Session::getFacadeRoot();
        $this->assertInstanceOf(SessionService::class, $jwt);
    }
}
