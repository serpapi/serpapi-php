<?php

namespace SerpApi\Tests;

class ExampleSearchGoogleImagesLightTest extends SerpApiTestCase
{
  /** @var array<string, string> */
  private $searchParams;

  protected function setUp(): void
  {
    parent::setUp();
    $this->searchParams = [
      'engine' => 'google_images_light',
      'q' => 'Coffee',
    ];
  }

  public function testResultExists()
  {
    $client = $this->serpApiClient();
    $response = $client->search($this->searchParams);
    $this->assertResponseHasProperty(
      $response,
      'images_results',
      'Error on `google_images_light` engine: no `images_results`'
    );
  }
}
