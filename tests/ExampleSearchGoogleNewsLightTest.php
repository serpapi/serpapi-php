<?php

namespace SerpApi\Tests;

class ExampleSearchGoogleNewsLightTest extends SerpApiTestCase
{
  /** @var array<string, string> */
  private $searchParams;

  protected function setUp(): void
  {
    parent::setUp();
    $this->searchParams = [
      'engine' => 'google_news_light',
      'q' => 'pizza',
    ];
  }

  public function testResultExists()
  {
    $client = $this->serpApiClient();
    $response = $client->search($this->searchParams);
    $this->assertResponseHasProperty(
      $response,
      'news_results',
      'Error on `google_news_light` engine: no `news_results`'
    );
  }
}
