<?php

namespace SerpApi\Tests;

class ExampleSearchGoogleLocalServicesTest extends SerpApiTestCase
{
  /** @var array<string, string> */
  private $searchParams;

  protected function setUp(): void
  {
    parent::setUp();
    $this->searchParams = [
      'engine' => 'google_local_services',
      'q' => 'electrician',
      'data_cid' => '6745062158417646970',
    ];
  }

  public function testResultExists()
  {
    $client = $this->serpApiClient();
    $response = $client->search($this->searchParams);
    $this->assertResponseHasProperty($response, 'local_ads', 'Error on `google_local_services` engine: no `local_ads`');
  }
}
