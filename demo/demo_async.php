<?php

/**
 * Non-blocking searches at scale.
 *
 * With `async => true` SerpApi accepts a search and returns right away
 * instead of waiting for the results. Submitting a batch first and then
 * collecting the results through the Search Archive API is much faster
 * than running the searches one after another.
 *
 * Usage:
 *   export SERPAPI_KEY="your secret key"
 *   php demo/demo_async.php
 */

require __DIR__ . '/../vendor/autoload.php';

use SerpApi\Client;
use SerpApi\SerpApiException;

$apiKey = getenv('SERPAPI_KEY');
if (empty($apiKey)) {
  fwrite(STDERR, "SERPAPI_KEY environment variable must be set\n");
  exit(1);
}

// One client, and so one open connection, for the whole batch.
$client = new Client(['api_key' => $apiKey, 'engine' => 'google']);

// `async` is passed per call rather than set on the client, where it would
// also be sent with every Search Archive request below.
$pending = [];
foreach (['meta', 'amazon', 'apple', 'netflix', 'google'] as $company) {
  $submitted = $client->search(['q' => $company, 'async' => true]);
  $pending[$submitted->search_metadata->id] = $company;
  echo "submitted: {$company}\n";
}

echo "\ncollecting " . count($pending) . " results\n";

$deadline = time() + 120;
while (!empty($pending)) {
  if (time() >= $deadline) {
    fwrite(STDERR, 'timed out waiting for: ' . implode(', ', $pending) . "\n");
    exit(1);
  }

  foreach ($pending as $searchId => $company) {
    try {
      $archived = $client->searchArchive((string) $searchId);
    } catch (SerpApiException $e) {
      // A failed search is archived with an `error`, which the client raises.
      fwrite(STDERR, "{$company} failed: {$e->getMessage()}\n");
      exit(1);
    }

    // Queued or Processing: check again on the next pass.
    if ($archived->search_metadata->status !== 'Success') {
      continue;
    }

    $count = count($archived->organic_results ?? []);
    printf("  %-8s %d organic results\n", $company, $count);
    unset($pending[$searchId]);
  }

  if (!empty($pending)) {
    sleep(1);
  }
}

$client->close();
echo "done\n";
