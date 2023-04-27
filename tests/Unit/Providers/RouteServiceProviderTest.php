<?php

use App\Providers\RouteServiceProvider;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Illuminate\Routing\RouteCollection;
use Illuminate\Contracts\Foundation\Application as app;
use Mockery as M;
use Tests\TestCase;

class RouteServiceProviderTest extends TestCase
{
    protected RouteServiceProvider $routeServiceProvider;

    /** @var Request|M\Mock */
    protected $requestMock;

    /** @var RouteServiceProvider|M\Mock */
    protected $routeServiceProviderMock;

    /** @var Route|M\Mock */
    protected $routeMock;

    /** @var RouteCollection|M\Mock */
    protected $routeCollectionMock;



    public function setUp(): void
    {
        parent::setUp();
        $this->requestMock = M::mock(Request::class);
        $this->routeServiceProviderMock = M::mock(RouteServiceProvider::class)->makePartial()->shouldAllowMockingProtectedMethods();
        $this->routeCollectionMock = M::mock(RouteCollection::class);
    }

    public function testBootMethodConfiguresRateLimitingAndRoutes()
    {
        $test = $this;
        $this->routeServiceProviderMock->shouldReceive('configureRateLimiting')
            ->once()
            ->andReturnNull();
        $this->routeServiceProviderMock->shouldReceive('routes')
            ->once();

        $this->routeServiceProviderMock->boot();
    }
}
