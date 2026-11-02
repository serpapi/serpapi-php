<?php

namespace SerpApi\Tests;

use SerpApi\Client;

/**
 * The query string sent with each request. Runs without an API key and
 * without network access.
 */
class ClientQueryTest extends SerpApiTestCase
{
  protected function requiresApiKey(): bool
  {
    return false;
  }

  public function testSourceReportsLibraryNameAndVersion()
  {
    $client = new RecordingClient('secret');
    $client->search(['q' => 'coffee']);

    $this->assertEquals('serpapi-php:' . Client::VERSION, $client->lastQuery()['source']);
  }

  public function testQueryIncludesEngineApiKeyAndOutput()
  {
    $client = new RecordingClient('secret', 'bing');
    $client->search(['q' => 'coffee']);
    $query = $client->lastQuery();

    $this->assertEquals('/search', $client->lastPath());
    $this->assertEquals('bing', $query['engine']);
    $this->assertEquals('secret', $query['api_key']);
    $this->assertEquals('coffee', $query['q']);
    $this->assertEquals('json', $query['output']);
  }

  public function testPerCallParamsOverrideEngineAndApiKey()
  {
    $client = new RecordingClient('secret');
    $client->search(['engine' => 'bing', 'api_key' => 'other']);
    $query = $client->lastQuery();

    $this->assertEquals('bing', $query['engine']);
    $this->assertEquals('other', $query['api_key']);
  }

  public function testLocationOmitsApiKeyWhenNotSet()
  {
    $client = (new RecordingClient())->willReturn('[]');
    $client->location(['q' => 'Austin']);

    $this->assertArrayNotHasKey('api_key', $client->lastQuery());
  }

  public function testOutputReflectsRequestedFormat()
  {
    $client = (new RecordingClient('secret'))->willReturn('<html></html>');
    $client->html(['q' => 'coffee']);

    $this->assertEquals('html', $client->lastQuery()['output']);
  }
}
