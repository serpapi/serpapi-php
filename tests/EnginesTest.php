<?php

namespace SerpApi\Tests;

class EnginesTest extends SerpApiTestCase
{
  /** @var array<string, mixed> */
  private $searchParams;

  protected function setUp(): void
  {
    parent::setUp();
    $this->searchParams = [
      'q' => 'Coffee',
      'location' => 'Austin,Texas',
    ];
  }

  public function testBingSearch()
  {
    $client = $this->serpApiClient('bing');
    $response = $client->search($this->searchParams);
    $this->assertEquals('Success', $response->search_metadata->status);
    $this->assertResponseHasProperty($response, 'organic_results');
    $this->assertNotEmpty($response->organic_results);
  }

  public function testBaiduSearch()
  {
    $client = $this->serpApiClient('baidu');
    $response = $client->search($this->searchParams);
    $this->assertEquals('Success', $response->search_metadata->status);
    $this->assertResponseHasProperty($response, 'organic_results');
    $this->assertNotEmpty($response->organic_results);
  }

  public function testYahooSearch()
  {
    $client = $this->serpApiClient('yahoo');
    $response = $client->search(['p' => 'Coffee']);
    $this->assertEquals('Success', $response->search_metadata->status);
    $this->assertResponseHasProperty($response, 'organic_results');
    $this->assertNotEmpty($response->organic_results);
  }

  public function testYandexSearch()
  {
    $client = $this->serpApiClient('yandex');
    $response = $client->search(['text' => 'Coffee']);
    $this->assertEquals('Success', $response->search_metadata->status);
    $this->assertResponseHasProperty($response, 'organic_results');
    $this->assertNotEmpty($response->organic_results);
  }

  public function testEbaySearch()
  {
    $client = $this->serpApiClient('ebay');
    $response = $client->search([
      '_nkw' => 'Coffee',
      'no_cache' => true,
    ]);
    $this->assertEquals('Success', $response->search_metadata->status);
    $this->assertResponseHasProperty($response, 'organic_results');
    $this->assertNotEmpty($response->organic_results);
  }

  public function testYoutubeSearch()
  {
    $client = $this->serpApiClient('youtube');
    $response = $client->search(['search_query' => 'Coffee']);
    $this->assertEquals('Success', $response->search_metadata->status);
    $this->assertResponseHasProperty($response, 'video_results');
    $this->assertNotEmpty($response->video_results);
  }
}
