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

  /** @var array<string, mixed> Search parameters merged into every request */
  private $params = [];

  /**
   * Accepts positional arguments or, like the Ruby client, a single array
   * mixing configuration with default search parameters:
   *
   *   new Client(['api_key' => '...', 'engine' => 'google', 'hl' => 'en'])
   *
   * `api_key`, `engine` and `timeout` configure the client and take
   * precedence over the positional arguments. Every other key becomes a
   * default search parameter, sent with every request unless overridden
   * per call.
   *
   * @param string|array<string, mixed> $apiKey API key, or a configuration array
   * @param string $engine
   * @param int $timeout Request timeout in seconds
   * @param array<string, mixed> $params Default search parameters and configuration
   * @throws SerpApiException
   */
  public function __construct(
    $apiKey = '',
    string $engine = 'google',
    int $timeout = self::DEFAULT_TIMEOUT,
    array $params = []
  ) {
    if (is_array($apiKey)) {
      $params = array_merge($apiKey, $params);
      $apiKey = '';
    }

    $apiKey = self::pull($params, 'api_key', $apiKey);
    $engine = self::pull($params, 'engine', $engine);
    $timeout = self::pull($params, 'timeout', $timeout);

    if (empty($engine)) {
      throw new SerpApiException('engine must be present');
    }

    $this->apiKey = (string) $apiKey;
    $this->engine = (string) $engine;
    $this->timeout = (int) $timeout;
    $this->params = $params;
  }

  /**
   * Remove a key from an options array and return its value, or the
   * default when the key is absent or null.
   *
   * @param array<string, mixed> $options
   * @param mixed $default
   * @return mixed
   */
  private static function pull(array &$options, string $key, $default)
  {
    $value = $options[$key] ?? $default;
    unset($options[$key]);

    return $value;
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
   * Get the request timeout in seconds.
   */
  public function getTimeout(): int
  {
    return $this->timeout;
  }

  /**
   * Get the default search parameters sent with every request.
   *
   * @return array<string, mixed>
   */
  public function getParams(): array
  {
    return $this->params;
  }

  /**
   * Human readable representation with the API key masked.
   */
  public function inspect(): string
  {
    return sprintf(
      '#<%s @engine=%s @timeout=%d @api_key=%s>',
      static::class,
      $this->engine,
      $this->timeout,
      $this->maskedApiKey()
    );
  }

  /**
   * Keeps var_dump(), print_r() and debuggers from printing the API key in
   * clear text.
   *
   * var_export() and (array) casts read properties directly and bypass this
   * hook; use inspect() when dumping a client on purpose.
   *
   * @return array<string, mixed>
   */
  public function __debugInfo(): array
  {
    return [
      'engine' => $this->engine,
      'timeout' => $this->timeout,
      'apiKey' => $this->maskedApiKey(),
      'params' => $this->params,
    ];
  }

  /**
   * Reveal only the first and last 4 characters of the API key, and none
   * of a key too short for that to hide most of it.
   */
  private function maskedApiKey(): string
  {
    $length = strlen($this->apiKey);

    if ($length === 0) {
      return '';
    }

    if ($length <= 8) {
      return '****';
    }

    return substr($this->apiKey, 0, 4) . '****' . substr($this->apiKey, -4);
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

    $timeout = (int) self::pull($params, 'timeout', $this->timeout);

    $query = $this->buildQuery($params, (string) $apiKey, $format);

    $url = self::BASE_URL . $endpoint . '?' . http_build_query($query);

    $requestResult = $this->request($url, $timeout);
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

    // A null value drops a default search parameter for a single call.
    $overrides = array_filter(array_merge($this->params, $params), static function ($value) {
      return $value !== null;
    });

    $query = array_merge($query, $overrides);
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
