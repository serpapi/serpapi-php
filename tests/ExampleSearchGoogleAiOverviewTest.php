<?php

namespace SerpApi\Tests;

class ExampleSearchGoogleAiOverviewTest extends SerpApiTestCase
{
  public function testResultExists()
  {
    // A page_token expires a minute after the Google search that returned
    // it, so fetch a fresh one and bypass the SerpApi cache.
    $google = $this->serpApiClient('google');
    $search = $google->search(['q' => 'what is coffee', 'no_cache' => true]);

    $pageToken = $search->ai_overview->page_token ?? null;
    if ($pageToken === null) {
      $this->markTestSkipped('Google returned no AI Overview page_token for this query');
    }

    $client = $this->serpApiClient('google_ai_overview');
    $response = $client->search(['page_token' => $pageToken]);
    $this->assertResponseHasProperty(
      $response,
      'ai_overview',
      'Error on `google_ai_overview` engine: no `ai_overview`'
    );
  }
}
