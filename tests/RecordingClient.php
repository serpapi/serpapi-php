<?php

namespace SerpApi\Tests;

use SerpApi\Client;

/**
 * Client double that records outgoing requests and replays a canned
 * response, so request building and response handling can be tested
 * without an API key or network access.
 */
class RecordingClient extends Client
{
  /** @var array<int, string> */
  public $urls = [];

  /** @var array<int, int> */
  public $timeouts = [];

  /** @var array<int, array<string, string|\CURLFile>|null> Form fields of each request, null for a GET */
  public $forms = [];

  /** @var array<int, string> Contents of uploaded files, read while the request was in flight */
  public $uploads = [];

  /** @var string */
  private $body = '{"search_metadata":{"id":"abc123","status":"Success"}}';

  /** @var int */
  private $httpCode = 200;

  public function willReturn(string $body, int $httpCode = 200): self
  {
    $this->body = $body;
    $this->httpCode = $httpCode;

    return $this;
  }

  protected function request(string $url, int $timeout, ?array $form = null): array
  {
    $this->urls[] = $url;
    $this->timeouts[] = $timeout;
    $this->forms[] = $form;

    foreach ($form ?? [] as $field) {
      if ($field instanceof \CURLFile) {
        $this->uploads[] = (string) file_get_contents($field->getFilename());
      }
    }

    return [
      'response' => $this->body,
      'http_code' => $this->httpCode,
      'curl_error' => '',
    ];
  }

  /**
   * Query parameters of the most recent request.
   *
   * @return array<string, mixed>
   */
  public function lastQuery(): array
  {
    $url = (string) end($this->urls);
    parse_str((string) parse_url($url, PHP_URL_QUERY), $query);

    return $query;
  }

  public function lastPath(): string
  {
    $url = (string) end($this->urls);

    return (string) parse_url($url, PHP_URL_PATH);
  }

  /**
   * Form fields of the most recent request.
   *
   * @return array<string, string|\CURLFile>
   */
  public function lastForm(): array
  {
    return end($this->forms) ?: [];
  }
}
