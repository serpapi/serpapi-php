# SerpApi PHP Library

[![Packagist Version](https://img.shields.io/packagist/v/serpapi/serpapi-php.svg?label=packagist)](https://packagist.org/packages/serpapi/serpapi-php)
[![PHP](https://img.shields.io/badge/php-%3E%3D7.2-brightgreen.svg)](https://www.php.net)
[![License: MIT](https://img.shields.io/badge/license-MIT-blue.svg)](https://github.com/serpapi/serpapi-php/blob/master/MIT-LICENSE.txt)

Integrate search data into your PHP application. This library is the official wrapper for [SerpApi](https://serpapi.com).

SerpApi supports Google, Google Maps, Google Shopping, Baidu, Yandex, Yahoo, eBay, App Stores, and [more](https://serpapi.com).

This is the new library provided by SerpApi as a replacement for our old library that can be found [here](https://github.com/serpapi/google-search-results-php). Feel free to contact us if you need any help: contact@serpapi.com

[The full documentation is available here.](https://serpapi.com/search-api)

The following services are provided:
 * [Search API](https://serpapi.com/search-api)
 * [Location API](https://serpapi.com/locations-api)
 * [Search Archive API](https://serpapi.com/search-archive-api)
 * [Account API](https://serpapi.com/account-api)

SerpApi provides a [script builder](https://serpapi.com/playground) to get you started quickly.

## Installation

PHP 7.2+ with `ext-curl` and `ext-json` must be installed along with the [Composer](https://getcomposer.org/) dependency management tool.

Tested PHP versions:
* 7.2
* 7.3
* 7.4
* 8.0
* 8.1
* 8.2
* 8.3
* 8.4
* 8.5

Package available from [packagist](https://packagist.org/packages/serpapi/serpapi-php).

## Quick start

```bash
composer require serpapi/serpapi-php
```

## Simple Usage

```php
require 'vendor/autoload.php';

use SerpApi\Client;

$client = new Client(getenv('SERPAPI_KEY'));
$results = $client->search([
  'q' => 'coffee',
]);

print_r($results->organic_results);
```

This example runs a search for "coffee" on Google. It returns the results as a PHP object decoded from JSON.

See the [playground](https://serpapi.com/playground) to generate your own code.

## Response formats

Use `search` for structured results decoded into a PHP object:

```php
$results = $client->search(['q' => 'coffee']);
```

Use `md` for a token-efficient Markdown string optimized for LLMs and AI agents:

```php
$markdown = $client->md(['q' => 'coffee']);
```

Use `html` when you need the raw search-engine response:

```php
$html = $client->html(['q' => 'coffee']);
```

Archived results are also available as Markdown with `$client->searchArchive($searchId, 'md')`.

Learn more about [SerpApi Markdown output](https://serpapi.com/markdown-output).

## Configuration

### API key

The API key can be set in the constructor or later via `setApiKey`:

```php
use SerpApi\Client;

// via constructor
$client = new Client('Your Private Key');

// or later
$client = new Client();
$client->setApiKey('Your Private Key');
```

Get your API key from [serpapi.com/dashboard](https://serpapi.com/dashboard).

### Engine

The default engine is `google`. You can change it via the second constructor parameter:

```php
use SerpApi\Client;

$client = new Client(getenv('SERPAPI_KEY'), 'bing');
$results = $client->search(['q' => 'coffee']);
```

### Timeout

The default request timeout is 120 seconds. Customize it via the third constructor parameter:

```php
use SerpApi\Client;

$client = new Client(getenv('SERPAPI_KEY'), 'google', 30);
```

## Search API

### Search Google
```php
use SerpApi\Client;

$client = new Client(getenv('SERPAPI_KEY'));
$results = $client->search([
  'engine' => 'google',
  'tbm' => 'isch',
  'q' => 'coffee',
]);

print_r($results->images_results);
```

 * source: [tests/ExampleSearchGoogleTest.php](https://github.com/serpapi/serpapi-php/blob/master/tests/ExampleSearchGoogleTest.php)
see: [https://serpapi.com/search-api](https://serpapi.com/search-api)

### Search Google Scholar
```php
use SerpApi\Client;

$client = new Client(getenv('SERPAPI_KEY'));
$results = $client->search([
  'engine' => 'google_scholar',
  'q' => 'coffee',
]);

print_r($results->organic_results);
```

 * source: [tests/ExampleSearchGoogleScholarTest.php](https://github.com/serpapi/serpapi-php/blob/master/tests/ExampleSearchGoogleScholarTest.php)
see: [https://serpapi.com/google-scholar-api](https://serpapi.com/google-scholar-api)

### Search Google Autocomplete
```php
use SerpApi\Client;

$client = new Client(getenv('SERPAPI_KEY'));
$results = $client->search([
  'engine' => 'google_autocomplete',
  'q' => 'coffee',
]);

print_r($results->organic_results);
```

 * source: [tests/ExampleSearchGoogleAutocompleteTest.php](https://github.com/serpapi/serpapi-php/blob/master/tests/ExampleSearchGoogleAutocompleteTest.php)
see: [https://serpapi.com/google-autocomplete-api](https://serpapi.com/google-autocomplete-api)

### Search Google Shopping
```php
use SerpApi\Client;

$client = new Client(getenv('SERPAPI_KEY'));
$results = $client->search([
  'engine' => 'google_shopping',
  'q' => 'coffee',
]);

print_r($results->organic_results);
```

 * source: [tests/ExampleSearchGoogleShoppingTest.php](https://github.com/serpapi/serpapi-php/blob/master/tests/ExampleSearchGoogleShoppingTest.php)
see: [https://serpapi.com/google-shopping-api](https://serpapi.com/google-shopping-api)

### Search Google Maps
```php
use SerpApi\Client;

$client = new Client(getenv('SERPAPI_KEY'));
$results = $client->search([
  'engine' => 'google_maps',
  'q' => 'pizza',
  'll' => '@40.7455096,-74.0083012,15.1z',
  'type' => 'search',
]);

print_r($results->local_results);
```

 * source: [tests/ExampleSearchGoogleMapsTest.php](https://github.com/serpapi/serpapi-php/blob/master/tests/ExampleSearchGoogleMapsTest.php)
see: [https://serpapi.com/google-maps-api](https://serpapi.com/google-maps-api)

### Search Google Jobs
```php
use SerpApi\Client;

$client = new Client(getenv('SERPAPI_KEY'));
$results = $client->search([
  'engine' => 'google_jobs',
  'q' => 'coffee',
]);

print_r($results->jobs_results);
```

 * source: [tests/ExampleSearchGoogleJobsTest.php](https://github.com/serpapi/serpapi-php/blob/master/tests/ExampleSearchGoogleJobsTest.php)
see: [https://serpapi.com/google-jobs-api](https://serpapi.com/google-jobs-api)

### Search Google Events
```php
use SerpApi\Client;

$client = new Client(getenv('SERPAPI_KEY'));
$results = $client->search([
  'engine' => 'google_events',
  'q' => 'Events in Austin',
  'location' => 'Austin, Texas, United States',
]);

print_r($results->organic_results);
```

 * source: [tests/ExampleSearchGoogleEventsTest.php](https://github.com/serpapi/serpapi-php/blob/master/tests/ExampleSearchGoogleEventsTest.php)
see: [https://serpapi.com/google-events-api](https://serpapi.com/google-events-api)

### Search Google Lens
```php
use SerpApi\Client;

$client = new Client(getenv('SERPAPI_KEY'));
$results = $client->search([
  'engine' => 'google_lens',
  'url' => 'https://i.imgur.com/5bGzZi7.jpg',
  'gl' => 'us',
  'hl' => 'en',
]);

print_r($results->visual_matches);
```

 * source: [tests/ExampleSearchGoogleLensTest.php](https://github.com/serpapi/serpapi-php/blob/master/tests/ExampleSearchGoogleLensTest.php)
see: [https://serpapi.com/google-lens-api](https://serpapi.com/google-lens-api)

### Search Google Play
```php
use SerpApi\Client;

$client = new Client(getenv('SERPAPI_KEY'));
$results = $client->search([
  'engine' => 'google_play',
  'q' => 'kite',
  'store' => 'apps',
]);

print_r($results->organic_results);
```

 * source: [tests/ExampleSearchGooglePlayTest.php](https://github.com/serpapi/serpapi-php/blob/master/tests/ExampleSearchGooglePlayTest.php)
see: [https://serpapi.com/google-play-api](https://serpapi.com/google-play-api)

### Search Google Local Services
```php
use SerpApi\Client;

$client = new Client(getenv('SERPAPI_KEY'));
$results = $client->search([
  'engine' => 'google_local_services',
  'q' => 'electrician',
  'data_cid' => '6745062158417646970',
]);

print_r($results->local_ads);
```

 * source: [tests/ExampleSearchGoogleLocalServicesTest.php](https://github.com/serpapi/serpapi-php/blob/master/tests/ExampleSearchGoogleLocalServicesTest.php)
see: [https://serpapi.com/google-local-services-api](https://serpapi.com/google-local-services-api)

### Search Bing
```php
use SerpApi\Client;

$client = new Client(getenv('SERPAPI_KEY'));
$results = $client->search([
  'engine' => 'bing',
  'q' => 'coffee',
]);

print_r($results->organic_results);
```

 * source: [tests/ExampleSearchBingTest.php](https://github.com/serpapi/serpapi-php/blob/master/tests/ExampleSearchBingTest.php)
see: [https://serpapi.com/bing-search-api](https://serpapi.com/bing-search-api)

### Search Baidu
```php
use SerpApi\Client;

$client = new Client(getenv('SERPAPI_KEY'));
$results = $client->search([
  'engine' => 'baidu',
  'q' => 'coffee',
]);

print_r($results->organic_results);
```

 * source: [tests/ExampleSearchBaiduTest.php](https://github.com/serpapi/serpapi-php/blob/master/tests/ExampleSearchBaiduTest.php)
see: [https://serpapi.com/baidu-search-api](https://serpapi.com/baidu-search-api)

### Search Yahoo
```php
use SerpApi\Client;

$client = new Client(getenv('SERPAPI_KEY'));
$results = $client->search([
  'engine' => 'yahoo',
  'p' => 'coffee',
]);

print_r($results->organic_results);
```

 * source: [tests/ExampleSearchYahooTest.php](https://github.com/serpapi/serpapi-php/blob/master/tests/ExampleSearchYahooTest.php)
see: [https://serpapi.com/yahoo-search-api](https://serpapi.com/yahoo-search-api)

### Search YouTube
```php
use SerpApi\Client;

$client = new Client(getenv('SERPAPI_KEY'));
$results = $client->search([
  'engine' => 'youtube',
  'search_query' => 'coffee',
]);

print_r($results->video_results);
```

 * source: [tests/ExampleSearchYoutubeTest.php](https://github.com/serpapi/serpapi-php/blob/master/tests/ExampleSearchYoutubeTest.php)
see: [https://serpapi.com/youtube-search-api](https://serpapi.com/youtube-search-api)

### Search Walmart
```php
use SerpApi\Client;

$client = new Client(getenv('SERPAPI_KEY'));
$results = $client->search([
  'engine' => 'walmart',
  'query' => 'coffee',
]);

print_r($results->organic_results);
```

 * source: [tests/ExampleSearchWalmartTest.php](https://github.com/serpapi/serpapi-php/blob/master/tests/ExampleSearchWalmartTest.php)
see: [https://serpapi.com/walmart-search-api](https://serpapi.com/walmart-search-api)

### Search eBay
```php
use SerpApi\Client;

$client = new Client(getenv('SERPAPI_KEY'));
$results = $client->search([
  'engine' => 'ebay',
  '_nkw' => 'water',
]);

print_r($results->organic_results);
```

 * source: [tests/ExampleSearchEbayTest.php](https://github.com/serpapi/serpapi-php/blob/master/tests/ExampleSearchEbayTest.php)
see: [https://serpapi.com/ebay-search-api](https://serpapi.com/ebay-search-api)

### Search Naver
```php
use SerpApi\Client;

$client = new Client(getenv('SERPAPI_KEY'));
$results = $client->search([
  'engine' => 'naver',
  'query' => 'coffee',
]);

print_r($results->ads_results);
```

 * source: [tests/ExampleSearchNaverTest.php](https://github.com/serpapi/serpapi-php/blob/master/tests/ExampleSearchNaverTest.php)
see: [https://serpapi.com/naver-search-api](https://serpapi.com/naver-search-api)

### Search Home Depot
```php
use SerpApi\Client;

$client = new Client(getenv('SERPAPI_KEY'));
$results = $client->search([
  'engine' => 'home_depot',
  'q' => 'table',
]);

print_r($results->products);
```

 * source: [tests/ExampleSearchHomeDepotTest.php](https://github.com/serpapi/serpapi-php/blob/master/tests/ExampleSearchHomeDepotTest.php)
see: [https://serpapi.com/home-depot-search-api](https://serpapi.com/home-depot-search-api)

### Search Apple App Store
```php
use SerpApi\Client;

$client = new Client(getenv('SERPAPI_KEY'));
$results = $client->search([
  'engine' => 'apple_app_store',
  'term' => 'coffee',
]);

print_r($results->organic_results);
```

 * source: [tests/ExampleSearchAppleAppStoreTest.php](https://github.com/serpapi/serpapi-php/blob/master/tests/ExampleSearchAppleAppStoreTest.php)
see: [https://serpapi.com/apple-app-store](https://serpapi.com/apple-app-store)

### Search DuckDuckGo
```php
use SerpApi\Client;

$client = new Client(getenv('SERPAPI_KEY'));
$results = $client->search([
  'engine' => 'duckduckgo',
  'q' => 'coffee',
]);

print_r($results->organic_results);
```

 * source: [tests/ExampleSearchDuckduckgoTest.php](https://github.com/serpapi/serpapi-php/blob/master/tests/ExampleSearchDuckduckgoTest.php)
see: [https://serpapi.com/duckduckgo-search-api](https://serpapi.com/duckduckgo-search-api)

## APIs supported

### Location API

```php
use SerpApi\Client;

$client = new Client(getenv('SERPAPI_KEY'));
$locations = $client->location(['q' => 'Austin', 'limit' => 3]);

echo "Number of locations: " . count($locations) . "\n";
print_r($locations);
```

NOTE: `api_key` is not required for this endpoint.

### Search Archive API

First, run a search and save the search ID:

```php
use SerpApi\Client;

$client = new Client(getenv('SERPAPI_KEY'));
$results = $client->search([
  'q' => 'Coffee',
  'location' => 'Austin, Texas',
]);
$search_id = $results->search_metadata->id;
```

Now retrieve the previous search from the archive (free of charge):

```php
$archived = $client->searchArchive($search_id);
print_r($archived);

$markdown = $client->searchArchive($search_id, 'md');
echo $markdown;
```

### Account API

```php
use SerpApi\Client;

$client = new Client(getenv('SERPAPI_KEY'));
$account = $client->account();
print_r($account);
```

### HTML results

```php
use SerpApi\Client;

$client = new Client(getenv('SERPAPI_KEY'));
$html = $client->html(['q' => 'Coffee']);

echo strlen($html) . " bytes of HTML\n";
```

## Error handling

`SerpApiException` includes structured context for HTTP and API errors (status code, endpoint, search params, search id).

```php
use SerpApi\Client;
use SerpApi\SerpApiException;

try {
  $client = new Client('invalid_key');
  $client->search(['q' => 'test']);
} catch (SerpApiException $exception) {
  echo $exception->getMessage() . "\n";
  // HTTP request failed with status: 401 error: Invalid API key... from url: https://serpapi.com/search

  echo $exception->getSerpApiError() . "\n";
  echo $exception->getResponseStatus() . "\n";
  echo $exception->getSearchId() . "\n";
  print_r($exception->getSearchParams());
  print_r($exception->toArray());
}
```

## Testing

We love "true open source", "continuous integration", and Test Driven Development (TDD).
We use PHPUnit to test our infrastructure around the clock using [GitHub Actions](https://github.com/serpapi/serpapi-php/actions/workflows/serpapi-php.yml) to achieve the best QoS (Quality of Service).

The `tests/` directory includes specifications that serve the dual purpose of examples and functional tests.

Set your secret API key in your shell before running tests:

```bash
export SERPAPI_KEY="your_secret_key"
```

Install dependencies and run the test suite:

```bash
make install
make test
```

Contributions are welcome. Feel free to submit a pull request!

## Change log

 * 1.0 - First stable version

## Conclusion

SerpApi supports all the major search engines. Google has more advanced support, with all the major services available: Images, News, Shopping, and more...

[The full documentation is available here.](https://serpapi.com/search-api)

Authors: Victor Benarbia victor@serpapi.com, Alaa Abdulridha alaa@serpapi.com
For more information: https://serpapi.com

## License

[MIT](MIT-LICENSE.txt)
