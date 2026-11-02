<?php

namespace SerpApi\Tests;

class ExampleSearchHomeDepotTest extends SerpApiTestCase
{
  /** @var array<string, string> */
  private $searchParams;

  protected function setUp(): void
  {
    parent::setUp();
    $this->searchParams = [
      'engine' => 'home_depot',
      'q' => 'table',
    ];
  }

  public function testResultExists()
  {
    $client = $this->serpApiClient();
    $response = $client->search($this->searchParams);
    $this->assertResponseHasProperty($response, 'products', 'Error on `home_depot` engine: no `products`');
  }
}
