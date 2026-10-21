<?php

namespace SerpApi\Tests;

class ExampleSearchGoogleFlightsTest extends SerpApiTestCase
{
  /** @var array<string, string> */
  private $searchParams;

  protected function setUp(): void
  {
    parent::setUp();

    // Dates are relative to today so the example never asks for a past flight.
    $this->searchParams = [
      'engine' => 'google_flights',
      'departure_id' => 'LAX',
      'arrival_id' => 'AUS',
      'outbound_date' => date('Y-m-d', strtotime('+30 days')),
      'return_date' => date('Y-m-d', strtotime('+37 days')),
    ];
  }

  public function testResultExists()
  {
    $client = $this->serpApiClient();
    $response = $client->search($this->searchParams);

    // Google only ranks some routes, so either list counts as a result.
    $this->assertNotNull(
      $response->best_flights ?? $response->other_flights ?? null,
      'Error on `google_flights` engine: no `best_flights` or `other_flights`'
    );
  }
}
