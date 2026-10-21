<?php

namespace SerpApi\Tests;

class ClientIntegrationTest extends SerpApiTestCase
{
  /** @var array<string, string> */
  private $searchParams;

  protected function setUp(): void
  {
    parent::setUp();
    $this->searchParams = [
      'q' => 'Coffee',
      'location' => 'Austin,Texas',
    ];
  }

  public function testAccount()
  {
    $client = $this->serpApiClient();
    $response = $client->account();
    $this->assertEquals($client->getApiKey(), $response->api_key);
  }

  public function testHtml()
  {
    $client = $this->serpApiClient();
    $response = $client->html($this->searchParams);
    $this->assertGreaterThan(10000, strlen($response));
  }

  public function testMd()
  {
    $client = $this->serpApiClient();
    $response = $client->md($this->searchParams);
    $this->assertStringStartsWith('---', $response);
    $this->assertStringContainsString('## Organic Results', $response);
  }

  public function testSearch()
  {
    $client = $this->serpApiClient();
    $response = $client->search($this->searchParams);
    $this->assertEquals('Success', $response->search_metadata->status);
    $this->assertResponseHasProperty($response, 'organic_results');
    $this->assertNotEmpty($response->organic_results);
  }

  public function testLocation()
  {
    $client = $this->serpApiClient();
    $location_list = $client->location(['q' => 'Austin', 'limit' => 3]);
    $this->assertCount(3, $location_list);
    $this->assertStringContainsString('Austin', $location_list[0]->name);
    $this->assertGreaterThan(0, $location_list[0]->google_id);
  }

  public function testSearchArchive()
  {
    $client = $this->serpApiClient();
    $result = $client->search($this->searchParams);
    $archived_result = $client->searchArchive($result->search_metadata->id);
    $this->assertEquals($result->search_metadata->id, $archived_result->search_metadata->id);

    $archived_markdown = $client->searchArchive($result->search_metadata->id, 'md');
    $this->assertStringStartsWith('---', $archived_markdown);
    $this->assertStringContainsString('## Organic Results', $archived_markdown);
  }

  public function testUploadImage()
  {
    $client = $this->serpApiClient();
    $upload = $client->uploadImage(__DIR__ . '/fixtures/mona-lisa.jpg');
    $this->assertNotEmpty($upload->image_id);

    // The search is a GET on the same connection the upload was POSTed on.
    $response = $client->search([
      'engine' => 'google_lens',
      'image_id' => $upload->image_id,
    ]);
    $this->assertEquals('Success', $response->search_metadata->status);
    $this->assertResponseHasProperty($response, 'visual_matches');
  }
}
