<?php

namespace SerpApi\Tests;

use SerpApi\Client;
use SerpApi\SerpApiException;

class ClientLegacyApiTest extends SerpApiTestCase
{
  protected function requiresApiKey(): bool
  {
    return false;
  }

  public function testSnakeCaseClientAliasesPreserveExistingAccessors()
  {
    $client = new Client([
      'api_key' => 'secret',
      'engine' => 'bing',
      'timeout' => 30,
      'persistent' => false,
      'assoc' => true,
      'hl' => 'en',
    ]);

    $this->assertSame('secret', $client->get_api_key());
    $this->assertSame('bing', $client->get_engine());
    $this->assertSame(30, $client->get_timeout());
    $this->assertSame(false, $client->is_persistent());
    $this->assertSame(true, $client->is_assoc());
    $this->assertSame(['engine' => 'bing', 'api_key' => 'secret', 'hl' => 'en'], $client->get_params());

    $client->set_api_key('updated');
    $this->assertSame('updated', $client->get_api_key());
  }

  public function testSnakeCaseArchiveAliasPreservesValidation()
  {
    $this->expectException(SerpApiException::class);
    $this->expectExceptionMessage('search_id must be present');

    (new Client('secret'))->search_archive('');
  }

  public function testSnakeCaseExceptionAccessorsPreserveExistingKeys()
  {
    $exception = new SerpApiException('failure', 'invalid key', ['q' => 'coffee'], 401, 'abc123', 'json');

    $this->assertSame('invalid key', $exception->get_serpapi_error());
    $this->assertSame(['q' => 'coffee'], $exception->get_search_params());
    $this->assertSame(401, $exception->get_response_status());
    $this->assertSame('abc123', $exception->get_search_id());
    $this->assertSame('json', $exception->get_decoder());
    $this->assertSame([
      'message' => 'failure',
      'serpapi_error' => 'invalid key',
      'search_params' => ['q' => 'coffee'],
      'response_status' => 401,
      'search_id' => 'abc123',
      'decoder' => 'json',
    ], $exception->to_array());
  }
}
