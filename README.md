# RH Dependency SDK
Private Dependency

## Installation
This package is installed using Composer. You can use the following command to add it to a project.

```bash
composer require acl-solution/rh-dependency
composer update
```

## WHMCS Usage
This API client encapsulates the WHMCS with a simple OO wrapper. 

First, you need to create a client instance with the details of your WHMCS installation:
```php
$api = new \ACL\RH\Dependency\Provider('https://example.com/whmcs/installation/url/', 'myusername', 'mypassword');
```
Note the trailing `/` in the URL. The username and password are the credentials of a user with the "API Access" permission. You can use your main admin user for this, but for security it's recommended to create a special API user for every project.

After creating the client, you can start to send a request. The methods correspond to the action names from [the WHMCS API](https://developers.whmcs.com/api/api-index/), the other attributes can be submitted as an array.

For example, to execute the "AcceptOrder" action, you could use the following code.
```php
try {
    $result = $api->acceptOrder([
        'orderid' => 123,
        'serverid' => 456,
        //...
    ]);
} catch (\HansAdema\WhmcsSdk\RequestException $e) {
    echo "Error connecting to WHMCS: ".$e->getMessage();
} catch (\HansAdema\WhmcsSdk\ResponseException $e) {
    echo "There was an issue with your API call: ".$e->getMessage();
}
```

Note that two different types of exceptions are being used here. The `RequestException` is used whenever there is a problem connecting to your WHMCS installation, for example because the installation is down or the credentials are not correct. The `ResponseException` is thrown whenever the API result was not successful, for example due to missing or invalid method parameters.

#### Quick Start

```php
require_once __DIR__ . '/vendor/autoload.php';

use ipinfo\ipinfo\IPinfo;

$access_token = '123456789abc';
$client = new IPinfo($access_token);
$ip_address = '216.239.36.21';
$details = $client->getDetails($ip_address);

$details->city; // Emeryville
$details->loc; // 37.8342,-122.2900
```

### Usage

The `IPinfo->getDetails()` method accepts an IP address as an optional, positional argument. If no IP address is specified, the API will return data for the IP address from which it receives the request.

```php
$client = new IPinfo();
$ip_address = '216.239.36.21';
$details = $client->getDetails($ip_address);
$details->city; // Emeryville
$details->loc; // 37.8342,-122.2900
```

### Authentication

The IPinfo library can be authenticated with your IPinfo API token, which is passed in as a positional argument. It also works without an authentication token, but in a more limited capacity.

```php
$access_token = '123456789abc';
$client = new IPinfo($access_token);
```

### Details Data

`IPinfo->getDetails()` will return a `Details` object that contains all fields listed [IPinfo developer docs](https://ipinfo.io/developers/responses#full-response) with a few minor additions. Properties can be accessed directly.

```php
$details->hostname; // cpe-104-175-221-247.socal.res.rr.com
```

#### Country Name

``Details->country_name`` will return the country name, as supplied by the ``countries.json`` file. See below for instructions on changing that file for use with non-English languages. ``Details->country`` will still return country code.

```php
$details->country; // US
$details->country_name; // United States
```

#### Longitude and Latitude

``Details->latitude`` and ``Details->longitude`` will return latitude and longitude, respectively, as strings. ``Details->loc`` will still return a composite string of both values.

```php
$details->loc; // 34.0293,-118.3570
$details->latitude; // 34.0293
$details->longitude; // -118.3570
```

#### Accessing all properties

``Details->all`` will return all details data as a dictionary.

```php
$details->all;
/*
    {
    'asn': {  'asn': 'AS20001',
               'domain': 'twcable.com',
               'name': 'Time Warner Cable Internet LLC',
               'route': '104.172.0.0/14',
               'type': 'isp'},
    'city': 'Los Angeles',
    'company': {   'domain': 'twcable.com',
                   'name': 'Time Warner Cable Internet LLC',
                   'type': 'isp'},
    'country': 'US',
    'country_name': 'United States',
    'hostname': 'cpe-104-175-221-247.socal.res.rr.com',
    'ip': '104.175.221.247',
    'loc': '34.0293,-118.3570',
    'latitude': '34.0293',
    'longitude': '-118.3570',
    'phone': '323',
    'postal': '90016',
    'region': 'California'
    }
*/
```

### Caching

In-memory caching of `Details` data is provided by default via the [sabre/cache](https://github.com/sabre-io/cache/) library. LRU (least recently used) cache-invalidation functionality has been added to the default TTL (time to live). This means that values will be cached for the specified duration; if the cache's max size is reached, cache values will be invalidated as necessary, starting with the oldest cached value.

#### Modifying cache options

Default cache TTL and maximum size can be changed by setting values in the `$settings` argument array.

* Default maximum cache size: 4096 (multiples of 2 are recommended to increase efficiency)
* Default TTL: 24 hours (in seconds)

```php
$access_token = '123456789abc';
$settings = ['cache_maxsize' => 30, 'cache_ttl' => 128];
$client = new IPinfo($access_token, $settings);
```

#### Using a different cache

It's possible to use a custom cache by creating a child class of the [CacheInterface](https://github.com/ipinfo/php/blob/master/src/cache/Interface.php) class and passing this into the handler object with the `cache` keyword argument. FYI this is known as [the Strategy Pattern](https://sourcemaking.com/design_patterns/strategy).

```php
$access_token = '123456789abc';
$settings = ['cache' => $my_fancy_custom_cache];
$client = new IPinfo($access_token, $settings);
```

#### Disabling the cache

You may disable the cache by passing in a `cache_disabled` key in the settings:

```php
$access_token = '123456789abc';
$settings = ['cache_disabled' => true];
$client = new IPinfo($access_token, $settings);
```

### Overriding HTTP Client options

The IPinfo client constructor accepts a `timeout` key which is the request
timeout in seconds.

For full flexibility, a `guzzle_opts` key is accepted which accepts an
associative array which is described in [Guzzle Request Options](https://docs.guzzlephp.org/en/stable/request-options.html).
Options set here will override any custom settings set by the IPinfo client
internally in case of conflict, including headers.

### Batch Operations

Looking up a single IP at a time can be slow. It could be done concurrently from the client side, but IPinfo supports a batch endpoint to allow you to group together IPs and let us handle retrieving details for them in bulk for you.

```php
$access_token = '123456789abc';
$client = new IPinfo($access_token);
$ips = ['1.1.1.1', '8.8.8.8', '1.2.3.4/country'];
$results = $client->getBatchDetails($ips);
echo $results['1.2.3.4/country']; // AU
var_dump($results['1.1.1.1']);
var_dump($results['8.8.8.8']);
```

The input size is not limited, as the interface will chunk operations for you behind the scenes.

Please see [the official documentation](https://ipinfo.io/developers/batch) for more information and limitations.

### Internationalization

When looking up an IP address, the response object includes a `Details->country_name` attribute which includes the country name based on American English. It is possible to return the country name in other languages by setting the `countries_file` keyword argument when creating the `IPinfo` object.

The file must be a `.json` file with the following structure:

```JSON
{
 "BD": "Bangladesh",
 "BE": "Belgium",
 "BF": "Burkina Faso",
 "BG": "Bulgaria"
 ...
}
```