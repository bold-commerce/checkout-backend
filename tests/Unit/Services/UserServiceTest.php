<?php

namespace Tests\Unit\Services;


use App\Exceptions\ApiCallExceptions\AddAuthenticatedCustomerApiCallException;
use App\Exceptions\ApiCallExceptions\CustomerInfosApiCallException;
use App\Exceptions\ApiCallExceptions\DeleteAuthenticatedCustomerApiCallException;
use App\Services\EndpointService;
use App\Services\ExperienceService;
use App\Services\UserService;
use GuzzleHttp\Exception\InvalidArgumentException;
use Mockery as M;
use Tests\TestCase;

class UserServiceTest extends TestCase
{

    protected ExperienceService $experienceServiceMock;
    protected EndpointService $endpointServiceMock;
    protected UserService $userService;

    protected array $apiReponseAddress = [
        'id' => '1',
        'first_name' => 'jon',
        'last_name' => 'smith',
        'company' => '',
        'street_1' => '50 Fultz',
        'street_2' => '',
        'city'=> 'winnipeg',
        'zip' => 'R3Y 0L6',
        'province' => 'manitoba',
        'province_code' => 'MB',
        'country' => 'canada',
        'country_iso2' => 'CA',
        'phone' => ''
    ];

    protected array $addressConvertedToAuthenticatedCustomer = [
        'first_name' => 'jon',
        'last_name' => 'smith',
        'email_address' => 'test@gmail.com',
        'platform_id' => 'test_id',
        'public_id' => '123',
        'saved_addresses' => [
            [
                'id' => '1',
                'first_name' => 'jon',
                'last_name' => 'smith',
                'company' => '',
                'business_name' => '',
                'address' => '50 Fultz',
                'address_line_1' => '50 Fultz',
                'address2' => '',
                'address_line_2' => '',
                'city' => 'winnipeg',
                'postal_code' => 'R3Y 0L6',
                'province' => 'manitoba',
                'province_code' => "MB",
                'country' => 'canada',
                'country_code' => 'CA',
                'phone' => '',
                'phone_number' => '',
                'default' => false,
            ],
        ],
    ];

    protected function setUp(): void
    {
        parent::setUp();
        $this->experienceServiceMock = M::mock(ExperienceService::class);
        $this->endpointServiceMock = M::mock(EndpointService::class);
        $this->userService = new UserService($this->endpointServiceMock, $this->experienceServiceMock);
    }

    public function testAddAuthenticatedUserSuccess(){

        $customer = [
            'addresses' => [$this->apiReponseAddress],
            'default_address' => [],
            'platform_id' => 'test_id',
            'email' => 'test@gmail.com',
            'first_name' => 'jon',
            'last_name' => 'smith',
            'id'=> '123'
        ];

        $customerInfoResponse = ['content' => ['customer' => $customer,]];

        $customerResponse = [
            'content' => [
                'data' => [
                    'customer' => $customer,
                    'application_state' => [
                        'customer' => $customer
                    ],
                ],
            ],
        ];
        $initializeOrderResponse = [
            'content' => ['data' => ['application_state' => [], 'initial_data' => [],],],
        ];
        $customerId = '3';

        $response = ['customer' => $customer];

        $this->endpointServiceMock->allows()->retrieveAuthenticatedCustomerInfos($customerId)->andReturns($customerInfoResponse);
        $this->endpointServiceMock->allows()->addAuthenticatedCustomer('', $this->addressConvertedToAuthenticatedCustomer )->andReturns($customerResponse);

        $result = $this->userService->addAuthenticatedUser($customerId, $initializeOrderResponse);
        $this->assertEquals($result, $response);

    }

