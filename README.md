# `superoffice-webapi`

[![Latest Stable Version](https://poser.pugx.org/roydejong/superoffice-webapi/version)](https://packagist.org/packages/roydejong/superoffice-webapi)
[![Travis CI Build](https://travis-ci.org/roydejong/superoffice-webapi-php-sdk.svg?branch=master)](https://travis-ci.org/github/roydejong/superoffice-webapi-php-sdk)

***Unofficial PHP SDK for SuperOffice Web API***

This library provides a PHP SDK for the SuperOffice [REST WebAPI](https://community.superoffice.com/documentation/sdk/SO.NetServer.Web.Services/html/Reference-WebAPI-REST-REST.htm).

> ⚠ **Note: This library is a work-in-progress, and only targets CRM Online (SuperOffice Cloud).**

## Installation
The recommended way to install this library is with [Composer](http://getcomposer.org/), by adding the [`superoffice-webapi`](https://packagist.org/packages/roydejong/superoffice-webapi) package as a dependency to your application:

    composer require roydejong/superoffice-webapi
    
## Configuration

You will need to be registered as a SuperOffice developer, and you must have a registered app to receive the necessary client credentials.

### Initializing

When initializing the client, you must pass a `Config` object:

```php
<?php

use roydejong\SoWebApi\Client;
use roydejong\SoWebApi\Config;

$config = new Config();
$config->environment = "sod";
$config->tenantId = "Cust12345";
// ...

$client = new Client($config);
```

You can also set the configuration values by array:

```php
<?php

new Config([
    'environment' => "sod",
    'tenantId' => "Cust12345"
    // ...
]);
```

### Options
Available configuration options:

|Key|Type|Required?|Description|
|---|----|--------|-----------|
|`environment`|`string`|Yes|SuperOffice environment (`sod`, `stage` or `online`).|
|`tenantId`|`string`|Yes|Customer / Context ID, usually in the format `Cust12345`.|
|`clientId`|`string`|*For OAuth*|Client ID (Application ID).|
|`clientSecret`|`string`|*For OAuth*|Client secret (Application Token).|
|`redirectUri`|`string`|*For OAuth*|OAuth callback URL. Must exactly match a redirect URI registered with SuperOffice.|
|`privateKey`|`string`|No|Private key for system user token signing (`<RSAKeyValue>` block).|

## Authentication (OAuth / SuperId)
If you are targeting Online CRM, you must use OAuth to aquire a `BEARER` access token for the web api.

Local installations must use `BASIC` / `SOTICKET` authentication methods (currently not supported by this library).

### Redirect user to authorization screen
After setting your configuration, you can ask the client to generate the OAuth authorization URL:

```php
<?php 

use roydejong\SoWebApi\Client;

$client = new Client(/* $config */);
$redirectUrl = $client->getOAuthAuthorizationUrl("optional_state");
````

This will generate a redirect URL like `https://env-name.superoffice.com/login/common/oauth/authorize?client_id=...`.

When you redirect the user to this URL, they will be asked to authorize your application and grant access to their account.

### Request access token 
Once the user authorizes your app, you will receive a callback request on your configured `requestUri`.

You can can exchange the `code` parameter in the request for an access token:

```php
<?php

$tokenResponse = $client->requestOAuthAccessToken($_GET['code']);
```

The `TokenResponse` object contains the following keys:

|Key|Type|Description|
|---|----|-----------|
|`token_type`|`string`|Should be set to `Bearer`.|
|`access_token`|`string`|The actual access token.|
|`expires_in`|`int`|The lifetime in seconds of the access token.|
|`refresh_token`|`string`|Can be used to generate access tokens, as long as the user hasn't revoked application access.|
|`id_token`|`string`|JSON Web Token (JWT), can be used to verify that the tokens came from the real SuperId server.|

Your application is responsible for storing these tokens.

### Refresh access token
You can use the `refresh_token` to generate new access tokens, as long as the user hasn't revoked your application's access:

 
```php
<?php

$tokenResponse = $client->refreshOAuthAccessToken($tokenResponse->refresh_token);
```

This response will be a `TokenResponse` object, but with `refresh_token` set to `null`.

### Configure access token
You must explicitly set the access token you want to use with the client before performing any requests:

```php
<?php

use roydejong\SoWebApi\Client;

// Optionally pass it directly in the client constructor:
$client = new Client(/* $config */, $tokenResponse->access_token);

// Or set it on an existing client instance:
$client->setAccessToken($tokenResponse->access_token);
``` 

## Tenant status check
You can perform a [tenant status check](https://community.superoffice.com/en/developer/create-apps/how-to/develop/check-tenant-status/) to retrieve information about the customer's environment. You can use this to determine whether the environment is available or not, and get a load-balanced target URL for your API requests. 

> "Each tenant has a status page where you can check its state to ensure your application remains stable and responds accordingly."

```php
<?php

use roydejong\SoWebApi\Client;

// Authentication is not required, but "tenantId" must be set in your config.
$client = new Client(/* $config */);
$tenantStatus = $client->getTenantStatus();

// If the target environment is offline, do not proceed:
if (!$tenantStatus->IsRunning) die("Tenant offline!");

// Tenant status gives a load-balanced base URL you can set on the client:
$client->setBaseUrl($tenantStatus->Endpoint);
```

The `TenantStatus` object contains the following keys: 

|Key|Type|Description|
|---|----|-----------|
|`ContextIdentifier`|`string`|Customer instance id, typically formatted like "Cust12345".|
|`Endpoint`|`string`|The load-balanced base URL of the customer installation.|
|`State`|`string`|Tenant status. Should be "Running" under normal conditions.|
|`IsRunning`|`bool`|This indicates whether the tenant is up and running.|
|`ValidUntil`|`DateTime`|When to check next time if an updated state is needed.|