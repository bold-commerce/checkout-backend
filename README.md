# Checkout Experience Backend

## Description

The Checkout Experience Backend project is a reference implementation that allows merchants to connect to Bold Checkout APIs with their custom checkout user interfaces.

It provides the following capabilities:

* Initialize and resume Bold Checkout orders.
* Serve the base HTML pages that will host your checkout user interface. These are ready-populated with all the necessary data, script, and stylesheet tags that a checkout user interface will need.
* Serve multiple stores from a single instance.

## Prerequisites

In order to run this project, you will need:

1. php@8.1 with Laravel 9.
2. Composer
3. A MySQL database server.
4. A store on a [supported commerce platform](https://developer.boldcommerce.com/default/guides/getting-started/supported-platforms-gateways), with Bold Checkout installed.
5. A [Bold Account Center account](https://developer.boldcommerce.com/default/guides/getting-started/public-integrations#bold-account-center-account), associated with your store.
6. A [Bold Developer Portal account](https://developer.boldcommerce.com/default/guides/getting-started/public-integrations#bold-developer-dashboard-account). This will be used to generate the OAuth Credentials required for checkout-experience-back-end to communicate with Bold Checkout.
7. A publicly-accessible URL where your server will be hosted, referred to as `<YOUR_PUBLIC_SERVER_URL>` in the rest of this documentation.

## Installation

### Clone the project

   * Clone and install the dependencies:
      ```sh
      git clone git@git-lab.boldapps.net:bold-checkout/checkout-experience-back-end.git
      cd checkout-experience-back-end
      composer install
      ```
   
   * Create a `.env` file from the sample file:
      ```sh
      cp .env.example .env
      ```
     
   * Generate key by running following command:
      ```
      ./artisan key:generate
      ```
     
   * Update the `.env` file and add following values:
      ```
      CHECKOUT_API_PATH=checkout
      CHECKOUT_API_ENVIRONMENT=https://api.boldcommerce.com
      APP_URL=<YOUR_PUBLIC_SERVER_URL>
      ```

### Create the Database

The checkout-experience-back-end requires a MySQL server for persistence.

* Connect to the MySQL server.
* Create a new database.
* In the `.env` file, enter the following information:
  * `DB_DATABASE` - The database name. 
  * `DB_USERNAME` - The database username. 
  * `DB_PASSWORD` - The database password.
  * `DB_HOST` - The database host name. 
  * `DB_PORT` - The database port.
* Run `./artisan migrate` from the project's root directory to configured the newly created database.

### Create Your Bold Commerce Application Credentials

* Navigate to [Developer Dashboard](https://developer.boldcommerce.com/default/dashboard) and log in. Create an account if necessary. 
* Once logged in, create your [API credentials](https://developer.boldcommerce.com/default/guides/getting-started/public-integrations#create-client-credentials).
* Name the set of credentials. Enter `<YOUR_PUBLIC_SERVER_URL>/api/authorize` in the `Redirect_uris` field.
* Once the credentials are created, copy the following values into your `.env` file:
  * `client_id` into the `DEVELOPER_CLIENT_ID` variable. 
  * `client_secret` into the `DEVELOPER_CLIENT_SECRET` variable. 
  * `redirect_uris` into the `DEVELOPER_REDIRECT_URL` variable without quotes or brackets.

### Start Your Server

* Run `./artisan serve` from the root directory of the project. 
* If the project is running locally, it will also need to expose the server to the public internet using a tunnel tool of your choice (ngrok, [cloudflare tunnels](https://developers.cloudflare.com/cloudflare-one/connections/connect-apps/install-and-setup/tunnel-guide/local/#set-up-a-tunnel-locally-cli-setup), etc.).

### Install the Server on Your Bold Checkout Shop

* In a browser, navigate to `<YOUR_PUBLIC_SERVER_URL>/install`.
* Click **install**. This redirects to [Bold Account Center](https://apps.boldapps.net/accounts/login/selection).
* Login to your Bold Account Center account.
* If the account has multiple stores, select the store you wish to install the server on and click **continue**. Otherwise, continue to the next step.
* In the prompt to install Bold Checkout on the store, review the permissions and click **approve**. If successfully installed, you should see a JSON response beginning with `{"message":"Shop install successful"...}`.

### Override Your Storefront’s Default Checkout

Storefront override is platform specific, which means that every ecommerce platform has a unique implementation.

We have provided a sample BigCommerce example, as well as general best practices to redirect the store's default checkout to the overriding Bold Checkout.

#### BigCommerce Example
Please follow the steps below to redirect the BigCommerce checkout to the new checkout override:

* Login to the store's BigCommerce admin portal.
* Go to Storefront -> Script Manager and create a new script with following values:
  * Name of script - The unique script name.
  * Description - A brief description.
  * Location - Select `Head` option.
  * Select pages where script will be added - Select `Store pages` option.
  * Script category - Select `Essential` option.
  * Script Type - Select `Script` option.
  * Script Content - Script Content - An example script can be found at `resources/assets/bigcommerce/installation.js`. Update `CUSTOM_CHECKOUT_INIT_URL` to match with your `<YOUR_PUBLIC_SERVER_URL>`.

Once done, you can test by clicking on the BigCommerce checkout button. It should be redirecting to the new Checkout backend override.

#### General Approach

This general approach will help you create a script depending on the platform.

1. First, construct a URL that points to `<YOUR_PUBLIC_SERVER_URL>/experience/init/<SHOP_DOMAIN>`. 
   * Replace `<SHOP_DOMAIN>` with the domain used when installing Bold Checkout on your store.
   * Include the appropriate query parameters (see below). 
2. Update your storefront’s checkout buttons to use this new URL. The exact method to do this will depend on your platform and storefront implementation.


There are two main variations of query parameters that are supported:

* The **initialization** variant creates a new order for the specified cart. This is typically desired for your storefront’s cart page or mini-cart:

  | Parameter             | Type              | Description                                                                                                                                                                                                                                                                                                                         |
  |-----------------------|-------------------|-------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------|
  | `cart_id`             | string            | The ID of the cart on your commerce platform. The exact structure of this string will vary by platform.                                                                                                                                                                                                                             |
  | `return_url`          | string            | Configures where any “return-to-cart/store” links on the checkout will point to. **Note:** If this is excluded, these links will be broken.                                                                                                                                                                                         |
  | `checkout_local_time` | number (optional) | A tool for tracking performance metrics. If included, it should be the current unix timestamp.                                                                                                                                                                                                                                      |
  | `customer_id`         | string (optional) | The ID of the customer on your commerce platform. Including this will cause checkout to be initialized as the user, allowing access to their saved customer information (addresses, payment methods, etc.). **Note:** This project does not verify any customer information. All the authentication should be done on the platform. |
  | `user_access_token`     | string (optional) | The access token for authenticated user. Optional on most platforms right now but required if user is authenticated.                                                                                                                                                                                                                                                                                                |

* The **resume** variant resumes an order that is already in progress. This variant is useful for custom communication (e.g. a custom abandoned cart email with a link to resume an order.)

  | Parameter             | Type              | Description                                                                                                                                                                                                                                                                                                                         |
  |-----------------------|-------------------|-------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------|
  | `public_order_id`     | string            | The public order ID of the Bold Checkout order you wish to resume.                                                                                                                                                                                                                                                                  |
  | `return_url`          | string            | Configures where any “return-to-cart/store” links on the checkout will point to. **Note:** If this is excluded, these links will be broken.                                                                                                                                                                                         |
  | `checkout_local_time` | number (optional) | A tool for tracking performance metrics. If included, it should be the current unix timestamp.                                                                                                                                                                                                                                      |
  | `customer_id`         | string (optional) | The ID of the customer on your commerce platform. Including this will cause checkout to be initialized as the user, allowing access to their saved customer information (addresses, payment methods, etc.). **Note:** This project does not verify any customer information. All the authentication should be done on the platform. |
  | `user_access_token`     | string (optional) | The access token for authenticated user. Optional on most platforms right now but required if user is authenticated.                                                                                                                                                                                                                                                                                               |

