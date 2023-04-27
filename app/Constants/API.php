<?php

declare(strict_types=1);

namespace App\Constants;

class API
{
    public const HEADER_AUTHORIZATION = 'authorization';
    public const HEADER_AUTHORIZATION_VALUE_BEARER = 'Bearer ';
    public const HEADER_CONTENT_TYPE = 'Content-Type';
    public const HEADER_CONTENT_TYPE_VALUE_JSON = 'application/json';

    public const ENV_PRODUCTION = 'production';
    public const ENV_STAGING = 'staging';

    public const API_URL_PRODUCTION = 'https://api.boldcommerce.com';
    public const API_URL_STAGING = 'https://api.staging.boldcommerce.com';
    public const API_URL_LOCAL = 'https://cashier-jake.bold.ninja/api/v2';
    public const API_V2_SCOPES = array('read_activity_logs',
    'read_customers',
    'read_discount_codes',
    'read_orders',
    'read_price_order_conditions',
    'read_price_rulesets',
    'read_products',
    'read_shop',
    'read_shop_settings',
    'read_subscription_groups',
    'read_subscriptions',
    'read_webhooks',
    'write_activity_logs',
    'write_customers',
    'write_discount_codes',
    'write_orders',
    'write_payments',
    'write_price_order_conditions',
    'write_price_rulesets',
    'write_products',
    'write_shop_settings',
    'write_subscription_groups',
    'write_subscriptions',
    'write_webhooks');

    public const API_V2_AUTH_DASH_URL_STAGING = "https://apps.staging.boldapps.net/accounts/dashboard/authorize";
    public const API_V2_AUTH_DASH_URL_PRODUCTION = "https://apps.boldapps.net/accounts/dashboard/authorize";
}
