<?php

namespace SerpApi\Tests;

use SerpApi\Client;

/**
 * Client that sends requests through the real cURL code path to a local
 * file:// URL, exercising connection handling without network access.
 */
class LocalFileClient extends Client
{
  public function fetch(): string
  {
    return (string) $this->request('file://' . __FILE__, 5)['response'];
  }
}
