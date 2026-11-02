<?php

/**
 * Persistent connection benchmark.
 *
 * Compares a client that reuses its connection with one that opens a new
 * connection for every request. Uses the Location API, which needs no API
 * key.
 *
 * Usage:
 *   php demo/demo_persistent.php
 */

require __DIR__ . '/../vendor/autoload.php';

use SerpApi\Client;

$requests = 10;

$benchmark = static function (Client $client) use ($requests): float {
  // Leave the first request out of the timing so DNS lookups and cold
  // caches count against neither client.
  $client->location(['q' => 'Austin', 'limit' => 1]);

  $start = microtime(true);
  for ($i = 0; $i < $requests; $i++) {
    $client->location(['q' => 'Austin', 'limit' => 1]);
  }

  return microtime(true) - $start;
};

$without = $benchmark(new Client(['persistent' => false]));
printf("persistent off: %6.0f ms  (%.1f req/s)\n", $without * 1000, $requests / $without);

$client = new Client(['persistent' => true]);
$with = $benchmark($client);
$client->close();
printf("persistent on:  %6.0f ms  (%.1f req/s)\n", $with * 1000, $requests / $with);

printf("\nspeedup: %.1fx\n", $without / $with);
echo "done\n";
