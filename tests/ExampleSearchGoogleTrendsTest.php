<?php

namespace SerpApi\Tests;

class ExampleSearchGoogleTrendsTest extends SerpApiTestCase
{
  /** @var array<string, string> */
  private $searchParams;

  protected function setUp(): void
  {
    parent::setUp();
    $this->searchParams = [
      'engine' => 'google_trends',
      'q' => 'coffee,milk,bread,pasta,steak',
      'data_type' => 'TIMESERIES',
    ];
  }

  public function testResultExists()
  {
    $client = $this->serpApiClient();
    $response = $client->search($this->searchParams);
    $this->assertResponseHasProperty(
      $response,
      'interest_over_time',
      'Error on `google_trends` engine: no `interest_over_time`'
    );
  }
}
