<?php

namespace Tests\Unit\Models;

use App\Models\Event;
use Carbon\Carbon;
use Mockery as M;
use Tests\TestCase;

class EventModelTest extends TestCase
{
    public function setUp(): void
    {
        parent::setUp();
    }

    public function testStaticFunctionGetFormat()
    {
        $asset = Event::getFormat();
        $this->assertEquals('Y-m-d H:i:s.u', $asset);
    }

    /** @dataProvider getterMethodsDataProvider */
    public function testGetters($factoryData, $getter, $expected) {
        $event = Event::factory()->make($factoryData);
        $result = $event->$getter();
        $this->assertEquals($expected, $result);
    }

    public function testGetContextGetterAsJSON() {
        $event = Event::factory()->make(['context' => json_encode($this->getMockContext())]);
        $context = $event->getContext();
        $this->assertEquals(json_encode($this->getMockContext()), $context);
    }

    public function testGetContextGetterAsArray() {
        $event = Event::factory()->make(['context' => json_encode($this->getMockContext())]);
        $context = $event->getContext(false);
        $this->assertEquals($this->getMockContext(), $context);
    }

    public function testSetEventDatetimeWithError() {
        Carbon::setTestNow(Carbon::create(2016, 01, 02, 8, 16, 24));

        $carbonMock = M::mock(Carbon::class);
        $event = new Event();
        $carbonMock->shouldReceive('createFromFormat')->andThrow(new \Exception());
        $event->setEventDateTime('aacdvbdregre');
        $this->assertEquals(Carbon::now(), $event->getEventDateTime());

    }

    /** @dataProvider setterMethodsDataProvider */
    public function testSetters($expected, $setter, $setterParameter) {
        $getter = str_replace('set', 'get', $setter);
        $event = new Event();
        $event->$setter($setterParameter);
        $this->assertEquals($expected, $event->$getter());
    }

    /** @dataProvider createEventFromDataProvider */
    public function testCreateEventFromDataDataProvider($shopID, $eventName, $dateTime, $context, $publicOrderID, $expected) {
        $event = new Event();
        $event->createEventFromData($shopID, $eventName, $dateTime, $context, $publicOrderID);
        $this->assertEquals($expected, $event->toArray());
    }

    /*
     * int $shopID, string $eventName, Carbon|string $dateTime, array|string|null $context, string $publicOrderID
     */
    private function createEventFromDataProvider(): array {
        return [
            'dateTime is string - Context is null' => [
                111,
                'First Event',
                '2021-10-12 11:13:15.171921',
                null,
                'publicORDERid1',
                [
                    'shop_id' => 111,
                    'event_name' => 'First Event',
                    'event_date_time' => '2021-10-12 11:13:15.171921',
                    'context' => '',
                    'public_order_id' => 'publicORDERid1',
                ]
            ],
            'dateTime is Carbon - Context is null' => [
                222,
                'Second Event',
                Carbon::createFromFormat('Y-m-d H:i:s.u', '2022-04-08 12:14:16.182022'),
                null,
                'publicORDERid2',
                [
                    'shop_id' => 222,
                    'event_name' => 'Second Event',
                    'event_date_time' => '2022-04-08 12:14:16.182022',
                    'context' => '',
                    'public_order_id' => 'publicORDERid2',
                ]
            ],
            'Context is array - Datetime is string' => [
                333,
                'Third Event',
                '2018-01-03 08:09:10.111213',
                $this->getMockContext(),
                'publicORDERid3',
                [
                    'shop_id' => 333,
                    'event_name' => 'Third Event',
                    'event_date_time' => '2018-01-03 08:09:10.111213',
                    'context' => json_encode($this->getMockContext()),
                    'public_order_id' => 'publicORDERid3',
                ]
            ],
            'Context is array - Datetime is Carbon' => [
                555,
                'Fifth Event',
                Carbon::createFromFormat('Y-m-d H:i:s.u', '2014-05-18 01:02:13.040506'),
                $this->getMockContext(),
                'publicORDERid5',
                [
                    'shop_id' => 555,
                    'event_name' => 'Fifth Event',
                    'event_date_time' => '2014-05-18 01:02:13.040506',
                    'context' => json_encode($this->getMockContext()),
                    'public_order_id' => 'publicORDERid5',
                ]
            ],
            'Context is string - Datetime is string' => [
                444,
                'Fourth Event',
                '1985-11-20 09:40:00.123456',
                json_encode($this->getMockContext()),
                'publicORDERid4',
                [
                    'shop_id' => 444,
                    'event_name' => 'Fourth Event',
                    'event_date_time' => '1985-11-20 09:40:00.123456',
                    'context' => json_encode($this->getMockContext()),
                    'public_order_id' => 'publicORDERid4',
                ]
            ],
            'Context is string - Datetime is Carbon' => [
                666,
                'Sixth Event',
                Carbon::createFromFormat('Y-m-d H:i:s.u', '1998-07-13 22:48:04.123456'),
                json_encode($this->getMockContext()),
                'publicORDERid6',
                [
                    'shop_id' => 666,
                    'event_name' => 'Sixth Event',
                    'event_date_time' => '1998-07-13 22:48:04.123456',
                    'context' => json_encode($this->getMockContext()),
                    'public_order_id' => 'publicORDERid6',
                ]
            ],
        ];
    }

    private function getterMethodsDataProvider(): array {
        return [
            'getID getter' => [
                ['id' => 123],
                'getID',
                123,
            ],
            'getShopID getter' => [
                ['shop_id' => 456],
                'getShopID',
                456,
            ],
            'getEventDateTime getter' => [
                ['event_date_time' => '2022-06-22 10:20:30.405060'],
                'getEventDateTime',
                Carbon::createFromFormat('Y-m-d H:i:s.u', '2022-06-22 10:20:30.405060'),
            ],
            'getEventName getter' => [
                ['event_name' => 'Some Event'],
                'getEventName',
                'Some Event',
            ],
            'getPublic OrderID getter' => [
                ['public_order_id' => '1234abcdefgh5678'],
                'getPublicOrderID',
                '1234abcdefgh5678',
            ],
        ];
    }

    private function setterMethodsDataProvider(): array {
        return [
            'setDateTime setter - Carbon type' => [
                Carbon::createFromFormat('Y-m-d H:i:s.u', '2022-06-22 10:20:30.405060'),
                'setEventDateTime',
                Carbon::createFromFormat('Y-m-d H:i:s.u', '2022-06-22 10:20:30.405060'),
            ],
            'setDateTime setter - string type' => [
                Carbon::createFromFormat('Y-m-d H:i:s.u', '2021-04-18 07:14:21.283542'),
                'setEventDateTime',
                '2021-04-18 07:14:21.283542',
            ],
            'setEventName setter' => [
                'Some Event',
                'setEventName',
                'Some Event',
            ],
            'setPublicOrderID setter' => [
                '1234abcdefgh5678',
                'setPublicOrderID',
                '1234abcdefgh5678',
            ],
            'setContext setter' => [
                json_encode($this->getMockContext()),
                'setContext',
                $this->getMockContext(),
            ],
        ];
    }

    private function getMockContext(): array {
        return [
            'event_name' => 'some Event Name',
            'event_time' => 'Some Event Time',
            'layer1' => [
                'layer2' => 'some data',
            ],
        ];
    }
}
