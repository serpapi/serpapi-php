<?php

namespace SerpApi\Tests;

class ExampleSearchEbayTest extends SerpApiTestCase
{
  /** @var array<string, string> */
  private $searchParams;

  protected function setUp(): void
  {
    parent::setUp();
    $this->searchParams = [
      'engine' => 'ebay',
      '_nkw' => 'water',
    ];
  }

  public function testResultExists()
  {
    $client = $this->serpApiClient();
    $response = $client->search($this->searchParams);
    $this->assertResponseHasProperty($response, 'organic_results', 'Error on `ebay` engine: no `organic_results`');
  }
}
