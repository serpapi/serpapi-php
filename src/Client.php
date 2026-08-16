<?php

namespace SerpApi;

class Client {
  const VERSION = '1.1.0';
  const BASE_URL = 'https://serpapi.com';
  const DEFAULT_TIMEOUT = 120;

  /** Client identifier reported to SerpApi for usage statistics. */
  const SOURCE = 'serpapi-php:' . self::VERSION;

  /** @var string */
  private $api_key;

  /** @var string */
  private $engine;

  /** @var int */
  private $timeout;

  /** @var array<string, mixed> Search parameters applied to every request */
  private $params = [];

  /** @var bool Reuse a single cURL handle, keeping the connection alive between requests */
  private $persistent = true;

  /** @var resource|\CurlHandle|null Shared cURL handle (a resource on PHP 7, a CurlHandle on PHP 8) */
  private $handle = null;

  /** @var bool Decode JSON responses to associative arrays instead of stdClass */
  private $assoc = false;

  /**
   * Client-only configuration keys, never forwarded to the API as search parameters.
   *
   * @var array<int, string>
   */
  private static $option_keys = ['api_key', 'engine', 'timeout', 'persistent', 'assoc'];

  /**
   * Client-only keys stripped from the query string of every request.
   *
   * @var array<int, string>
   */
  private static $client_only_keys = ['timeout', 'persistent', 'assoc'];

  /**
   * Accepts either positional arguments or, like the Ruby client, a single
   * associative array holding both configuration and default search parameters:
   *
   *   new Client(['api_key' => '...', 'engine' => 'google', 'hl' => 'en'])
   *
   * Any key that is not `api_key`, `engine` or `timeout` becomes a default
   * search parameter merged into every request, and can still be overridden
   * per call.
   *
   * @param string|array<string, mixed> $api_key  API key, or a full configuration array
   * @param string $engine
   * @param int $timeout  Request timeout in seconds
   * @param array<string, mixed> $params  Default search parameters
   * @throws SerpApiException
   */
  public function __construct($api_key = '', string $engine = 'google', int $timeout = self::DEFAULT_TIMEOUT, array $params = []) {
    if (is_array($api_key)) {
      $config = $api_key;
      $api_key = (string) $this->take($config, 'api_key', '');
      $engine = (string) $this->take($config, 'engine', $engine);
      $timeout = (int) $this->take($config, 'timeout', $timeout);
      $this->persistent = (bool) $this->take($config, 'persistent', true);
      $this->assoc = (bool) $this->take($config, 'assoc', false);
      $params = array_merge($config, $params);
    }

    if (empty($engine)) {
      throw new SerpApiException('engine must be present');
    }

    $this->api_key = (string) $api_key;
    $this->engine = $engine;
    $this->timeout = $timeout;
    $this->params = $this->without_option_keys($params);
  }

  /**
   * Pull a value out of a configuration array, removing it in the process.
   *
   * @param array<string, mixed> $config
   * @param mixed $default
   * @return mixed
   */
  private function take(array &$config, string $key, $default) {
    if (!array_key_exists($key, $config) || $config[$key] === null) {
      unset($config[$key]);
      return $default;
    }

    $value = $config[$key];
    unset($config[$key]);

    return $value;
  }

  /**
   * @param array<string, mixed> $params
   * @return array<string, mixed>
   */
  private function without_option_keys(array $params): array {
    foreach (self::$option_keys as $key) {
      unset($params[$key]);
    }

    return $params;
  }

  /**
   * Set the SerpApi API key.
   *
   * @param string $api_key
   * @throws SerpApiException
   */
  public function set_api_key(string $api_key): void {
    if (empty($api_key)) {
      throw new SerpApiException('api_key must have a value');
    }

    $this->api_key = $api_key;
  }

  /**
   * Get the current API key.
   */
  public function get_api_key(): string {
    return $this->api_key;
  }

  /**
   * Get the current engine.
   */
  public function get_engine(): string {
    return $this->engine;
  }

  /**
   * Get the request timeout in seconds.
   */
  public function get_timeout(): int {
    return $this->timeout;
  }

  /**
   * Get the default search parameters applied to every request,
   * including `engine` and `api_key`.
   *
   * @return array<string, mixed>
   */
  public function get_params(): array {
    $params = ['engine' => $this->engine];

    if (!empty($this->api_key)) {
      $params['api_key'] = $this->api_key;
    }

    return array_merge($params, $this->params);
  }

  /**
   * Whether the client reuses a single connection across requests.
   */
  public function is_persistent(): bool {
    return $this->persistent;
  }

  /**
   * Whether JSON responses are decoded to associative arrays instead of stdClass.
   */
  public function is_assoc(): bool {
    return $this->assoc;
  }

