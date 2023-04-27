<?php

namespace Tests\Mocks;

// use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ApiMockData extends TestCase
{
    /**
     * A basic test example.
     *
     * @return void
     */
    public const SHOPS_V1_INFO = array(
            'id' => 123,
            'shop_domain' => 'store-123456.mybigcommerce.com',
            'custom_domain' => 'jake-123456.bolddemos.ninja',
            'shop_identifier' => '123456',
            'shop_owner' => 'Jake Smith',
            'city' => null,
            'province' => null,
            'country' => 'Canada',
            'country_code' => 'CA',
            'address' => '',
            'address_2' => null,
            'postal_code' => null,
            'store_name' => 'jake-123456-store',
            'admin_email' => 'test@someemail.com',
            'order_email' => 'info@someemail.com',
            'currency' => 'USD',
            'currency_symbol' => '$',
            'money_format' => '${{amount}}',
            'phone' => '',
            'plan_name' => 'Partner Sandbox',
            'plan_level' => 'Sandbox Store',
            'platform_status' => '',
            'is_price_entered_with_tax' => false,
            'weight_unit' => 'kg',
            'created_at' => '2022-02-22T23:15:59Z',
            'updated_at' => '2022-12-13T18:30:42Z',
            'redacted_at' => null,
            'sync_status' => '',
            'setup_required' => false,
            'password_enabled' => false,
            'pre_launch_enabled' => false,
            'has_storefront' => false,
            'timezone' => 'Europe/London',
            'organization_id' => 1258,
            'platform_slug' => 'bigcommerce',
            'base_url' => null,
            'api_base_url' => null,
            'auth_base_url' => null,
            'tax_rounding_mode' => null,
            'inventory_control' => null,
            'currency_precision' => null,
            'locale' => 'en',
            'shop_enabled' => true,
            'has_ac_shop_platform_creds' => false,
            'platform_updated_at' => null
           );
}
