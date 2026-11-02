<?php

namespace SerpApi\Tests;

class ExampleSearchYelpTest extends SerpApiTestCase
{
  /** @var array<string, string> */
  private $searchParams;

  protected function setUp(): void
  {
    parent::setUp();
    $this->searchParams = [
      'engine' => 'yelp',
      'find_desc' => 'Coffee',
      'find_loc' => 'New York, NY, USA',
    ];
  }

  public function testResultExists()
  {
    $client = $this->serpApiClient();
    $response = $client->search($this->searchParams);
    $this->assertResponseHasProperty(
      $response,
      'organic_results',
      'Error on `yelp` engine: no `organic_results`'
    );
  }
}
