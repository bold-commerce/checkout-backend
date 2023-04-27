<?php

namespace Tests\Unit\Services;

use App\Models\Event;
use App\Services\EventsService;
use Tests\TestCase;

class EventsServiceTest extends TestCase
{
    protected EventsService $eventsService;

    public function setUp(): void
    {
        parent::setUp();

        $this->artisan('migrate');

        $this->eventsService = app()->make(EventsService::class);
    }

    public function testCreateEventFunctionNotSaved()
    {
        $fakeShopID = fake()->randomNumber(2);
        $fakeName = fake()->text(20);
        $event = $this->eventsService->createEvent($fakeShopID, $fakeName);
        $this->assertNotEmpty($event);
        $this->assertNull($event->getID());
    }

    public function testCreateEventFunctionImmediatlySaved()
    {
        $fakeShopID = fake()->randomNumber(2);
        $fakeName = fake()->text(20);
        $event = $this->eventsService->createEvent($fakeShopID, $fakeName, saveImmediatly: true);
        $this->assertNotEmpty($event);
        $this->assertNotNull($event->getID());
    }

    public function testEventNameExistsReturnsFalse()
    {
        $eventName = 'fake event name';
        $result = $this->eventsService->eventNameExists($eventName);
        $this->assertFalse($result);
    }

    public function testEventNameExistsReturnsTrue()
    {
        $eventName = 'CONTROLLER_INIT_ORDER_INIT_ENDPOINT_RESPONDED';
        $result = $this->eventsService->eventNameExists($eventName);
        $this->assertTrue($result);
    }

    public function testRegisterEventsListFunction()
    {
        $count = 3;
        $events = Event::factory()->count($count)->make();
        $result = $this->eventsService->registerEventsList($events->all());
        $this->assertEquals($result, $count);
    }
}
