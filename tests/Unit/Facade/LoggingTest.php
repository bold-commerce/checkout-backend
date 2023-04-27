<?php

namespace Tests\Unit\Facade;

use App\Facades\Logging;
use App\Services\LoggingService;
use Tests\TestCase;

class LoggingTest extends TestCase
{
    public function testGetFacadeAccessor()
    {
        $jwt = Logging::getFacadeRoot();
        $this->assertInstanceOf(LoggingService::class, $jwt);
    }
}
