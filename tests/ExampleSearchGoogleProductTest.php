<?php

namespace SerpApi\Tests;

class ExampleSearchGoogleProductTest extends SerpApiTestCase
{
  public function testResultExists()
  {
    // Product ids disappear when listings are removed, so take one from
    // Google Shopping rather than hardcoding it.
    $shopping = $this->serpApiClient('google_shopping');
    $search = $shopping->search(['q' => 'coffee maker']);

    $productId = null;
    foreach ($search->shopping_results ?? [] as $product) {
      if (isset($product->product_id)) {
        $productId = $product->product_id;
        break;
      }
    }

    if ($productId === null) {
      $this->markTestSkipped('Google Shopping returned no product_id');
    }

    $client = $this->serpApiClient('google_product');
    $response = $client->search(['product_id' => $productId]);
    $this->assertResponseHasProperty(
      $response,
      'product_results',
      'Error on `google_product` engine: no `product_results`'
    );
  }
}
