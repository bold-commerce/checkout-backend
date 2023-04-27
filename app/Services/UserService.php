<?php

declare(strict_types=1);

namespace App\Services;

use App\Constants\Errors;
use App\Constants\Fields;
use App\Exceptions\ApiCallExceptions\AddAuthenticatedCustomerApiCallException;
use App\Exceptions\ApiCallExceptions\CustomerInfosApiCallException;
use App\Exceptions\ApiCallExceptions\DeleteAuthenticatedCustomerApiCallException;
use App\Facades\Logging;
use GuzzleHttp\Exception\GuzzleException;
use Illuminate\Support\Collection;

class UserService
{
    protected EndpointService $endpointService;
    protected ExperienceService $experienceService;
    protected ShopService $shopService;

    public function __construct(
        EndpointService $endpointService,
        ExperienceService $experienceService
    ) {
        $this->endpointService = $endpointService;
        $this->experienceService = $experienceService;
    }

    public function addAuthenticatedUser(string $customerID, array $initializeOrderResponse): array
    {
        $responseData = $initializeOrderResponse[Fields::CONTENT_IN_RESPONSE][Fields::DATA_IN_RESPONSE] ?? [];
        $lineItems = $responseData['application_state']['line_items'] ?? [];
        $countryInfos = $responseData['initial_data']['country_info'] ?? [];
        $publicOrderID = $responseData['public_order_id'] ?? '';

        try {
            $customerInfosResponse = $this->endpointService->retrieveAuthenticatedCustomerInfos($customerID);
            $customerInfos = $customerInfosResponse[Fields::CONTENT_IN_RESPONSE]['customer'] ?? [];
        } catch (GuzzleException $e) {
            Logging::exception(Errors::CUSTOMER_INFOS_API);

            return $responseData['application_state'];
        } catch (CustomerInfosApiCallException $e) {
            Logging::exception(sprintf('Error while retrieving customer ID %d', $customerID));

            return $responseData['application_state'];
        }

        $customerAddresses = $customerInfos['addresses'] ?? [];
        $customerDefaultAddress = $customerInfos['default_address'] ?? [];

        $isShippingRequired = $this->requiresShipping($lineItems);
        if ($isShippingRequired) {
            $allowedCountries = $this->retrieveAllowedShippingCountries($countryInfos);
            $addresses = $this->cleanSavedAddresses($allowedCountries, $customerAddresses, $customerDefaultAddress);
        } else {
            $addresses = $this->convertCustomerInfosAddresses($customerAddresses, $customerDefaultAddress);
        }

        $customer = $this->convertToAuthenticatedCustomer($customerInfos, $addresses);
        try {
            if ($this->hasCustomerSaved($responseData)) {
                $this->endpointService->deleteCustomer($publicOrderID);
            }
        } catch (GuzzleException $e) {
            Logging::exception(sprintf(Errors::DELETE_AUTHENTICATED_CUSTOMER_API));

            return $responseData['application_state'];
        } catch (DeleteAuthenticatedCustomerApiCallException $e) {
            Logging::exception('Error while deleting previous customer');

            return $responseData['application_state'];
        }

        try {
            $customerResponse = $this->endpointService->addAuthenticatedCustomer($publicOrderID, $customer);
        } catch (GuzzleException $e) {
            Logging::exception(Errors::ADD_AUTHENTICATED_CUSTOMER_API);

            return $responseData['application_state'];
        } catch (AddAuthenticatedCustomerApiCallException $e) {
            Logging::exception(sprintf('Error while adding auth customer ID %d into order %s', $customerID, $publicOrderID));

            return $responseData['application_state'];
        }

        $customerResponseData = $customerResponse[Fields::CONTENT_IN_RESPONSE][Fields::DATA_IN_RESPONSE] ?? [];

        return array_merge($responseData['application_state'], $customerResponseData['application_state']);
    }

    protected function convertToAuthenticatedCustomer(array $customerInfos, Collection $addresses): array
    {
        return [
            'first_name' => $customerInfos['first_name'] ?? '',
            'last_name' => $customerInfos['last_name'] ?? '',
            'email_address' => $customerInfos['email'] ?? '',
            'platform_id' => $customerInfos['platform_id'] ?? '',
            'public_id' => (string) ($customerInfos['id'] ?? ''),
            'saved_addresses' => $addresses->toArray(),
        ];
    }

    protected function retrieveAllowedShippingCountries(array $countriesList): Collection
    {
        $countries = [];
        foreach ($countriesList as $country) {
            $countries[] = $country['iso_code'];
        }

        return collect($countries);
    }

    protected function cleanSavedAddresses(Collection $allowedCountries, array $customerAddresses, array $defaultAddress = []): Collection
    {
        $unfilteredAddresses = collect($customerAddresses);
        $addresses = $unfilteredAddresses->filter(function ($item) use ($allowedCountries) {
            return $allowedCountries->contains($item['country_iso2']);
        });

        return $this->convertCustomerInfosAddresses($addresses, $customerAddresses, $defaultAddress);
    }

    protected function requiresShipping(array $lineItems): bool
    {
        $requiresShipping = false;
        foreach ($lineItems as $lineItem) {
            if (!empty($lineItem['product_data']['requires_shipping'])) {
                $requiresShipping = true;
                break;
            }
        }

        return $requiresShipping;
    }

    /**
     * Convert addresses to both App State and Endpoint values.
     */
    protected function convertCustomerInfosAddresses(Collection|array $addresses, array $defaultAddress = []): Collection
    {

        $formattedAddresses = [];
        foreach ($addresses as $address) {
            $formattedAddresses[] = [
                'id' => $address['id'],
                'first_name' => $address['first_name'],
                'last_name' => $address['last_name'],
                'company' => $address['company'],
                'business_name' => $address['company'],
                'address' => $address['street_1'],
                'address_line_1' => $address['street_1'],
                'address2' => $address['street_2'],
                'address_line_2' => $address['street_2'],
                'city' => $address['city'],
                'postal_code' => $address['zip'],
                'province' => $address['province'],
                'province_code' => $address['province_code'],
                'country' => $address['country'],
                'country_code' => $address['country_iso2'],
                'phone' => $address['phone'],
                'phone_number' => $address['phone'],
                'default' => $address === $defaultAddress,
            ];
        }
        return collect($formattedAddresses);
    }

    protected function hasCustomerSaved(array $responseData): bool
    {
        return !empty($responseData['application_state']['customer']['platform_id'])
            || !empty($responseData['application_state']['customer']['public_id']);
    }
}
