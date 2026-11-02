<?php

namespace SerpApi\Tests;

class ClientIntegrationTest extends SerpApiTestCase
{
  public function testMarkdownSearchReturnsContent()
  {
    $response = $this->serpApiClient()->md([
      'q' => 'Coffee',
      'location' => 'Austin,Texas',
    ]);

    $this->assertStringStartsWith('---', $response);
    $this->assertStringContainsString('## Organic Results', $response);
  }
}
