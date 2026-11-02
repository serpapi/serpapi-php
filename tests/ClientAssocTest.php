<?php

namespace SerpApi\Tests;

use SerpApi\Client;

/**
 * Decoding JSON responses to associative arrays instead of stdClass,
 * the PHP counterpart of the Ruby client's symbolize_names option.
 */
class ClientAssocTest extends SerpApiTestCase
{
  protected function requiresApiKey(): bool
  {
    return false;
  }

  public function testObjectsAreReturnedByDefault()
  {
    $this->assertFalse((new RecordingClient('secret'))->isAssoc());
  }

  public function testAssocCanBeEnabled()
  {
    $client = new RecordingClient(['api_key' => 'secret', 'assoc' => true]);
    $this->assertTrue($client->isAssoc());

    $results = $client->willReturn('{"organic_results":[{"title":"Coffee"}]}')->search();
    $this->assertSame('Coffee', $results['organic_results'][0]['title']);
  }

  public function testAssocIsNotSentAsASearchParameter()
  {
    $client = new RecordingClient(['api_key' => 'secret', 'assoc' => true]);
    $client->search(['q' => 'coffee']);

    $this->assertArrayNotHasKey('assoc', $client->getParams());
    $this->assertArrayNotHasKey('assoc', $client->lastQuery());
  }

  public function testPerCallAssocIsNotSentAsASearchParameter()
  {
    $client = new RecordingClient('secret');
    $client->search(['q' => 'coffee', 'assoc' => true]);

    $this->assertArrayNotHasKey('assoc', $client->lastQuery());
    $this->assertSame('coffee', $client->lastQuery()['q']);
  }

  public function testDigReadsBothObjectAndArrayShapes()
  {
    $client = new RecordingClient('secret');

    foreach ([json_decode('{"error":"boom"}'), json_decode('{"error":"boom"}', true)] as $data) {
      $this->assertEquals('boom', $this->dig($client, $data, 'error'));
      $this->assertNull($this->dig($client, $data, 'missing'));
    }
  }

  public function testDigToleratesScalarAndNullResponses()
  {
    $client = new RecordingClient('secret');

    $this->assertNull($this->dig($client, null, 'error'));
    $this->assertNull($this->dig($client, 'a string', 'error'));
  }

  /**
   * @param mixed $data
   * @return mixed
   */
  private function dig(Client $client, $data, string $key) {
    $method = new \ReflectionMethod(Client::class, 'dig');

    if (PHP_VERSION_ID < 80100) {
      $method->setAccessible(true);
    }

    return $method->invoke($client, $data, $key);
  }
}
