<?php

namespace SerpApi\Tests;

class ExampleSearchGoogleScholarTest extends SerpApiTestCase
{
  /** @var array<string, string> */
  private $searchParams;

  protected function setUp(): void
  {
    parent::setUp();
    $this->searchParams = [
      'engine' => 'google_scholar',
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
      'Error on `google_scholar` engine: no `organic_results`'
    );
  }
}
