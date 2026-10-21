<?php

namespace SerpApi\Tests;

use SerpApi\SerpApiException;

/**
 * searchArray() decodes results to associative arrays, the PHP counterpart
 * of the Ruby client's symbolize_names option. Runs without an API key and
 * without network access.
 */
class ClientSearchArrayTest extends SerpApiTestCase
{
  private const RESULTS = '{"search_metadata":{"id":"abc123","status":"Success"},'
    . '"organic_results":[{"position":1,"title":"Coffee"}]}';

  protected function requiresApiKey(): bool
  {
    return false;
  }

  public function testSearchArrayReturnsAssociativeArray()
  {
    $client = (new RecordingClient('secret'))->willReturn(self::RESULTS);
    $results = $client->searchArray(['q' => 'coffee']);

    $this->assertIsArray($results);
    $this->assertIsArray($results['organic_results'][0]);
    $this->assertEquals('Coffee', $results['organic_results'][0]['title']);
    $this->assertEquals('abc123', $results['search_metadata']['id']);
  }

  public function testSearchArraySendsSameQueryAsSearch()
  {
    $client = (new RecordingClient('secret', 'bing'))->willReturn(self::RESULTS);

    $client->search(['q' => 'coffee']);
    $objectQuery = $client->lastQuery();

    $client->searchArray(['q' => 'coffee']);

    $this->assertEquals('/search', $client->lastPath());
    $this->assertEquals($objectQuery, $client->lastQuery());
  }

  public function testSearchStillReturnsObject()
  {
    $client = (new RecordingClient('secret'))->willReturn(self::RESULTS);
    $results = $client->search(['q' => 'coffee']);

    $this->assertIsObject($results);
    $this->assertEquals('Coffee', $results->organic_results[0]->title);
  }

  public function testSearchArrayRaisesSerpApiError()
  {
    $client = (new RecordingClient('secret'))->willReturn(
      '{"error":"Invalid API key.","search_metadata":{"id":"abc123"}}',
      401
    );

    try {
      $client->searchArray(['q' => 'coffee']);
      $this->fail('Expected SerpApiException');
    } catch (SerpApiException $e) {
      $this->assertEquals('Invalid API key.', $e->getSerpApiError());
      $this->assertEquals('abc123', $e->getSearchId());
      $this->assertEquals(401, $e->getResponseStatus());
    }
  }

  public function testSearchArrayRaisesErrorReturnedWithStatus200()
  {
    $client = (new RecordingClient('secret'))->willReturn(
      '{"error":"Google hasn\'t returned any results for this query."}'
    );

    $this->expectException(SerpApiException::class);
    $this->expectExceptionMessage("error: Google hasn't returned any results for this query.");

    $client->searchArray(['q' => 'zzzz']);
  }

  public function testSearchArrayRaisesOnInvalidJson()
  {
    $client = (new RecordingClient('secret'))->willReturn('not json');

    $this->expectException(SerpApiException::class);
    $this->expectExceptionMessage('JSON parse error');

    $client->searchArray(['q' => 'coffee']);
  }
}
