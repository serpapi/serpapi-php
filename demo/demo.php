<?php

/**
 * Basic search.
 *
 * Usage:
 *   composer install
 *   export SERPAPI_KEY="your secret key"  # https://serpapi.com/manage-api-key
 *   php demo/demo.php
 */

require __DIR__ . '/../vendor/autoload.php';

use SerpApi\Client;

$apiKey = getenv('SERPAPI_KEY');
if (empty($apiKey)) {
  fwrite(STDERR, "SERPAPI_KEY environment variable must be set\n");
  exit(1);
}

$client = new Client([
  'api_key' => $apiKey,
  'engine' => 'google',
]);

$results = $client->search(['q' => 'coffee']);

if (empty($results->organic_results)) {
  fwrite(STDERR, "no organic results found\n");
  exit(1);
}

foreach ($results->organic_results as $result) {
  printf("%d. %s\n   %s\n", $result->position, $result->title, $result->link);
}

$client->close();
echo "done\n";
