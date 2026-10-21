<?php

namespace SerpApi;

class Client
{
  public const VERSION = '1.0.0';
  public const BASE_URL = 'https://serpapi.com';
  public const DEFAULT_TIMEOUT = 120;

  /** Client identifier reported to SerpApi in the `source` parameter. */
  public const SOURCE = 'serpapi-php:' . self::VERSION;

  /** @var string */
  private $apiKey;

  /** @var string */
  private $engine;

  /** @var int */
  private $timeout;

  /**
   * @param string $apiKey
   * @param string $engine
   * @param int $timeout Request timeout in seconds
   * @throws SerpApiException
   */
  public function __construct(string $apiKey = '', string $engine = 'google', int $timeout = self::DEFAULT_TIMEOUT)
  {
    if (empty($engine)) {
      throw new SerpApiException('engine must be present');
    }

    $this->apiKey = $apiKey;
    $this->engine = $engine;
    $this->timeout = $timeout;
  }

  /**
   * Set the SerpApi API key.
   *
   * @param string $apiKey
   * @throws SerpApiException
   */
  public function setApiKey(string $apiKey): void
  {
    if (empty($apiKey)) {
      throw new SerpApiException('api_key must have a value');
    }

    $this->apiKey = $apiKey;
  }

  /**
   * Get the current API key.
   */
  public function getApiKey(): string
  {
    return $this->apiKey;
  }

  /**
   * Get the current engine.
   */
  public function getEngine(): string
  {
    return $this->engine;
  }

  /**
   * Run a search and return decoded JSON.
   *
   * @param array<string, mixed> $params
   * @throws SerpApiException
   */
  public function search(array $params = []): object
  {
    return $this->get('/search', 'json', $params);
  }

  /**
   * Run a search and return Markdown optimized for LLMs and AI agents.
   *
   * @param array<string, mixed> $params
   * @throws SerpApiException
   */
  public function md(array $params = []): string
  {
    return $this->get('/search.md', 'md', $params);
  }

  /**
   * Run a search and return raw HTML.
   *
   * @param array<string, mixed> $params
   * @throws SerpApiException
   */
  public function html(array $params = []): string
  {
    return $this->get('/search', 'html', $params);
  }

  /**
   * Get account information using Account API.
   *
   * @throws SerpApiException
   */
  public function account(?string $apiKey = null): object
  {
    $params = empty($apiKey) ? [] : ['api_key' => $apiKey];
    return $this->get('/account', 'json', $params);
  }

  /**
   * Get locations using Location API.
   *
   * @param array<string, mixed> $params
   * @return array<int, object>
   * @throws SerpApiException
   */
  public function location(array $params = []): array
  {
    return $this->get('/locations.json', 'json', $params);
  }

  /**
   * Retrieve search result from the Search Archive API.
   *
   * @return object|string
   * @throws SerpApiException
   */
  public function searchArchive(string $searchId, string $format = 'json')
  {
    if (empty($searchId)) {
      throw new SerpApiException('search_id must be present');
    }

    if (!in_array($format, ['json', 'html', 'md'], true)) {
      throw new SerpApiException('format must be json, html, or md');
    }

    $safeSearchId = rawurlencode($searchId);
    return $this->get("/searches/{$safeSearchId}.{$format}", $format, []);
  }

  /**
   * @param array<string, mixed> $params
   * @return object|array<int|string, mixed>|string
   * @throws SerpApiException
   */
  private function get(string $endpoint, string $format = 'json', array $params = [])
  {
    if (!in_array($format, ['json', 'html', 'md'], true)) {
      throw new SerpApiException("Unsupported format '$format'. Expected 'html', 'json', or 'md'.");
    }

    $apiKey = $params['api_key'] ?? $this->apiKey;

    $requiresKey = strpos($endpoint, '/locations') !== 0;
    if ($requiresKey && empty($apiKey)) {
      throw new SerpApiException('api_key must be present');
    }

    $query = $this->buildQuery($params, (string) $apiKey, $format);

    $url = self::BASE_URL . $endpoint . '?' . http_build_query($query);

    $requestResult = $this->request($url, $this->timeout);
    $response = $requestResult['response'];
    $httpCode = $requestResult['http_code'];
    $curlError = $requestResult['curl_error'];

    if ($response === false) {
      throw new SerpApiException('cURL error: ' . $curlError);
    }

    if (in_array($format, ['html', 'md'], true)) {
      if ($httpCode === 200) {
        return $response;
      }

      $this->raiseHttpError($httpCode, $endpoint, $query, null, null, $format);
    }

    $decoded = json_decode($response);
    if ($decoded === null && json_last_error() !== JSON_ERROR_NONE) {
      $this->raiseParserError($httpCode, $endpoint, $query);
    }

    $serpApiError = (is_object($decoded) && isset($decoded->error)) ? $decoded->error : null;
    $searchId = (is_object($decoded) && isset($decoded->search_metadata->id))
      ? (string) $decoded->search_metadata->id
      : null;

    if ($httpCode === 200) {
      if ($serpApiError !== null) {
        $this->raiseHttpError($httpCode, $endpoint, $query, $serpApiError, $searchId, 'json');
      }

      return $decoded;
    }

    $this->raiseHttpError($httpCode, $endpoint, $query, $serpApiError, $searchId, 'json');
  }

