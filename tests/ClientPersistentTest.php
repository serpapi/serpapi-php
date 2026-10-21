<?php

namespace SerpApi\Tests;

use SerpApi\Client;
use SerpApi\SerpApiException;

/**
 * Connection reuse, exercised through the real cURL code path against a
 * local file:// URL, so these tests need neither an API key nor network.
 */
class ClientPersistentTest extends SerpApiTestCase
{
  private const MARKER = 'class LocalFileClient';

  protected function requiresApiKey(): bool
  {
    return false;
  }

  /**
   * @return resource|\CurlHandle|null
   */
  private function handle(Client $client)
  {
    $property = new \ReflectionProperty(Client::class, 'handle');

    // Required before PHP 8.1, and deprecated as a no-op since 8.5.
    if (PHP_VERSION_ID < 80100) {
      $property->setAccessible(true);
    }

    return $property->getValue($client);
  }

  public function testPersistentIsEnabledByDefault()
  {
    $this->assertTrue((new Client('secret'))->isPersistent());
  }

  public function testPersistentCanBeDisabled()
  {
    $this->assertFalse((new Client(['api_key' => 'secret', 'persistent' => false]))->isPersistent());
    $this->assertFalse((new Client('secret', 'google', 120, ['persistent' => false]))->isPersistent());
  }

  public function testPersistentIsNotSentAsASearchParameter()
  {
    $client = new RecordingClient(['api_key' => 'secret', 'persistent' => false]);
    $client->search(['q' => 'coffee']);

    $this->assertArrayNotHasKey('persistent', $client->getParams());
    $this->assertArrayNotHasKey('persistent', $client->lastQuery());
  }

  public function testPerCallPersistentIsRejected()
  {
    $this->expectException(SerpApiException::class);
    $this->expectExceptionMessage('persistent can only be set on the client');
    (new RecordingClient('secret'))->search(['persistent' => false]);
  }

  public function testInspectReportsPersistentMode()
  {
    $this->assertStringContainsString('@persistent=true', (new Client('secret'))->inspect());
    $this->assertStringContainsString('@persistent=false', (new Client(['persistent' => false]))->inspect());
  }

  public function testRequestsWorkThroughTheSharedHandle()
  {
    $client = new LocalFileClient('secret');

    $this->assertStringContainsString(self::MARKER, $client->fetch());
    $this->assertStringContainsString(self::MARKER, $client->fetch());
  }

  public function testNoConnectionIsOpenedBeforeTheFirstRequest()
  {
    $this->assertNull($this->handle(new LocalFileClient('secret')));
  }

  public function testPersistentClientReusesTheSameHandle()
  {
    $client = new LocalFileClient('secret');

    $client->fetch();
    $first = $this->handle($client);
    $client->fetch();

    $this->assertNotNull($first);
    $this->assertSame($first, $this->handle($client));
  }

  public function testNonPersistentClientKeepsNoHandle()
  {
    $client = new LocalFileClient(['api_key' => 'secret', 'persistent' => false]);

    $this->assertStringContainsString(self::MARKER, $client->fetch());
    $this->assertNull($this->handle($client));
  }

  public function testCloseIsIdempotentAndTheClientReconnects()
  {
    $client = new LocalFileClient('secret');
    $client->fetch();
    $first = $this->handle($client);

    $client->close();
    $client->close();
    $this->assertNull($this->handle($client));

    $client->fetch();
    $this->assertNotNull($this->handle($client));
    $this->assertNotSame($first, $this->handle($client));
  }

  public function testCloneOpensItsOwnConnection()
  {
    $client = new LocalFileClient('secret');
    $client->fetch();
    $original = $this->handle($client);

    $copy = clone $client;
    $this->assertNull($this->handle($copy));

    $copy->fetch();
    unset($copy);

    $this->assertSame($original, $this->handle($client));
    $this->assertStringContainsString(self::MARKER, $client->fetch());
  }

  public function testClientCanBeSerializedAfterARequest()
  {
    $client = new LocalFileClient(['api_key' => 'secret', 'engine' => 'bing', 'hl' => 'en']);
    $client->fetch();

    $restored = unserialize(serialize($client));

    $this->assertInstanceOf(LocalFileClient::class, $restored);
    $this->assertEquals('bing', $restored->getEngine());
    $this->assertEquals('secret', $restored->getApiKey());
    $this->assertEquals(['hl' => 'en'], $restored->getParams());
    $this->assertStringContainsString(self::MARKER, $restored->fetch());
  }
}
