<?php

namespace SerpApi\Tests;

use SerpApi\Client;
use PHPUnit\Framework\TestCase;

abstract class SerpApiTestCase extends TestCase
{
  /** @var string|null */
  protected $apiKey;

  protected function setUp(): void
  {
    parent::setUp();
    $resolved = $this->resolveApiKey();

    if ($resolved === null && $this->requiresApiKey()) {
      $this->markTestSkipped('SERPAPI_KEY is not set');
      return;
    }

    $this->apiKey = $resolved;
  }

  protected function requiresApiKey(): bool
  {
    return true;
  }

  protected function resolveApiKey(): ?string
  {
    foreach (['SERPAPI_KEY', 'API_KEY'] as $name) {
      $env = $_ENV[$name] ?? null;

      if (!empty($env)) {
        return $env;
      }

      $value = getenv($name);
      if (!empty($value)) {
        return $value;
      }
    }

    return null;
  }

  protected function serpApiClient(string $engine = 'google'): Client
  {
    return new Client($this->apiKey ?? '', $engine);
  }

  protected function assertResponseHasProperty(object $response, string $property, string $message = ''): void
  {
    if (method_exists($this, 'assertObjectHasProperty')) {
      $this->assertObjectHasProperty($property, $response, $message);
      return;
    }

    $this->assertTrue(
      property_exists($response, $property),
      $message ?: "Failed asserting that object has property '{$property}'"
    );
  }
}
