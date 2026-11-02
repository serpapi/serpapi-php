<?php

namespace SerpApi\Tests;

class ExampleSearchGoogleFinanceTest extends SerpApiTestCase
{
  /** @var array<string, string> */
  private $searchParams;

  protected function setUp(): void
  {
    parent::setUp();
    $this->searchParams = [
      'engine' => 'google_finance',
      'q' => 'GOOG:NASDAQ',
    ];
  }

  public function testResultExists()
  {
    $client = $this->serpApiClient();
    $response = $client->search($this->searchParams);
    $this->assertResponseHasProperty(
      $response,
      'summary',
      'Error on `google_finance` engine: no `summary`'
    );
  }
}
