<?php

namespace SerpApi\Tests;

class ExampleSearchGoogleImmersiveProductTest extends SerpApiTestCase
{
  public function testResultExists()
  {
    // A page_token identifies a live product offer, so take one from
    // Google Shopping rather than hardcoding a token that will go stale.
    $shopping = $this->serpApiClient('google_shopping');
    $search = $shopping->search(['q' => 'coffee maker']);

    $pageToken = null;
    foreach ($search->shopping_results ?? [] as $product) {
      if (isset($product->immersive_product_page_token)) {
        $pageToken = $product->immersive_product_page_token;
        break;
      }
    }

    if ($pageToken === null) {
      $this->markTestSkipped('Google Shopping returned no immersive_product_page_token');
    }

    $client = $this->serpApiClient('google_immersive_product');
    $response = $client->search(['page_token' => $pageToken]);
    $this->assertResponseHasProperty(
      $response,
      'product_results',
      'Error on `google_immersive_product` engine: no `product_results`'
    );
  }
}
