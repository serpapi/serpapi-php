<?php

namespace SerpApi\Tests;

class ExampleSearchGooglePlayTest extends SerpApiTestCase
{
  /** @var array<string, string> */
  private $searchParams;

  protected function setUp(): void
  {
    parent::setUp();
    $this->searchParams = [
      'engine' => 'google_play',
      'q' => 'kite',
      'store' => 'apps',
    ];
  }

  public function testResultExists()
  {
    $client = $this->serpApiClient();
    $response = $client->search($this->searchParams);
    $this->assertResponseHasProperty(
      $response,
      'organic_results',
      'Error on `google_play` engine: no `organic_results`'
    );
  }
}
