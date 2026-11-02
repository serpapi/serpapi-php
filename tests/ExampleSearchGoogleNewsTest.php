<?php

namespace SerpApi\Tests;

class ExampleSearchGoogleNewsTest extends SerpApiTestCase
{
  /** @var array<string, string> */
  private $searchParams;

  protected function setUp(): void
  {
    parent::setUp();
    $this->searchParams = [
      'engine' => 'google_news',
      'q' => 'pizza',
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
      'news_results',
      'Error on `google_news` engine: no `news_results`'
    );
  }
}
