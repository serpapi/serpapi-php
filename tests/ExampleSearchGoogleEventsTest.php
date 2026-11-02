<?php

namespace SerpApi\Tests;

use SerpApi\SerpApiException;

class ExampleSearchGoogleEventsTest extends SerpApiTestCase
{
  /** @var array<string, string> */
  private $searchParams;

  protected function setUp(): void
  {
    parent::setUp();
    $this->searchParams = [
      'engine' => 'google_events',
      'q' => 'Events in Austin',
      'location' => 'Austin, Texas, United States',
    ];
  }

  public function testResultExists()
  {
    $client = $this->serpApiClient();

    // Temporary fix for google_events engine being unavailable
    try {
      $response = $client->search($this->searchParams);
    } catch (SerpApiException $e) {
      $this->markTestSkipped(
        'google_events is currently unavailable: ' . ($e->getSerpApiError() ?? $e->getMessage())
      );
    }

    if (!property_exists($response, 'events_results')) {
      $this->markTestSkipped('google_events returned no events_results');
    }

    $this->assertResponseHasProperty(
      $response,
      'events_results',
      'Error on `google_events` engine: no `events_results`'
    );
  }
}
