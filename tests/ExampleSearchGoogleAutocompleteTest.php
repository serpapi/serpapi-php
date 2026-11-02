<?php

namespace SerpApi\Tests;

class ExampleSearchGoogleAutocompleteTest extends SerpApiTestCase
{
  /** @var array<string, string> */
  private $searchParams;

  protected function setUp(): void
  {
    parent::setUp();
    $this->searchParams = [
      'engine' => 'google_autocomplete',
      'q' => 'coffee',
    ];
  }

  public function testResultExists()
  {
    $client = $this->serpApiClient();
    $response = $client->search($this->searchParams);
    $this->assertResponseHasProperty(
      $response,
      'suggestions',
      'Error on `google_autocomplete` engine: no `suggestions`'
    );
  }
}
