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

  /** @var bool Keep one connection open and reuse it across requests */
  private $persistent = true;

  /** @var resource|\CurlHandle|null Shared cURL handle: a resource on PHP 7, a CurlHandle on PHP 8 */
  private $handle;

  /**
   * Accepts positional arguments or, like the Ruby client, a single array
   * mixing configuration with default search parameters:
   *
   *   new Client(['api_key' => '...', 'engine' => 'google', 'hl' => 'en'])
   *
   * `api_key`, `engine`, `timeout` and `persistent` configure the client
   * and take precedence over the positional arguments. Every other key
   * becomes a default search parameter, sent with every request unless
   * overridden per call.
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
    $persistent = self::pull($params, 'persistent', true);

    if (empty($engine)) {
      throw new SerpApiException('engine must be present');
    }

    $this->apiKey = (string) $apiKey;
    $this->engine = (string) $engine;
    $this->timeout = (int) $timeout;
    $this->persistent = (bool) $persistent;
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
   * Whether the client reuses one connection across requests.
   */
  public function isPersistent(): bool
  {
    return $this->persistent;
  }

  /**
   * Close the shared connection. Safe to call more than once; the client
   * stays usable and reconnects on the next request.
   */
  public function close(): void
  {
    if ($this->hasOpenHandle()) {
      self::closeHandle($this->handle);
    }

    $this->handle = null;
  }

  public function __destruct()
  {
    $this->close();
  }

  /**
   * A clone opens its own connection rather than sharing, and later
   * closing, the original's.
   */
  public function __clone()
  {
    $this->handle = null;
  }

  /**
   * cURL handles cannot be serialized; the connection is reopened on the
   * first request after unserializing.
   *
   * @return array<string, mixed>
   */
  public function __serialize(): array
  {
    $data = get_object_vars($this);
    unset($data['handle']);

    return $data;
  }

  /**
   * @param array<string, mixed> $data
   */
  public function __unserialize(array $data): void
  {
    foreach ($data as $property => $value) {
      $this->{$property} = $value;
    }
  }

  /**
   * Human readable representation with the API key masked.
   */
  public function inspect(): string
  {
    return sprintf(
      '#<%s @engine=%s @timeout=%d @persistent=%s @api_key=%s>',
      static::class,
      $this->engine,
      $this->timeout,
      $this->persistent ? 'true' : 'false',
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
      'persistent' => $this->persistent,
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
   * Run a search and return decoded JSON as an associative array.
   *
   * @param array<string, mixed> $params
   * @return array<string, mixed>
   * @throws SerpApiException
   */
  public function searchArray(array $params = []): array
  {
    return $this->get('/search', 'json', $params, true);
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
   * Upload an image using the Image API.
   *
   * The returned `image_id` can be passed to Search API engines that accept
   * uploaded images, such as Google Lens. Image IDs expire after 10 minutes.
   *
   * The upload carries the same parameters as any other request, including
   * default search parameters set on the client, plus the image.
   *
   * @param string|resource $image Path to a JPG, PNG, or WebP file, or a readable stream;
   *                               seekable streams are uploaded from the beginning
   * @param array<string, mixed> $params Request parameters, such as `api_key` or `timeout`
   * @throws SerpApiException
   */
  public function uploadImage($image, array $params = []): object
  {
    if (is_resource($image) && get_resource_type($image) === 'stream') {
      $copy = self::copyToTempFile($image);

      try {
        $path = stream_get_meta_data($copy)['uri'];
        return $this->post('/image', $params, ['image' => new \CURLFile($path, '', 'image')]);
      } finally {
        fclose($copy);
      }
    }

    if (!is_string($image) || $image === '') {
      throw new SerpApiException('image must be a file path or a stream');
    }

    if (!is_file($image) || !is_readable($image)) {
      throw new SerpApiException("image is not a readable file: {$image}");
    }

    // Without a post name, cURL would send the full local path as the file name.
    return $this->post('/image', $params, ['image' => new \CURLFile($image, '', basename($image))]);
  }

  /**
   * cURL uploads files by path, and CURLStringFile requires PHP 8.1, so a
   * stream is uploaded from a temporary copy that is deleted once closed.
   *
   * @param resource $stream
   * @return resource
   * @throws SerpApiException
   */
  private static function copyToTempFile($stream)
  {
    $copy = tmpfile();
    if ($copy === false) {
      throw new SerpApiException('Failed to create a temporary file for the image');
    }

    if (stream_get_meta_data($stream)['seekable']) {
      rewind($stream);
    }

    if (stream_copy_to_stream($stream, $copy) === false || !fflush($copy)) {
      fclose($copy);
      throw new SerpApiException('Failed to read the image stream');
    }

    return $copy;
  }

  /**
   * Retrieve search result from the Search Archive API.
   *
   * @return object|string
   * @phpstan-return ($format is 'json' ? object : string)
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
   * @param bool $assoc Decode JSON objects to associative arrays instead of stdClass
   * @return object|array<int|string, mixed>|string
   * @throws SerpApiException
   */
  private function get(string $endpoint, string $format = 'json', array $params = [], bool $assoc = false)
  {
    return $this->send($endpoint, $format, $params, $assoc);
  }

  /**
   * Send a multipart/form-data POST. The request parameters travel as form
   * fields next to the files instead of in the URL.
   *
   * @param array<string, mixed> $params
   * @param array<string, \CURLFile> $files
   * @throws SerpApiException
   */
  private function post(string $endpoint, array $params, array $files): object
  {
    return $this->send($endpoint, 'json', $params, false, $files);
  }

  /**
   * @param array<string, mixed> $params
   * @param bool $assoc Decode JSON objects to associative arrays instead of stdClass
   * @param array<string, \CURLFile>|null $files Files to POST, or null for a GET
   * @return object|array<int|string, mixed>|string
   * @throws SerpApiException
   */
  private function send(string $endpoint, string $format, array $params, bool $assoc, ?array $files = null)
  {
    if (!in_array($format, ['json', 'html', 'md'], true)) {
      throw new SerpApiException("Unsupported format '$format'. Expected 'html', 'json', or 'md'.");
    }

    $apiKey = $params['api_key'] ?? $this->apiKey;

    $requiresKey = strpos($endpoint, '/locations') !== 0;
    if ($requiresKey && empty($apiKey)) {
      throw new SerpApiException('api_key must be present');
    }

    if (array_key_exists('persistent', $params)) {
      throw new SerpApiException('persistent can only be set on the client');
    }

    $timeout = (int) self::pull($params, 'timeout', $this->timeout);

    $query = $this->buildQuery($params, (string) $apiKey, $format);

    if ($files === null) {
      $requestResult = $this->request(self::BASE_URL . $endpoint . '?' . http_build_query($query), $timeout);
    } else {
      $form = array_merge(self::formFields($query), $files);
      $requestResult = $this->request(self::BASE_URL . $endpoint, $timeout, $form);
    }

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

    $decoded = json_decode($response, $assoc);
    if ($decoded === null && json_last_error() !== JSON_ERROR_NONE) {
      $this->raiseParserError($httpCode, $endpoint, $query);
    }

    $error = self::dig($decoded, 'error');
    $serpApiError = is_scalar($error) ? (string) $error : null;

    $id = self::dig(self::dig($decoded, 'search_metadata'), 'id');
    $searchId = is_scalar($id) ? (string) $id : null;

    if ($httpCode === 200) {
      if ($serpApiError !== null) {
        $this->raiseHttpError($httpCode, $endpoint, $query, $serpApiError, $searchId, 'json');
      }

      return $decoded;
    }

    $this->raiseHttpError($httpCode, $endpoint, $query, $serpApiError, $searchId, 'json');
  }

  /**
   * Read a key from decoded JSON, whether it was decoded to objects or
   * associative arrays.
   *
   * @param mixed $data
   * @return mixed
   */
  private static function dig($data, string $key)
  {
    if (is_array($data)) {
      return $data[$key] ?? null;
    }

    if (is_object($data)) {
      return $data->{$key} ?? null;
    }

    return null;
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
   * Encode request parameters as form fields exactly as they would be in a
   * query string. cURL would otherwise send false as an empty string and
   * nested arrays as "Array".
   *
   * @param array<string, mixed> $query
   * @return array<string, string>
   */
  private static function formFields(array $query): array
  {
    $fields = [];

    foreach (explode('&', http_build_query($query)) as $pair) {
      if ($pair === '') {
        continue;
      }

      $parts = explode('=', $pair, 2);
      $fields[urldecode($parts[0])] = urldecode($parts[1] ?? '');
    }

    return $fields;
  }

  /**
   * Perform the HTTP request: a GET, or a multipart/form-data POST when
   * form fields are given.
   *
   * @internal Seam for tests to observe requests without network access;
   *           not covered by the backwards compatibility promise.
   *
   * @param array<string, string|\CURLFile>|null $form
   * @return array{response: string|false, http_code: int, curl_error: string}
   * @throws SerpApiException
   */
  protected function request(string $url, int $timeout, ?array $form = null): array
  {
    $ch = $this->acquireHandle();

    try {
      $options = [
        CURLOPT_URL => $url,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_USERAGENT => 'serpapi-php/' . self::VERSION,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_TIMEOUT => $timeout,
        CURLOPT_HTTPHEADER => [],
      ];

      // A shared handle keeps the method and headers of the previous request.
      if ($form === null) {
        $options[CURLOPT_HTTPGET] = true;
      } else {
        $options[CURLOPT_POSTFIELDS] = $form;
        // libcurl before 7.69 sends `Expect: 100-continue` with bodies over
        // 1 KB. SerpApi does not answer it, stalling each upload for a second.
        $options[CURLOPT_HTTPHEADER] = ['Expect:'];
      }

      $isConfigured = curl_setopt_array($ch, $options);

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
      if (!$this->persistent) {
        self::closeHandle($ch);
      }
    }
  }

  /**
   * The shared handle in persistent mode, otherwise a new one per request.
   *
   * @return resource|\CurlHandle
   * @throws SerpApiException
   */
  private function acquireHandle()
  {
    if ($this->persistent && $this->hasOpenHandle()) {
      return $this->handle;
    }

    $ch = curl_init();
    if ($ch === false) {
      throw new SerpApiException('Failed to initialize cURL handle');
    }

    if ($this->persistent) {
      $this->handle = $ch;
    }

    return $ch;
  }

  /**
   * False for a closed PHP 7 resource too, and for the integer a resource
   * turns into when serialized on PHP 7.2 and 7.3.
   *
   * @phpstan-assert-if-true !null $this->handle
   */
  private function hasOpenHandle(): bool
  {
    return is_resource($this->handle) || $this->handle instanceof \CurlHandle;
  }

  /**
   * @param resource|\CurlHandle $handle
   */
  private static function closeHandle($handle): void
  {
    // curl_close() is a no-op since PHP 8.0 and deprecated in 8.5; the
    // handle is freed once nothing references it.
    if (PHP_VERSION_ID < 80500) {
      curl_close($handle);
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
      'JSON parse error: ' . json_last_error_msg() . ' from url: ' . self::BASE_URL . $endpoint,
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