    public function testAddAuthenticatedUserSuccessWithShippingRequired(){

        $customer = [
            'addresses' => [$this->apiReponseAddress],
            'default_address' => [],
            'platform_id' => 'test_id',
            'email' => 'test@gmail.com',
            'first_name' => 'jon',
            'last_name' => 'smith',
            'id'=> '123'
        ];
        $lineItem = ['product_data' => ['requires_shipping' => true]];

        $customerInfoResponse = ['content' => ['customer' => $customer,]];

        $customerResponse = [
            'content' => [
                'data' => [
                    'customer' => $customer,
                    'application_state' => [
                        'customer' => $customer
                    ],
                ],
            ],
        ];
        $initializeOrderResponse = [
            'content' => ['data' => [
                    'application_state' => [
                        'line_items' => [$lineItem]
                    ],
                    'initial_data' => [
                        'country_info' => [['iso_code' => 'CA' ]]
                    ],
                ],
            ],
        ];
        $customerId = '3';

        $response = ['customer' => $customer, 'line_items' => [$lineItem]];

        $this->endpointServiceMock->allows()->retrieveAuthenticatedCustomerInfos($customerId)->andReturns($customerInfoResponse);
        $this->endpointServiceMock->allows()->addAuthenticatedCustomer('', $this->addressConvertedToAuthenticatedCustomer )->andReturns($customerResponse);

        $result = $this->userService->addAuthenticatedUser($customerId, $initializeOrderResponse);
        $this->assertEquals($result, $response);

    }

    public function testAddAuthenticatedUserThrowsAuthenticatedCustomerGuzzleException(){

        $appState = ['customer' => []];
        $initializeOrderResponse = ['content' => ['data' => ['application_state' => $appState, 'initial_data' => [],],],];
        $customerId = '3';

        $exception = new InvalidArgumentException('Undefined array key "application_state"');

        $this->endpointServiceMock->allows()->retrieveAuthenticatedCustomerInfos($customerId)->andThrows($exception);

        $result = $this->userService->addAuthenticatedUser($customerId, $initializeOrderResponse);
        $this->assertEquals($result, $appState);

    }

    public function testAddAuthenticatedUserThrowsAuthenticatedCustomerCustomerInfosApiCallException(){

        $appState = ['customer' => []];
        $initializeOrderResponse = ['content' => ['data' => ['application_state' => $appState, 'initial_data' => [],],],];
        $customerId = '3';

        $exception = new CustomerInfosApiCallException('Undefined array key "application_state"');

        $this->endpointServiceMock->allows()->retrieveAuthenticatedCustomerInfos($customerId)->andThrows($exception);

        $result = $this->userService->addAuthenticatedUser($customerId, $initializeOrderResponse);
        $this->assertEquals($result, $appState);

    }

    public function testAddAuthenticatedUserThrowsDeleteCustomerGuzzleException(){

        $publicOrderId = 'test-id';
        $customer = [
            'addresses' => [$this->apiReponseAddress],
            'default_address' => [],
            'platform_id' => 'test_id',
            'email' => 'test@gmail.com',
            'first_name' => 'jon',
            'last_name' => 'smith',
            'id'=> '123'
        ];
        $appState = ['customer' => $customer];
        $customerInfoResponse = ['content' => ['customer' => $customer,]];

        $initializeOrderResponse = ['content' => ['data' => ['application_state' => $appState, 'initial_data' => [],'public_order_id' => $publicOrderId],],];
        $customerId = '3';

        $exception = new InvalidArgumentException('Undefined array key "application_state"');

        $this->endpointServiceMock->allows()->retrieveAuthenticatedCustomerInfos($customerId)->andReturns($customerInfoResponse);
        $this->endpointServiceMock->allows()->deleteCustomer($publicOrderId)->andThrows($exception);

        $result = $this->userService->addAuthenticatedUser($customerId, $initializeOrderResponse);
        $this->assertEquals($result, $appState);

    }

    public function testAddAuthenticatedUserThrowsDeleteAuthCustomerApiCallException(){

        $publicOrderId = 'test-id';
        $customer = [
            'addresses' => [$this->apiReponseAddress],
            'default_address' => [],
            'platform_id' => 'test_id',
            'email' => 'test@gmail.com',
            'first_name' => 'jon',
            'last_name' => 'smith',
            'id'=> '123'
        ];
        $appState = ['customer' => $customer];
        $customerInfoResponse = ['content' => ['customer' => $customer,]];

        $initializeOrderResponse = ['content' => ['data' => ['application_state' => $appState, 'initial_data' => [],'public_order_id' => $publicOrderId],],];
        $customerId = '3';

        $exception = new DeleteAuthenticatedCustomerApiCallException('Undefined array key "application_state"');

        $this->endpointServiceMock->allows()->retrieveAuthenticatedCustomerInfos($customerId)->andReturns($customerInfoResponse);
        $this->endpointServiceMock->allows()->deleteCustomer($publicOrderId)->andThrows($exception);

        $result = $this->userService->addAuthenticatedUser($customerId, $initializeOrderResponse);
        $this->assertEquals($result, $appState);

    }

