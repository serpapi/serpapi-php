<?php

namespace SerpApi\Tests;

class ExampleSearchGoogleHotelsTest extends SerpApiTestCase
{
  /** @var array<string, string> */
  private $searchParams;

  protected function setUp(): void
  {
    parent::setUp();

    // Dates are relative to today so the example never asks for a past stay.
    $this->searchParams = [
      'engine' => 'google_hotels',
      'q' => 'Bali Resorts',
      'check_in_date' => date('Y-m-d', strtotime('+30 days')),
      'check_out_date' => date('Y-m-d', strtotime('+31 days')),
      'adults' => '2',
      'currency' => 'USD',
      'gl' => 'us',
      'hl' => 'en',
    ];
  }

  public function testResultExists()
  {
    $client = $this->serpApiClient();
    $response = $client->search($this->searchParams);
    $this->assertResponseHasProperty(
      $response,
      'properties',
      'Error on `google_hotels` engine: no `properties`'
    );
  }
}
