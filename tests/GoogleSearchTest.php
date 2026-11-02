<?php

namespace SerpApi\Tests;

class GoogleSearchTest extends SerpApiTestCase
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

  public function testGoogleSearchReturnsOrganicResults()
  {
    $client = $this->serpApiClient('google');
    $response = $client->search($this->searchParams);
    $this->assertEquals('Success', $response->search_metadata->status);
    $this->assertResponseHasProperty($response, 'organic_results');
    $this->assertNotEmpty($response->organic_results);
  }

  public function testGoogleHtmlReturnsHtmlPayload()
  {
    $client = $this->serpApiClient('google');
    $response = $client->html($this->searchParams);
    $this->assertGreaterThan(10000, strlen($response));
  }

  public function testGoogleAccountReturnsApiKey()
  {
    $client = $this->serpApiClient('google');
    $info = $client->account();
    $this->assertEquals($client->getApiKey(), $info->api_key);
  }

  public function testGoogleLocationReturnsResults()
  {
    $client = $this->serpApiClient('google');
    $location_list = $client->location(['q' => 'Austin', 'limit' => 3]);
    $this->assertCount(3, $location_list);
    $this->assertStringContainsString('Austin', $location_list[0]->name);
    $this->assertGreaterThan(0, $location_list[0]->google_id);
  }

  public function testGoogleSearchArchiveReturnsSameId()
  {
    $client = $this->serpApiClient('google');
    $result = $client->search($this->searchParams);
    $archived_result = $client->searchArchive($result->search_metadata->id);
    $this->assertEquals($result->search_metadata->id, $archived_result->search_metadata->id);
  }
}