  /**
   * Assemble the query string parameters for a request.
   *
   * @param array<string, mixed> $params
   * @return array<string, mixed>
   */
  private function buildQuery(array $params, string $apiKey, string $format): array
  {
    $query = [
      'engine' => $this->engine,
      'source' => self::SOURCE,
    ];

    if (!empty($apiKey)) {
      $query['api_key'] = $apiKey;
    }

    $query = array_merge($query, $params);
    $query['output'] = $format;

    return $query;
  }

  /**
   * Perform the HTTP GET request.
   *
   * @internal Seam for tests to observe requests without network access;
   *           not covered by the backwards compatibility promise.
   *
   * @return array{response: string|false, http_code: int, curl_error: string}
   * @throws SerpApiException
   */
  protected function request(string $url, int $timeout): array
  {
    $ch = curl_init();
    if ($ch === false) {
      throw new SerpApiException('Failed to initialize cURL handle');
    }

    try {
      $isConfigured = curl_setopt_array($ch, [
        CURLOPT_URL => $url,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_USERAGENT => 'serpapi-php/' . self::VERSION,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_TIMEOUT => $timeout,
      ]);

      if ($isConfigured === false) {
        throw new SerpApiException('Failed to configure cURL options: ' . curl_error($ch));
      }

      $response = curl_exec($ch);
      $httpCode = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
      $curlError = curl_error($ch);

      return [
        'response' => $response,
        'http_code' => $httpCode,
        'curl_error' => $curlError,
      ];
    } finally {
      if (PHP_VERSION_ID < 80500) {
        curl_close($ch);
      }

      $ch = null;
    }
  }

  /**
   * @param array<string, mixed> $searchParams
   * @return never
   * @throws SerpApiException
   */
  private function raiseHttpError(
    int $responseStatus,
    string $endpoint,
    array $searchParams,
    ?string $serpApiError = null,
    ?string $searchId = null,
    string $decoder = 'json'
  ): void {
    $message = "HTTP request failed with status: {$responseStatus}";
    if ($serpApiError !== null) {
      $message .= " error: {$serpApiError}";
    }
    $message .= ' from url: ' . self::BASE_URL . $endpoint;
    $sanitizedSearchParams = $this->sanitizeSearchParams($searchParams);

    throw new SerpApiException(
      $message,
      $serpApiError,
      $sanitizedSearchParams,
      $responseStatus,
      $searchId,
      $decoder
    );
  }

  /**
   * @param array<string, mixed> $searchParams
   * @return never
   * @throws SerpApiException
   */
  private function raiseParserError(
    int $responseStatus,
    string $endpoint,
    array $searchParams
  ): void {
    $sanitizedSearchParams = $this->sanitizeSearchParams($searchParams);

    throw new SerpApiException(
      'JSON parse error: ' . json_last_error_msg() . ' on get url: ' . self::BASE_URL . $endpoint,
      null,
      $sanitizedSearchParams,
      $responseStatus,
      null,
      'json'
    );
  }

  /**
   * @param array<string, mixed> $searchParams
   * @param array<int, string> $keysToRemove
   * @return array<string, mixed>
   */
  private function sanitizeSearchParams(array $searchParams, array $keysToRemove = ['api_key']): array
  {
    foreach ($keysToRemove as $key) {
      unset($searchParams[$key]);
    }

    return $searchParams;
  }
}
