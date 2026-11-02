<?php

namespace SerpApi\Tests;

class ExampleSearchGoogleVideosTest extends SerpApiTestCase
{
  /** @var array<string, string> */
  private $searchParams;

  protected function setUp(): void
  {
    parent::setUp();
    $this->searchParams = [
      'engine' => 'google_videos',
      'q' => 'coffee',
    ];
  }

  public function testResultExists()
  {
    $client = $this->serpApiClient();
    $response = $client->search($this->searchParams);
    $this->assertResponseHasProperty(
      $response,
      'video_results',
      'Error on `google_videos` engine: no `video_results`'
    );
  }
}
