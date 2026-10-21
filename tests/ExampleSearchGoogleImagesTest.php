<?php

namespace SerpApi\Tests;

class ExampleSearchGoogleImagesTest extends SerpApiTestCase
{
  /** @var array<string, string> */
  private $searchParams;

  protected function setUp(): void
  {
    parent::setUp();
    $this->searchParams = [
      'engine' => 'google_images',
      'q' => 'coffee',
    ];
  }

  public function testResultExists()
  {
    $client = $this->serpApiClient();
    $response = $client->search($this->searchParams);
    $this->assertResponseHasProperty(
      $response,
      'images_results',
      'Error on `google_images` engine: no `images_results`'
    );
  }
}