  /**
   * Close the shared connection. Safe to call more than once; the client
   * stays usable and opens a new connection on the next request.
   */
  public function close(): void {
    if ($this->handle === null) {
      return;
    }

    if (PHP_VERSION_ID < 80500) {
      curl_close($this->handle);
    }

    $this->handle = null;
  }

  public function __destruct() {
    $this->close();
  }

  /**
   * Human readable representation with the API key masked.
   */
  public function inspect(): string {
    return sprintf(
      '#<%s @engine=%s @timeout=%d @persistent=%s @api_key=%s>',
      static::class,
      $this->engine,
      $this->timeout,
      $this->persistent ? 'true' : 'false',
      $this->masked_api_key()
    );
  }

  /**
   * Keeps `var_dump()` and debuggers from printing the API key in clear text.
   *
   * Note that `print_r()` and `var_export()` bypass this hook and read
   * properties directly; use `inspect()` when dumping a client on purpose.
   *
   * @return array<string, mixed>
   */
  public function __debugInfo(): array {
    return [
      'engine'     => $this->engine,
      'timeout'    => $this->timeout,
      'persistent' => $this->persistent,
      'assoc'      => $this->assoc,
      'api_key'    => $this->masked_api_key(),
      'params'     => $this->params,
    ];
  }

  /**
   * Show only the first and last 4 characters of the API key.
   */
  private function masked_api_key(): string {
    $length = strlen($this->api_key);

    if ($length === 0) {
      return '';
    }

    if ($length <= 8) {
      return '****';
    }

    return substr($this->api_key, 0, 4) . '****' . substr($this->api_key, -4);
  }

  /**
   * Run a search and return decoded JSON.
   *
   * @param array<string, mixed> $params
   * @return object|array<string, mixed>  stdClass, or an array when `assoc` is enabled
   * @throws SerpApiException
   */
  public function search(array $params = []) {
    return $this->get('/search', 'json', $params);
  }

  /**
   * Run a search and return raw HTML.
   *
   * @param array<string, mixed> $params
   * @throws SerpApiException
   */
  public function html(array $params = []): string {
    return $this->get('/search', 'html', $params);
  }

  /**
   * Get account information using Account API.
   *
   * @return object|array<string, mixed>  stdClass, or an array when `assoc` is enabled
   * @throws SerpApiException
   */
  public function account(?string $api_key = null) {
    $params = empty($api_key) ? [] : ['api_key' => $api_key];
    return $this->get('/account', 'json', $params);
  }

  /**
   * Get locations using Location API.
   *
   * @param array<string, mixed> $params
   * @return array<int, object|array<string, mixed>>
   * @throws SerpApiException
   */
  public function location(array $params = []): array {
    return $this->get('/locations.json', 'json', $params);
  }

  /**
   * Retrieve search result from the Search Archive API.
   *
   * @return object|string
   * @throws SerpApiException
   */
  public function search_archive(string $search_id, string $format = 'json') {
    if (empty($search_id)) {
      throw new SerpApiException('search_id must be present');
    }

    if (!in_array($format, ['json', 'html'], true)) {
      throw new SerpApiException('format must be json or html');
    }

    $safe_search_id = rawurlencode($search_id);
    return $this->get("/searches/{$safe_search_id}.{$format}", $format, []);
  }

  /**
   * @param array<string, mixed> $params
   * @return object|array<int|string, mixed>|string
   * @throws SerpApiException
   */
  private function get(string $endpoint, string $format = 'json', array $params = []) {
    if (!in_array($format, ['json', 'html'], true)) {
      throw new SerpApiException("Unsupported format '$format'. Expected 'html' or 'json'.");
    }

    $api_key = $params['api_key'] ?? $this->api_key;

    $requires_key = strpos($endpoint, '/locations') !== 0;
    if ($requires_key && empty($api_key)) {
      throw new SerpApiException('api_key must be present');
    }

    $query = $this->query($params, $api_key, $format);

    $url = self::BASE_URL . $endpoint . '?' . http_build_query($query);

    $request_result = $this->request($url);
    $response = $request_result['response'];
    $http_code = $request_result['http_code'];
    $curl_error = $request_result['curl_error'];

    if ($response === false) {
      throw new SerpApiException('cURL error: ' . $curl_error);
    }

    if ($format === 'html') {
      if ($http_code === 200) {
        return $response;
      }

      $this->raise_http_error($http_code, $endpoint, $query, null, null, 'html');
    }

    $assoc = isset($params['assoc']) ? (bool) $params['assoc'] : $this->assoc;

    $decoded = json_decode($response, $assoc);
    if ($decoded === null && json_last_error() !== JSON_ERROR_NONE) {
      $this->raise_parser_error($http_code, $endpoint, $query);
    }

    $error = $this->dig($decoded, 'error');
    $serpapi_error = is_string($error) ? $error : null;

    $metadata = $this->dig($decoded, 'search_metadata');
    $id = $this->dig($metadata, 'id');
    $search_id = ($id === null) ? null : (string) $id;

    if ($http_code === 200) {
      if ($serpapi_error !== null) {
        $this->raise_http_error($http_code, $endpoint, $query, $serpapi_error, $search_id, 'json');
      }

      return $decoded;
    }

    $this->raise_http_error($http_code, $endpoint, $query, $serpapi_error, $search_id, 'json');
  }

