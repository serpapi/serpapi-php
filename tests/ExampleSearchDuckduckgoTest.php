<?php

namespace SerpApi\Tests;

class ExampleSearchDuckduckgoTest extends SerpApiTestCase
{
  /** @var array<string, string> */
  private $searchParams;

  protected function setUp(): void
  {
    parent::setUp();
    $this->searchParams = [
      'engine' => 'duckduckgo',
      'q' => 'coffee',
    ];
  }

  public function testResultExists()
  {
    $client = $this->serpApiClient();
    $response = $client->search($this->searchParams);
    $this->assertResponseHasProperty(
      $response,
      'organic_results',
      'Error on `duckduckgo` engine: no `organic_results`'
    );
  }
}