    public function testAddAuthenticatedUserThrowsAddAuthCustomerApiCallException(){

        $publicOrderId = 'test-id';
        $customer = [
            'addresses' => [$this->apiReponseAddress],
            'default_address' => [],
            'platform_id' => 'test_id',
            'email' => 'test@gmail.com',
            'first_name' => 'jon',
            'last_name' => 'smith',
            'id'=> '123'
        ];
        $appState = ['customer' => $customer];
        $customerInfoResponse = ['content' => ['customer' => $customer,]];

        $initializeOrderResponse = ['content' => ['data' => ['application_state' => $appState, 'initial_data' => [],'public_order_id' => $publicOrderId],],];
        $customerId = '3';

        $exception = new DeleteAuthenticatedCustomerApiCallException('Undefined array key "application_state"');

        $this->endpointServiceMock->allows()->retrieveAuthenticatedCustomerInfos($customerId)->andReturns($customerInfoResponse);
        $this->endpointServiceMock->allows()->deleteCustomer($publicOrderId)->andThrows($exception);

        $result = $this->userService->addAuthenticatedUser($customerId, $initializeOrderResponse);
        $this->assertEquals($result, $appState);

    }

    public function testAddAuthenticatedUserThrowsGuzzleExceptionForAddAuthenticatedCustomer(){

        $publicOrderId = 'test-id';
        $customer = [
            'addresses' => [$this->apiReponseAddress],
            'default_address' => [],
            'platform_id' => 'test_id',
            'email' => 'test@gmail.com',
            'first_name' => 'jon',
            'last_name' => 'smith',
            'id'=> '123'
        ];
        $appState = ['customer' => $customer];
        $customerInfoResponse = ['content' => ['customer' => $customer,]];

        $initializeOrderResponse = ['content' => ['data' => ['application_state' => $appState, 'initial_data' => [],'public_order_id' => $publicOrderId],],];
        $customerId = '3';


        $exception = new InvalidArgumentException('Undefined array key "application_state"');

        $this->endpointServiceMock->allows()->retrieveAuthenticatedCustomerInfos($customerId)->andReturns($customerInfoResponse);
        $this->endpointServiceMock->allows()->deleteCustomer($publicOrderId)->andReturn([]);
        $this->endpointServiceMock->allows()->addAuthenticatedCustomer($publicOrderId, $this->addressConvertedToAuthenticatedCustomer)->andThrows($exception);

        $result = $this->userService->addAuthenticatedUser($customerId, $initializeOrderResponse);
        $this->assertEquals($result, $appState);

    }

    public function testAddAuthenticatedUserThrowsApiExceptionForAddAuthenticatedCustomer(){

        $publicOrderId = 'test-id';
        $customer = [
            'addresses' => [$this->apiReponseAddress],
            'default_address' => [],
            'platform_id' => 'test_id',
            'email' => 'test@gmail.com',
            'first_name' => 'jon',
            'last_name' => 'smith',
            'id'=> '123'
        ];
        $appState = ['customer' => $customer];
        $customerInfoResponse = ['content' => ['customer' => $customer,]];

        $initializeOrderResponse = ['content' => ['data' => ['application_state' => $appState, 'initial_data' => [],'public_order_id' => $publicOrderId],],];
        $customerId = '3';

        $exception = new AddAuthenticatedCustomerApiCallException('Undefined array key "application_state"');

        $this->endpointServiceMock->allows()->retrieveAuthenticatedCustomerInfos($customerId)->andReturns($customerInfoResponse);
        $this->endpointServiceMock->allows()->deleteCustomer($publicOrderId)->andReturn();
        $this->endpointServiceMock->allows()->addAuthenticatedCustomer($publicOrderId, $this->addressConvertedToAuthenticatedCustomer)->andThrows($exception);

        $result = $this->userService->addAuthenticatedUser($customerId, $initializeOrderResponse);
        $this->assertEquals($result, $appState);

    }

}
