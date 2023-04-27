<?php

declare(strict_types=1);

namespace App\Constants;

class Paths
{
    public const API_PATH = 'checkout';

    public const INIT_PATH = 'init';
    public const RESUME_PATH = 'resume';

    public const SHOP_INFOS_PATH = 'shops/v1/info';
    public const CUSTOMER_INFOS_PATH = 'customers/v2/shops/%s/customers/pid/%s';

    public const CHECKOUT_STOREFRONT_PATH = 'storefront';
    public const CUSTOMER_PATH = 'customer';
    public const ADD_AUTHENTICATED_USER_PATH = self::CUSTOMER_PATH.'/authenticated';
    public const DELETE_SHIPPING_ADDRESS_PATH = 'addresses/shipping';
    public const DELETE_BILLING_ADDRESS_PATH = 'addresses/billing';
    public const ORDERS_PATH = 'orders';

    // FRONTEND TEMPLATE PATHS
    public const SHIPPING_PATH = 'shipping_lines';
    public const THANK_YOU_PATH = 'thank_you';
    public const PAYMENT_PATH = 'payment';
    public const SESSION_EXPIRED_PATH = 'session_expired';
    public const OUT_OF_STOCK_PATH = 'out_of_stock';

    // API RESPONSE MODEL PATHS
    public const CONTENT_PATH = 'content';
    public const DATA_PATH = self::CONTENT_PATH.'.data';
    public const APP_STATE_PATH = self::DATA_PATH.'.application_state';
    public const INITIAL_DATA_PATH = self::DATA_PATH.'.initial_data';
    public const PUBLIC_ORDER_ID_PATH = self::DATA_PATH.'.public_order_id';
    public const JWT_TOKEN_PATH = self::DATA_PATH.'.jwt_token';
    public const APP_STATE_CUSTOMER_PATH = self::APP_STATE_PATH.'.customer';
}
