<?php

namespace SerpApi\Tests;

use SerpApi\Client;
use SerpApi\SerpApiException;

/**
 * Default search parameters, and the array constructor that mirrors the
 * Ruby client.
 */
class ClientDefaultParamsTest extends SerpApiTestCase
{
  protected function requiresApiKey(): bool
  {
    return false;
  }

  public function testDefaultParamsAreSentWithEveryRequest()
  {
    $client = new RecordingClient('secret', 'google', 120, ['hl' => 'en', 'gl' => 'us']);
    $client->search(['q' => 'coffee']);
    $query = $client->lastQuery();

    $this->assertEquals('en', $query['hl']);
    $this->assertEquals('us', $query['gl']);
    $this->assertEquals('coffee', $query['q']);
  }

  public function testPerCallParamsOverrideDefaults()
  {
    $client = new RecordingClient('secret', 'google', 120, ['hl' => 'en']);
    $client->search(['hl' => 'fr']);

    $this->assertEquals('fr', $client->lastQuery()['hl']);
  }

  public function testNullDropsADefaultParamForOneCall()
  {
    $client = new RecordingClient('secret', 'google', 120, ['hl' => 'en']);

    $client->search(['hl' => null]);
    $this->assertArrayNotHasKey('hl', $client->lastQuery());

    $client->search();
    $this->assertEquals('en', $client->lastQuery()['hl']);
  }

  public function testNullCannotDropTheEngineOrApiKey()
  {
    $client = new RecordingClient('secret', 'bing');
    $client->search(['engine' => null, 'api_key' => null]);
    $query = $client->lastQuery();

    $this->assertEquals('bing', $query['engine']);
    $this->assertEquals('secret', $query['api_key']);
  }

  public function testArrayConstructorSetsConfiguration()
  {
    $client = new Client([
      'api_key' => 'secret',
      'engine' => 'bing',
      'timeout' => 30,
    ]);

    $this->assertEquals('secret', $client->getApiKey());
    $this->assertEquals('bing', $client->getEngine());
    $this->assertEquals(30, $client->getTimeout());
    $this->assertEquals([], $client->getParams());
  }

  public function testArrayConstructorTreatsOtherKeysAsDefaultParams()
  {
    $client = new RecordingClient([
      'api_key' => 'secret',
      'location' => 'Austin, TX',
      'no_cache' => true,
    ]);
    $client->search(['q' => 'coffee']);
    $query = $client->lastQuery();

    $this->assertEquals(['location' => 'Austin, TX', 'no_cache' => true], $client->getParams());
    $this->assertEquals('Austin, TX', $query['location']);
    $this->assertEquals('1', $query['no_cache']);
  }

  public function testArrayConstructorDefaultsEngineToGoogle()
  {
    $client = new Client(['api_key' => 'secret']);
    $this->assertEquals('google', $client->getEngine());
  }

  public function testArrayConstructorRejectsEmptyEngine()
  {
    $this->expectException(SerpApiException::class);
    $this->expectExceptionMessage('engine must be present');
    new Client(['api_key' => 'secret', 'engine' => '']);
  }

  public function testConfigurationInParamsArgumentIsHonored()
  {
    $client = new Client('secret', 'google', 120, ['timeout' => 15, 'hl' => 'en']);

    $this->assertEquals(15, $client->getTimeout());
    $this->assertEquals(['hl' => 'en'], $client->getParams());
  }

  public function testPositionalConstructorRemainsSupported()
  {
    $client = new Client('secret', 'bing', 30);

    $this->assertEquals('secret', $client->getApiKey());
    $this->assertEquals('bing', $client->getEngine());
    $this->assertEquals(30, $client->getTimeout());
  }

  public function testMissingEnvironmentKeyIsTreatedAsEmpty()
  {
    // getenv() returns false for unset variables.
    $client = new Client(false);
    $this->assertEquals('', $client->getApiKey());
  }

  public function testClientTimeoutIsUsedByDefault()
  {
    $client = new RecordingClient('secret', 'google', 30);
    $client->search(['q' => 'coffee']);

    $this->assertEquals([30], $client->timeouts);
  }

  public function testPerCallTimeoutAppliesToThatRequestOnly()
  {
    $client = new RecordingClient('secret', 'google', 30);
    $client->search(['q' => 'coffee', 'timeout' => 5]);
    $this->assertArrayNotHasKey('timeout', $client->lastQuery());

    $client->search(['q' => 'coffee']);
    $this->assertEquals([5, 30], $client->timeouts);
  }
}
