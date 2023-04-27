<?php

declare(strict_types=1);

namespace App\Constants;

class Errors
{
    // API Calls fail
    public const ERROR_GUZZLE_CLIENT_FAIL = 'Error while calling API';

    // Function Parameters
    public const EMPTY_PARAMETERS_LIST = 'Parameters List empty';
    public const EMPTY_PARAMETER = 'Parameter %s cannot be empty';
    public const INVALID_PARAMETERS = 'Parameters incorrect';
    public const MANDATORY_PARAMETERS_REQUIREMENT_NOT_MET = 'At least one mandatory parameter is empty or undefined';

    // Generic error messages
    public const INVALID_ID = '%s with ID %d not found';
    public const INVALID_NAME = '%s with Name `%s` not found';
    public const NOT_FOUND = '%s not found';

    // Assets Service error messages
    public const ASSET_POSITION_NOT_FOUND = 'Asset with Position %d not found';
    public const ASSET_TYPE_NOT_FOUND = 'Asset with Type `%s` not found';
    public const ASSET_POSITION_AND_TYPE_NOT_FOUND = 'Asset with Type `%s` and position %d not found';
    public const CHILDREN_ASSET_NOT_FOUND = 'No Children Asset found for Parent Asset ID %d';

    // Endpoint Service error messages
    public const INITIALIZE_ORDER_API = 'Error(s) while calling INIT v2 endpoint';
    public const INITIALIZE_SHOPIFY_ADMIN_ORDER_API = 'Error(s) while calling INIT v2 endpoint with Shopify Order from Checkout Admin';
    public const RESUME_ORDER_API = 'Error(s) while calling RESUME v2 endpoint';
    public const SHOP_INFOS_API = 'Error(s) while calling SHOP INFOS v2 endpoint';
    public const CUSTOMER_INFOS_API = 'Error(s) while calling CUSTOMER INFOS v2 endpoint';
    public const ADD_AUTHENTICATED_CUSTOMER_API = 'Error(s) while calling ADD AUTHENTICATED CUSTOMER v2 endpoint';
    public const DELETE_AUTHENTICATED_CUSTOMER_API = 'Error(s) while calling DELETE AUTHENTICATED CUSTOMER v2 endpoint';

    // Experience Service error messages
    public const INVALID_PLATFORM = 'Platform %s was not found or not supported';

    // ShopApiToken Service error messages
    public const INVALID_SHOP_OR_TOKEN_VERIFICATION = 'Invalid Shop or Token';
    public const EMPTY_TOKEN = 'Token is empty';
    public const ERROR_ENCRYPT_TOKEN = 'Token cannot be encrypted! Try again.';

    // ShopAssets Service error messages
    public const UNDEFINED_TEMPLATE = 'No template defined for Shop ID %d';
    public const TEMPLATE_NOT_EXISTING = 'Template with ID %d does not exist';
    public const ASSET_EMPTY = 'Asset ID/Name provided is empty';
    public const INVALID_ASSET = 'Asset ID/Name provided is incorrect';

    // Shop Service error messages
    public const INVALID_DOMAIN = 'Shop with Domain `%s` not found';
    public const INVALID_IDENTIFIER = 'Shop with Identifier `%s` not found';
    public const INVALID_DOMAIN_IDENTIFIER = 'Shop with Domain or Identifier `%s` not found or too many results found';

    public const EMPTY_SHOP_INFOS = 'Shop Information\s empty';

    // ShopUrl Service error messages
    public const UNDEFINED_URLS = 'No URIs defined for Shop ID %d';

    // Frontend Shop Model
    public const CONSTRUCTION_ERROR = 'Error while building FrontendShop object';

    public const EMPTY_ENVIRONMENT_PROPERTIES = "App environment properties are missing";

    public const SEND_API_ERROR = "Error(s) while sending api request";
}
