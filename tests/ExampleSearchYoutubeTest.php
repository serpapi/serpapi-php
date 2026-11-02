<?php

namespace SerpApi\Tests;

class ExampleSearchYoutubeTest extends SerpApiTestCase
{
  /** @var array<string, string> */
  private $searchParams;

  protected function setUp(): void
  {
    parent::setUp();
    $this->searchParams = [
      'engine' => 'youtube',
      'search_query' => 'coffee',
    ];
  }

  public function testResultExists()
  {
    $client = $this->serpApiClient();
    $response = $client->search($this->searchParams);
    $this->assertResponseHasProperty($response, 'video_results', 'Error on `youtube` engine: no `video_results`');
  }
}
