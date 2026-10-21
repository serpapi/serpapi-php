<?php

namespace SerpApi\Tests;

use SerpApi\Client;

class ClientInspectTest extends SerpApiTestCase
{
  private const LONG_KEY = 'abcd1234secret5678wxyz';

  protected function requiresApiKey(): bool
  {
    return false;
  }

  public function testInspectDoesNotExposeTheApiKey()
  {
    $client = new Client(self::LONG_KEY);
    $this->assertStringNotContainsString(self::LONG_KEY, $client->inspect());
  }

  public function testInspectShowsFirstAndLastFourCharacters()
  {
    $client = new Client(self::LONG_KEY);
    $this->assertStringContainsString('@api_key=abcd****wxyz', $client->inspect());
  }

  public function testInspectReportsEngineAndTimeout()
  {
    $inspect = (new Client(self::LONG_KEY, 'bing', 30))->inspect();

    $this->assertStringContainsString('@engine=bing', $inspect);
    $this->assertStringContainsString('@timeout=30', $inspect);
  }

  public function testShortApiKeyIsFullyMasked()
  {
    $inspect = (new Client('abcdefgh'))->inspect();

    $this->assertStringNotContainsString('abcd', $inspect);
    $this->assertStringContainsString('@api_key=****', $inspect);
  }

  public function testMissingApiKeyRendersAsEmpty()
  {
    $this->assertStringEndsWith('@api_key=>', (new Client())->inspect());
  }

  public function testVarDumpDoesNotExposeTheApiKey()
  {
    $client = new Client(['api_key' => self::LONG_KEY, 'hl' => 'en']);

    ob_start();
    var_dump($client);
    $dump = (string) ob_get_clean();

    $this->assertStringNotContainsString(self::LONG_KEY, $dump);
    $this->assertStringContainsString('abcd****wxyz', $dump);
    $this->assertStringContainsString('"hl"', $dump);
  }

  public function testPrintRDoesNotExposeTheApiKey()
  {
    $dump = print_r(new Client(self::LONG_KEY), true);

    $this->assertStringNotContainsString(self::LONG_KEY, $dump);
    $this->assertStringContainsString('abcd****wxyz', $dump);
  }
}
