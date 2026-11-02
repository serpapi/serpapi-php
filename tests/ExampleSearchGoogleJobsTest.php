<?php

namespace SerpApi\Tests;

class ExampleSearchGoogleJobsTest extends SerpApiTestCase
{
  /** @var array<string, string> */
  private $searchParams;

  protected function setUp(): void
  {
    parent::setUp();
    $this->searchParams = [
      'engine' => 'google_jobs',
      'q' => 'coffee',
    ];
  }

  public function testResultExists()
  {
    $client = $this->serpApiClient();
    $response = $client->search($this->searchParams);
    $this->assertResponseHasProperty($response, 'jobs_results', 'Error on `google_jobs` engine: no `jobs_results`');
  }
}
