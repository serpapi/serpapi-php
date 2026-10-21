<?php

namespace SerpApi\Tests;

class ExampleSearchGoogleAiModeTest extends SerpApiTestCase
{
  /** @var array<string, string> */
  private $searchParams;

  protected function setUp(): void
  {
    parent::setUp();
    $this->searchParams = [
      'engine' => 'google_ai_mode',
      'q' => 'best coffee maker',
    ];
  }

  public function testResultExists()
  {
    $client = $this->serpApiClient();
    $response = $client->search($this->searchParams);
    $this->assertResponseHasProperty(
      $response,
      'reconstructed_markdown',
      'Error on `google_ai_mode` engine: no `reconstructed_markdown`'
    );
  }
}