  /**
   * Read a key from a decoded response, which is an object or an
   * associative array depending on the `assoc` setting.
   *
   * @param mixed $data
   * @return mixed  null when absent
   */
  private function dig($data, string $key) {
    if (is_object($data)) {
      return $data->{$key} ?? null;
    }

    if (is_array($data)) {
      return $data[$key] ?? null;
    }

    return null;
  }

  /**
   * Build the query string parameters for a request.
   *
   * @param array<string, mixed> $params
   * @return array<string, mixed>
   */
  private function query(array $params, string $api_key, string $format): array {
    $default_query = [
      'engine'  => $this->engine,
      'source'  => self::SOURCE,
    ];

    if (!empty($api_key)) {
      $default_query['api_key'] = $api_key;
    }

    $query = array_merge($default_query, $this->params, $params);
    $query['output'] = $format;

    foreach (self::$client_only_keys as $key) {
      unset($query[$key]);
    }

    return array_filter($query, static function ($value) {
      return $value !== null;
    });
  }

  /**
   * @return array{response: string|false, http_code: int, curl_error: string}
   * @throws SerpApiException
   */
  private function request(string $url): array {
    $ch = $this->acquire_handle();

    try {
      $is_configured = curl_setopt_array($ch, [
        CURLOPT_URL            => $url,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_USERAGENT      => 'serpapi-php/' . self::VERSION,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_TIMEOUT        => $this->timeout,
      ]);

      if ($is_configured === false) {
        throw new SerpApiException('Failed to configure cURL options: ' . curl_error($ch));
      }

      $response = curl_exec($ch);
      $http_code = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
      $curl_error = curl_error($ch);

      return [
        'response' => $response,
        'http_code' => $http_code,
        'curl_error' => $curl_error,
      ];
    } finally {
      $this->release_handle($ch);
    }
  }

  /**
   * Return the shared handle in persistent mode, otherwise a fresh one.
   *
   * @return resource|\CurlHandle
   * @throws SerpApiException
   */
  private function acquire_handle() {
    if ($this->persistent && $this->handle !== null) {
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
   * Keep the shared handle open so the underlying connection is reused;
   * dispose of single use handles.
   *
   * @param resource|\CurlHandle $ch
   */
  private function release_handle($ch): void {
    if ($this->persistent && $ch === $this->handle) {
      return;
    }

    // curl_close() is a no-op since PHP 8.0 and deprecated in 8.5;
    // the handle is released by the garbage collector instead.
    if (PHP_VERSION_ID < 80500) {
      curl_close($ch);
    }
  }

  /**
   * @param array<string, mixed> $search_params
   * @return never
   * @throws SerpApiException
   */
  private function raise_http_error(
    int $response_status,
    string $endpoint,
    array $search_params,
    ?string $serpapi_error = null,
    ?string $search_id = null,
    string $decoder = 'json'
  ): void {
    $message = "HTTP request failed with status: {$response_status}";
    if ($serpapi_error !== null) {
      $message .= " error: {$serpapi_error}";
    }
    $message .= ' from url: ' . self::BASE_URL . $endpoint;
    $sanitized_search_params = $this->sanitize_search_params($search_params);

    throw new SerpApiException(
      $message,
      $serpapi_error,
      $sanitized_search_params,
      $response_status,
      $search_id,
      $decoder
    );
  }

  /**
   * @param array<string, mixed> $search_params
   * @return never
   * @throws SerpApiException
   */
  private function raise_parser_error(
    int $response_status,
    string $endpoint,
    array $search_params
  ): void {
    $sanitized_search_params = $this->sanitize_search_params($search_params);

    throw new SerpApiException(
      'JSON parse error: ' . json_last_error_msg() . ' on get url: ' . self::BASE_URL . $endpoint,
      null,
      $sanitized_search_params,
      $response_status,
      null,
      'json'
    );
  }

  /**
   * @param array<string, mixed> $search_params
   * @param array<int, string> $keys_to_remove
   * @return array<string, mixed>
   */
  private function sanitize_search_params(array $search_params, array $keys_to_remove = ['api_key']): array {
    foreach ($keys_to_remove as $key) {
      unset($search_params[$key]);
    }

    return $search_params;
  }
}
