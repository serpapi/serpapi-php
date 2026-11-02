<?php

namespace SerpApi\Tests;

class ExampleSearchGoogleReverseImageTest extends SerpApiTestCase
{
  /** @var array<string, string> */
  private $searchParams;

  protected function setUp(): void
  {
    parent::setUp();
    $this->searchParams = [
      'engine' => 'google_reverse_image',
      'image_url' => 'https://i.imgur.com/HBrB8p0.png',
    ];
  }

  public function testResultExists()
  {
    $client = $this->serpApiClient();
    $response = $client->search($this->searchParams);
    $this->assertResponseHasProperty(
      $response,
      'image_results',
      'Error on `google_reverse_image` engine: no `image_results`'
    );
  }
}
