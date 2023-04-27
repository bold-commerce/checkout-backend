<?php

namespace Tests\Unit\Models;

use App\Libraries\CheckoutCollection;
use App\Models\ApiResponseContent;
use Tests\TestCase;

class ApiResponseContentModelTest extends TestCase
{
    public function testConstructor() {
        $apiResponseContent = new ApiResponseContent();
        $this->assertEmpty($apiResponseContent->getApiResponseContent());
    }

    public function testSetter() {
        $apiResponseContent = new ApiResponseContent();
        $apiResponseContent->setApiResponseContent(['key' => 'value']);
        $this->assertEquals(new CheckoutCollection(['key' => 'value']), $apiResponseContent->getApiResponseContent());
    }

    public function testSetApplicationState() {
        $apiResponseContent = new ApiResponseContent();
        $apiResponseContent->setApplicationState(['key' => 'value']);
        $this->assertEquals(collect(['key' => 'value']), $apiResponseContent->getApplicationState());
    }

    public function testGetContent() {
        $apiResponseContent = new ApiResponseContent();
        $apiResponseContent->setApiResponseContent(['content' => ['key' => 'value']]);
        $this->assertEquals(['key' => 'value'], $apiResponseContent->getContent());
    }

    public function testGetData() {
        $apiResponseContent = new ApiResponseContent();
        $apiResponseContent->setApiResponseContent(['content' => ['data' => ['key' => 'value']]]);
        $this->assertEquals(collect(['key' => 'value']), $apiResponseContent->getData());
    }

    public function testGetPublicOrderIDExists() {
        $apiResponseContent = new ApiResponseContent();
        $apiResponseContent->setApiResponseContent(['content' => ['data' => ['public_order_id' => '1234orderID5678']]]);
        $this->assertEquals('1234orderID5678', $apiResponseContent->getPublicOrderID());
    }

    public function testGetPublicOrderIDNotExists() {
        $apiResponseContent = new ApiResponseContent();
        $apiResponseContent->setApiResponseContent(['content' => ['data' => ['key' => 'value']]]);
        $this->assertEquals('', $apiResponseContent->getPublicOrderID());
    }

    public function testGetApplicationStateExists() {
        $apiResponseContent = new ApiResponseContent();
        $apiResponseContent->setApiResponseContent(['content' => ['data' => ['application_state' => ['key' => 'value']]]]);
        $this->assertEquals(collect(['key' => 'value']), $apiResponseContent->getApplicationState());
    }

    public function testGetApplicationStateNotExists() {
        $apiResponseContent = new ApiResponseContent();
        $apiResponseContent->setApiResponseContent(['content' => ['data' => ['any_key' => ['key' => 'value']]]]);
        $this->assertEquals(collect([]), $apiResponseContent->getApplicationState());
    }

    public function testGetInitialDataExists() {
        $apiResponseContent = new ApiResponseContent();
        $apiResponseContent->setApiResponseContent(['content' => ['data' => ['initial_data' => ['key' => 'value']]]]);
        $this->assertEquals(collect(['key' => 'value']), $apiResponseContent->getInitialData());
    }

    public function testGetInitialDataNotExists() {
        $apiResponseContent = new ApiResponseContent();
        $apiResponseContent->setApiResponseContent(['content' => ['data' => ['any_key' => ['key' => 'value']]]]);
        $this->assertEquals(collect([]), $apiResponseContent->getInitialData());
    }

    public function testGetJwtTokenExists() {
        $apiResponseContent = new ApiResponseContent();
        $apiResponseContent->setApiResponseContent(['content' => ['data' => ['jwt_token' => 'someTOKEN']]]);
        $this->assertEquals('someTOKEN', $apiResponseContent->getJwtToken());
    }

    public function testGetJwtTokenNotExists() {
        $apiResponseContent = new ApiResponseContent();
        $apiResponseContent->setApiResponseContent(['content' => ['data' => ['key' => 'value']]]);
        $this->assertEquals('', $apiResponseContent->getJwtToken());
    }

    public function testGetFieldFromApplicationState() {
        $apiResponseContent = new ApiResponseContent();
        $apiResponseContent->setApiResponseContent(['content' => ['data' => ['application_state' => ['key1' => 'value1']]]]);
        $this->assertEquals('value1', $apiResponseContent->getFieldFromApplicationState('key1'));
        $this->assertEmpty($apiResponseContent->getFieldFromApplicationState('not_existing_key'));
    }

    public function testCleanPiiFromResponse() {
        $apiResponseContent = new ApiResponseContent();
        $apiResponseContent->setApiResponseContent([
            'content' => [
                'data' => [
                    'application_state' => [
                        'addresses' => [
                            'shipping' => [
                                'address' => fake()->address(),
                                'city' => fake()->city(),
                            ],
                            'billing' => [
                                'address' => fake()->address(),
                                'city' => fake()->city(),
                            ],
                        ],
                        'customer' => [
                            'first_name' => fake()->firstName,
                            'last_name' => fake()->lastName,
                        ]
                    ],
                ],
            ],
        ]);
        $this->assertNotEmpty($apiResponseContent->getFieldFromApplicationState('addresses'));
        $this->assertNotEmpty($apiResponseContent->getFieldFromApplicationState('customer'));
        $apiResponseContent->cleanPiiFromResponse();
        $this->assertEmpty($apiResponseContent->getFieldFromApplicationState('addresses'));
        $this->assertEmpty($apiResponseContent->getFieldFromApplicationState('customer'));
    }

    public function testCleanJwtFromResponse() {
        $fakeToken = fake()->text(64);
        $apiResponseContent = new ApiResponseContent();
        $apiResponseContent->setApiResponseContent([
            'content' => [
                'data' => [
                    'jwt_token' => $fakeToken,
                ],
            ],
        ]);
        $this->assertEquals($fakeToken, $apiResponseContent->getJwtToken());
        $apiResponseContent->cleanJwtFromResponse();
        $this->assertEquals('invalid', $apiResponseContent->getJwtToken());
    }

    /** @dataProvider isOrderProcessedDataProvider */
    public function testIsOrderProcessed($appState, $expected) {
        $apiResponseContent = new ApiResponseContent();
        $apiResponseContent->setApiResponseContent($appState);
        $this->assertEquals($expected, $apiResponseContent->isOrderProcessed());
    }

    private function isOrderProcessedDataProvider(): array {
        return [
            'is_processed not existing' => [
                ['content' => ['data' => ['application_state' => ['key1' => 'value1']]]],
                false,
            ],
            'is_processed false' => [
                ['content' => ['data' => ['application_state' => ['is_processed' => false]]]],
                false,
            ],
            'is_processed true' => [
                ['content' => ['data' => ['application_state' => ['is_processed' => true]]]],
                true,
            ],
        ];
    }
}