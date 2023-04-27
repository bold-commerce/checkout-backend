<?php

use App\Http\Controllers\EventsController;
use App\Models\Event;
use App\Models\Shop;
use App\Services\EventsService;
use App\Services\ShopService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Mockery as M;
use Tests\TestCase;


class EventControllerTest extends TestCase
{

    protected Request $requestMock;
    protected ShopService $mockShopService;
    protected EventsService $mockEventService;
    protected EventsController $eventsController;
    protected Event $mockEvent;
    protected Shop $mockShop;

    public function setUp(): void
    {
        parent::setUp();

        $this->requestMock = M::mock(Request::class);
        $this->mockShopService = M::mock(ShopService::class);
        $this->mockEventService = M::mock(EventsService::class);

        $this->eventsController = new EventsController(
            $this->mockShopService,
            $this->mockEventService
        );

        $this->mockEvent = M::mock(Event::class);
        $this->mockShop = M::mock(Shop::class);
    }

    public function testRegisterEventWithNoData(){

        $params = [];
        $this->requestMock->shouldReceive('post')->times(1)->andReturn($params);

        $expected = response()->json([
            'message' => 'Event(s) saved',
            'results' => [
                'saved' => 0,
                'in_error' => 0,
            ],
        ], 201);
        $result = $this->eventsController->register($this->requestMock);
        $this->assertEquals($expected, $result);

    }

    public function testRegisterEventWithData(){

        $params = [['event_name'=> 'CLICK_CHECKOUT_BUTTON']];
        $this->requestMock->shouldReceive('post')->times(1)->andReturn($params);
        $this->requestMock->shouldReceive('header')->times(1)->andReturn('shop_test_id');

        $this->mockEvent->shouldReceive('getID')->times(1)->andReturn(1);
        $this->mockShop->shouldReceive('getID')->times(1)->andReturn(2);

        $this->mockShopService->shouldReceive('getShopByDomain')
            ->with('shop_test_id')
            ->once()
            ->andReturn($this->mockShop);

        $this->mockEventService->shouldReceive('eventNameExists')
            ->once()
            ->with('CLICK_CHECKOUT_BUTTON')
            ->andReturn(true);

        $this->mockEventService->shouldReceive('createEvent')
            ->once()
            ->andReturn($this->mockEvent);

        $expected = response()->json([
            'message' => 'Event(s) saved',
            'results' => [
                'saved' => 1,
                'in_error' => 0,
            ],
        ], 201);
        $result = $this->eventsController->register($this->requestMock);
        $this->assertEquals($expected, $result);

    }

    public function testRegisterEventWithInvalidEventName(){

        $params = [['event_name'=> 'CLICK_CHECKOUT_BUTTONS']];
        $this->requestMock->shouldReceive('post')->times(1)->andReturn($params);
        $this->requestMock->shouldReceive('header')->times(1)->andReturn('shop_test_id');

        $this->mockShopService->shouldReceive('getShopByDomain')
            ->with('shop_test_id')
            ->once()
            ->andReturn($this->mockShop);

        $this->mockEventService->shouldReceive('eventNameExists')
            ->once()
            ->with('CLICK_CHECKOUT_BUTTONS')
            ->andReturn(false);


        $expected = response()->json([
            'message' => 'Event(s) saved',
            'results' => [
                'saved' => 0,
                'in_error' => 1,
            ],
        ], 201);
        $result = $this->eventsController->register($this->requestMock);
        $this->assertEquals($expected, $result);

    }

    public function testRegisterEventWithException(){

        $params = [['event_name'=> 'CLICK_CHECKOUT_BUTTONS']];
        $this->requestMock->shouldReceive('post')->times(1)->andReturn($params);
        $this->requestMock->shouldReceive('header')->times(1)->andReturn('shop_test_id');

        $this->mockShopService->shouldReceive('getShopByDomain')
            ->with('shop_test_id')
            ->once()
            ->andThrow(new Exception('error'));

        Log::shouldReceive('info')
            ->with(Exception::class , ['message' => 'error']);

        $expected = response()->json([
            'message' => 'Event(s) saved',
            'results' => [
                'saved' => 0,
                'in_error' => 0,
            ],
        ], 201);
        $result = $this->eventsController->register($this->requestMock);
        $this->assertEquals($expected, $result);

    }

}
