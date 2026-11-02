<?php

namespace SerpApi\Tests;

class ExampleSearchGoogleLensTest extends SerpApiTestCase
{
  /** @var array<string, string> */
  private $searchParams;

  protected function setUp(): void
  {
    parent::setUp();
    $this->searchParams = [
      'engine' => 'google_lens',
      'url' => 'https://i.imgur.com/5bGzZi7.jpg',
      'gl' => 'us',
      'hl' => 'en',
    ];
  }

  public function testResultExists()
  {
    $client = $this->serpApiClient();
    $response = $client->search($this->searchParams);
    $this->assertResponseHasProperty($response, 'visual_matches', 'Error on `google_lens` engine: no `visual_matches`');
  }
}
