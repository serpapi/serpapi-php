<?php

/**
 * Google Autocomplete suggestions.
 *
 * Search parameters set once on the client (client, hl, gl) are sent with
 * every call, so only the query changes from one search to the next.
 *
 * Usage:
 *   export SERPAPI_KEY="your secret key"
 *   php demo/demo_suggest.php
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
  'engine' => 'google_autocomplete',
  'timeout' => 10,
  'client' => 'safari',
  'hl' => 'en',
  'gl' => 'us',
]);

foreach (['coffee', 'espresso'] as $query) {
  $results = $client->search(['q' => $query]);

  if (empty($results->suggestions)) {
    fwrite(STDERR, "no suggestions found for {$query}\n");
    exit(1);
  }

  echo "{$query}:\n";
  foreach ($results->suggestions as $suggestion) {
    echo " - {$suggestion->value}\n";
  }
}

echo "done\n";
