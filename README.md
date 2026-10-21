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
 * [Image API](https://serpapi.com/image-api)

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

## Simple usage

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
echo $results->organic_results[0]->title;
```

Use `searchArray` for the same results as an associative array:

```php
$results = $client->searchArray(['q' => 'coffee']);
echo $results['organic_results'][0]['title'];
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

Or for a single request:

```php
$results = $client->search(['q' => 'coffee', 'timeout' => 10]);
```

### Default search parameters

The constructor also accepts a single array. `api_key`, `engine`, `timeout`, and `persistent` configure the client; every other key becomes a default search parameter sent with every request:

```php
use SerpApi\Client;

$client = new Client([
  'api_key' => getenv('SERPAPI_KEY'),
  'engine' => 'google',
  'hl' => 'en',
  'gl' => 'us',
]);

// q=coffee, hl=en, gl=us
$results = $client->search(['q' => 'coffee']);

// Per-call parameters take precedence: q=coffee, hl=fr, gl=us
$results = $client->search(['q' => 'coffee', 'hl' => 'fr']);

// null drops a default for one call: q=coffee, hl=en
$results = $client->search(['q' => 'coffee', 'gl' => null]);
```

With positional arguments, pass default search parameters fourth: `new Client($apiKey, 'google', 30, ['hl' => 'en'])`.

### Persistent connections

The client keeps its connection to SerpApi open and reuses it, so only the first request pays for the DNS lookup and TLS handshake. The connection closes when the client is destroyed, or explicitly with `close()`:

```php
use SerpApi\Client;

$client = new Client(getenv('SERPAPI_KEY'));
$client->search(['q' => 'coffee']);
$client->search(['q' => 'tea']); // reuses the connection
$client->close();
```

A closed client reconnects on its next request. To open a new connection for every request instead, pass `'persistent' => false` in the configuration array. [`demo/demo_persistent.php`](demo/demo_persistent.php) compares the two.

### Debugging

`inspect()`, `var_dump()`, and `print_r()` mask the API key:

```php
echo $client->inspect();
// #<SerpApi\Client @engine=google @timeout=120 @persistent=true @api_key=abcd****wxyz>
```

`var_export()` bypasses the mask and prints the key in clear text.

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

print_r($results->suggestions);
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

print_r($results->shopping_results);
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

print_r($results->events_results);
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

To search with a local image instead of a URL, upload it with the [Image API](#image-api).

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

### Search Google Images
```php
use SerpApi\Client;

$client = new Client(getenv('SERPAPI_KEY'));
$results = $client->search([
  'engine' => 'google_images',
  'q' => 'coffee',
]);

print_r($results->images_results);
```

 * source: [tests/ExampleSearchGoogleImagesTest.php](https://github.com/serpapi/serpapi-php/blob/master/tests/ExampleSearchGoogleImagesTest.php)
see: [https://serpapi.com/google-images-api](https://serpapi.com/google-images-api)

### Search Google Images Light
```php
use SerpApi\Client;

$client = new Client(getenv('SERPAPI_KEY'));
$results = $client->search([
  'engine' => 'google_images_light',
  'q' => 'Coffee',
]);

print_r($results->images_results);
```

 * source: [tests/ExampleSearchGoogleImagesLightTest.php](https://github.com/serpapi/serpapi-php/blob/master/tests/ExampleSearchGoogleImagesLightTest.php)
see: [https://serpapi.com/google-images-light-api](https://serpapi.com/google-images-light-api)

### Search Google News
```php
use SerpApi\Client;

$client = new Client(getenv('SERPAPI_KEY'));
$results = $client->search([
  'engine' => 'google_news',
  'q' => 'pizza',
  'gl' => 'us',
  'hl' => 'en',
]);

print_r($results->news_results);
```

 * source: [tests/ExampleSearchGoogleNewsTest.php](https://github.com/serpapi/serpapi-php/blob/master/tests/ExampleSearchGoogleNewsTest.php)
see: [https://serpapi.com/google-news-api](https://serpapi.com/google-news-api)

### Search Google News Light
```php
use SerpApi\Client;

$client = new Client(getenv('SERPAPI_KEY'));
$results = $client->search([
  'engine' => 'google_news_light',
  'q' => 'pizza',
]);

print_r($results->news_results);
```

 * source: [tests/ExampleSearchGoogleNewsLightTest.php](https://github.com/serpapi/serpapi-php/blob/master/tests/ExampleSearchGoogleNewsLightTest.php)
see: [https://serpapi.com/google-news-light-api](https://serpapi.com/google-news-light-api)

### Search Google Videos
```php
use SerpApi\Client;

$client = new Client(getenv('SERPAPI_KEY'));
$results = $client->search([
  'engine' => 'google_videos',
  'q' => 'coffee',
]);

print_r($results->video_results);
```

 * source: [tests/ExampleSearchGoogleVideosTest.php](https://github.com/serpapi/serpapi-php/blob/master/tests/ExampleSearchGoogleVideosTest.php)
see: [https://serpapi.com/google-videos-api](https://serpapi.com/google-videos-api)

### Search Google Light
```php
use SerpApi\Client;

$client = new Client(getenv('SERPAPI_KEY'));
$results = $client->search([
  'engine' => 'google_light',
  'q' => 'coffee',
]);

print_r($results->organic_results);
```

 * source: [tests/ExampleSearchGoogleLightTest.php](https://github.com/serpapi/serpapi-php/blob/master/tests/ExampleSearchGoogleLightTest.php)
see: [https://serpapi.com/google-light-api](https://serpapi.com/google-light-api)

### Search Google AI Mode
```php
use SerpApi\Client;

$client = new Client(getenv('SERPAPI_KEY'));
$results = $client->search([
  'engine' => 'google_ai_mode',
  'q' => 'best coffee maker',
]);

print_r($results->reconstructed_markdown);
```

 * source: [tests/ExampleSearchGoogleAiModeTest.php](https://github.com/serpapi/serpapi-php/blob/master/tests/ExampleSearchGoogleAiModeTest.php)
see: [https://serpapi.com/google-ai-mode-api](https://serpapi.com/google-ai-mode-api)

### Search Google AI Overview

The AI Overview API takes a `page_token` from a Google search. Google shows most AI Overviews inline, so the token is only present for some queries, and it expires a minute after the search:

```php
use SerpApi\Client;

$client = new Client(getenv('SERPAPI_KEY'));
$search = $client->search(['q' => 'how does art work in android']);

if (isset($search->ai_overview->page_token)) {
  $results = $client->search([
    'engine' => 'google_ai_overview',
    'page_token' => $search->ai_overview->page_token,
  ]);

  print_r($results->ai_overview);
}
```

 * source: [tests/ExampleSearchGoogleAiOverviewTest.php](https://github.com/serpapi/serpapi-php/blob/master/tests/ExampleSearchGoogleAiOverviewTest.php)
see: [https://serpapi.com/google-ai-overview-api](https://serpapi.com/google-ai-overview-api)

### Search Google Product

The Product API takes the `immersive_product_page_token` of a Google Shopping result, or a `product_id`:

```php
use SerpApi\Client;

$client = new Client(getenv('SERPAPI_KEY'));
$shopping = $client->search(['engine' => 'google_shopping', 'q' => 'coffee maker']);

$results = $client->search([
  'engine' => 'google_product',
  'page_token' => $shopping->shopping_results[0]->immersive_product_page_token,
]);

print_r($results->product_results);
```

 * source: [tests/ExampleSearchGoogleProductTest.php](https://github.com/serpapi/serpapi-php/blob/master/tests/ExampleSearchGoogleProductTest.php)
see: [https://serpapi.com/google-product-api](https://serpapi.com/google-product-api)

### Search Google Immersive Product

The Immersive Product API takes the `immersive_product_page_token` of a Google Shopping result:

```php
use SerpApi\Client;

$client = new Client(getenv('SERPAPI_KEY'));
$shopping = $client->search(['engine' => 'google_shopping', 'q' => 'coffee maker']);

$results = $client->search([
  'engine' => 'google_immersive_product',
  'page_token' => $shopping->shopping_results[0]->immersive_product_page_token,
]);

print_r($results->product_results);
```

 * source: [tests/ExampleSearchGoogleImmersiveProductTest.php](https://github.com/serpapi/serpapi-php/blob/master/tests/ExampleSearchGoogleImmersiveProductTest.php)
see: [https://serpapi.com/google-immersive-product-api](https://serpapi.com/google-immersive-product-api)

### Search Google Flights
```php
use SerpApi\Client;

$client = new Client(getenv('SERPAPI_KEY'));
$results = $client->search([
  'engine' => 'google_flights',
  'departure_id' => 'LAX',
  'arrival_id' => 'AUS',
  'outbound_date' => date('Y-m-d', strtotime('+30 days')),
  'return_date' => date('Y-m-d', strtotime('+37 days')),
]);

print_r($results->best_flights);
```

 * source: [tests/ExampleSearchGoogleFlightsTest.php](https://github.com/serpapi/serpapi-php/blob/master/tests/ExampleSearchGoogleFlightsTest.php)
see: [https://serpapi.com/google-flights-api](https://serpapi.com/google-flights-api)

### Search Google Hotels
```php
use SerpApi\Client;

$client = new Client(getenv('SERPAPI_KEY'));
$results = $client->search([
  'engine' => 'google_hotels',
  'q' => 'Bali Resorts',
  'check_in_date' => date('Y-m-d', strtotime('+30 days')),
  'check_out_date' => date('Y-m-d', strtotime('+31 days')),
  'adults' => '2',
  'currency' => 'USD',
  'gl' => 'us',
  'hl' => 'en',
]);

print_r($results->properties);
```

 * source: [tests/ExampleSearchGoogleHotelsTest.php](https://github.com/serpapi/serpapi-php/blob/master/tests/ExampleSearchGoogleHotelsTest.php)
see: [https://serpapi.com/google-hotels-api](https://serpapi.com/google-hotels-api)

### Search Google Finance
```php
use SerpApi\Client;

$client = new Client(getenv('SERPAPI_KEY'));
$results = $client->search([
  'engine' => 'google_finance',
  'q' => 'GOOG:NASDAQ',
]);

print_r($results->summary);
```

 * source: [tests/ExampleSearchGoogleFinanceTest.php](https://github.com/serpapi/serpapi-php/blob/master/tests/ExampleSearchGoogleFinanceTest.php)
see: [https://serpapi.com/google-finance-api](https://serpapi.com/google-finance-api)

### Search Google Trends
```php
use SerpApi\Client;

$client = new Client(getenv('SERPAPI_KEY'));
$results = $client->search([
  'engine' => 'google_trends',
  'q' => 'coffee,milk,bread,pasta,steak',
  'data_type' => 'TIMESERIES',
]);

print_r($results->interest_over_time);
```

 * source: [tests/ExampleSearchGoogleTrendsTest.php](https://github.com/serpapi/serpapi-php/blob/master/tests/ExampleSearchGoogleTrendsTest.php)
see: [https://serpapi.com/google-trends-api](https://serpapi.com/google-trends-api)

### Search Google Patents
```php
use SerpApi\Client;

$client = new Client(getenv('SERPAPI_KEY'));
$results = $client->search([
  'engine' => 'google_patents',
  'q' => '(Coffee)',
]);

print_r($results->organic_results);
```

 * source: [tests/ExampleSearchGooglePatentsTest.php](https://github.com/serpapi/serpapi-php/blob/master/tests/ExampleSearchGooglePatentsTest.php)
see: [https://serpapi.com/google-patents-api](https://serpapi.com/google-patents-api)

### Search Google Reverse Image
```php
use SerpApi\Client;

$client = new Client(getenv('SERPAPI_KEY'));
$results = $client->search([
  'engine' => 'google_reverse_image',
  'image_url' => 'https://i.imgur.com/HBrB8p0.png',
]);

print_r($results->image_results);
```

 * source: [tests/ExampleSearchGoogleReverseImageTest.php](https://github.com/serpapi/serpapi-php/blob/master/tests/ExampleSearchGoogleReverseImageTest.php)
see: [https://serpapi.com/google-reverse-image](https://serpapi.com/google-reverse-image)

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

### Search Amazon
```php
use SerpApi\Client;

$client = new Client(getenv('SERPAPI_KEY'));
$results = $client->search([
  'engine' => 'amazon',
  'k' => 'coffee',
]);

print_r($results->organic_results);
```

 * source: [tests/ExampleSearchAmazonTest.php](https://github.com/serpapi/serpapi-php/blob/master/tests/ExampleSearchAmazonTest.php)
see: [https://serpapi.com/amazon-search-api](https://serpapi.com/amazon-search-api)

### Search Yandex
```php
use SerpApi\Client;

$client = new Client(getenv('SERPAPI_KEY'));
$results = $client->search([
  'engine' => 'yandex',
  'text' => 'coffee',
]);

print_r($results->organic_results);
```

 * source: [tests/ExampleSearchYandexTest.php](https://github.com/serpapi/serpapi-php/blob/master/tests/ExampleSearchYandexTest.php)
see: [https://serpapi.com/yandex-search-api](https://serpapi.com/yandex-search-api)

### Search Yelp
```php
use SerpApi\Client;

$client = new Client(getenv('SERPAPI_KEY'));
$results = $client->search([
  'engine' => 'yelp',
  'find_desc' => 'Coffee',
  'find_loc' => 'New York, NY, USA',
]);

print_r($results->organic_results);
```

 * source: [tests/ExampleSearchYelpTest.php](https://github.com/serpapi/serpapi-php/blob/master/tests/ExampleSearchYelpTest.php)
see: [https://serpapi.com/yelp-search-api](https://serpapi.com/yelp-search-api)

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

### Image API

Upload a local image, then search with its `image_id` on engines that accept uploaded images, such as Google Lens:

```php
use SerpApi\Client;

$client = new Client(getenv('SERPAPI_KEY'));
$upload = $client->uploadImage('path/to/image.jpg');

$results = $client->search([
  'engine' => 'google_lens',
  'image_id' => $upload->image_id,
]);
print_r($results->visual_matches);
```

`uploadImage` also accepts an open stream, such as `fopen('path/to/image.jpg', 'rb')`. Images must be JPG, PNG, or WebP files up to 500 KB, and an `image_id` expires after 10 minutes.

see: [https://serpapi.com/image-api](https://serpapi.com/image-api) and [https://serpapi.com/google-lens-upload-an-image](https://serpapi.com/google-lens-upload-an-image)

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

Run the linter and static analysis:

```bash
make lint
make analyse
```

The `demo/` directory holds runnable scripts, including a batch of async searches and a persistent connection benchmark:

```bash
make demo
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
