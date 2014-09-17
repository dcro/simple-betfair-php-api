Simple Betfair PHP API
======================

This is a simple PHP implementation for [Betfair's API (API-NG)](https://api.developer.betfair.com/services/webapps/docs/display/1smk3cen4v3lu3yomq5qye0ni/API-NG+Overview). It handles both authentication against the SSO endpoint & requests against the JSON-RPC endpoint.


Installation
------------

You can either get the files from GIT or you can install the library via [Composer](getcomposer.org). To use Composer, simply add the following to your `composer.json` file.

```json
{
    "require": {
        "dcro/simple-betfair-php-api": "dev-master"
    }
}
```

How to use it?
--------------

To initialize the API, you'll need to pass an array with your application key (`appKey`), `username`, `password` and certificate file (`cert`). Check out the Betfair API documentation for details on how to get these.

```php
// Set the API configuration
$configuration = array(
    'appKey'   => '<betfair-application-key>',
    'username' => '<betfair-api-username>',
    'password' => '<betfair-api-password>',
    'cert'     => '/path/to/your/certificate.pem',
)

$api = Betfair\SimpleAPI($configuration);
```

To make a request against the API endpoint, call the `request()` method like this:

```php
try {
    // List event types
    $response = $api->request('listEventTypes', '{"filter":{}}');

} catch (Exception $ex) {
    // handle the exception
}
```

A more complex request example, to get the market catalogue for a specific event type (`<your-event-type-id>`) from in Great Britain and for the `WIN` market.

```php
try {
    // Define the parameters
    $params = '{
                   "filter" : {
                       "eventTypeIds" : ["<your-event-type-id>"],
                       "marketCountries" : ["GB"],
                       "marketTypeCodes" : ["WIN"],
                       "marketStartTime" : {"from":"' . date('c') . '"}
                   },
                   "sort" : "FIRST_TO_START",
                   "maxResults" : "1000",
                   "marketProjection" : ["EVENT","MARKET_START_TIME","RUNNER_DESCRIPTION","MARKET_DESCRIPTION"]
              }';

    // Get the market catalogue
    $response = $api->request('listMarketCatalogue', $params);

} catch (Exception $ex) {
    // handle the exception
}
```