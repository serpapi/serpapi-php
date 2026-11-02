<?php

namespace SerpApi\Tests;

class ExampleSearchYandexTest extends SerpApiTestCase
{
  /** @var array<string, string> */
  private $searchParams;

  protected function setUp(): void
  {
    parent::setUp();
    $this->searchParams = [
      'engine' => 'yandex',
      'text' => 'coffee',
    ];
  }

  public function testResultExists()
  {
    $client = $this->serpApiClient();
    $response = $client->search($this->searchParams);
    $this->assertResponseHasProperty(
      $response,
      'organic_results',
      'Error on `yandex` engine: no `organic_results`'
    );
  }
}
