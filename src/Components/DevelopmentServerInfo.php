<?php
declare(strict_types=1);

namespace TrayDigita\WP\Headless\Resource\Components;

use TrayDigita\WP\Headless\Resource\Utils\Callback;
use function file_exists;
use function in_array;
use function is_string;
use function strtolower;

/**
 * @property-read string|null $host
 * @property-read int|null $port
 * @property-read string|null $url
 * @property-read string|null $environment
 * @property-read array{
 *     host: ?string,
 *     port: ?int,
 *     running: bool,
 * } $status
 * @property-read bool $running
 */
class DevelopmentServerInfo
{
    public const MAX_FILE_SIZE = 1024 * 512; // 512 KB

    /**
     * @var array{
     *     host: string,
     *     port: int,
     *     url: string,
     *     environment: "development"|"production"|null,
     * }|false|null $data
     */
    private array|false|null $data;

    /**
     * @var array{
     *     host: ?string,
     *     port: ?int,
     *     running: bool,
     * }
     */
    private array $status;

    private bool $validUrl;
    /**
     * DevelopmentServerInfo constructor.
     *
     * @param Container $container The container instance.
     */
    public function __construct(
        public readonly Container $container
    ) {
    }

    /**
     * Get the development server data from the .server.json file.
     *
     * @return array{
     *     host: string,
     *     port: int,
     *     url: string,
     *     environment: "development"|"production"|null,
     * }&array<string, mixed>|null Returns the development server data as an associative array, or null if not available.
     */
    public function getData(): ?array
    {
        if (isset($this->data)) {
            return $this->data ?: null;
        }
        $this->data = false;
        if (!$this->container->development) {
            return null;
        }
        $server_file = $this->container->server_file;
        if (!file_exists($server_file)) {
            return null;
        }
        $size = Callback::apply('filesize', $server_file);
        if (!$size || $size <= 0 || $size > self::MAX_FILE_SIZE) {
            return null;
        }
        $content = Callback::apply('file_get_contents', $server_file);
        if (!is_string($content) || empty($content)) {
            return null;
        }
        $json = json_decode($content, true);
        if (!is_array($json)) {
            return null;
        }
        $host = $json['host'] ?? null;
        $port = $json['port'] ?? null;
        $url = $json['url'] ?? null;
        $environment = $json['environment'] ?? null;
        if (!is_string($host) || !is_int($port) || !is_string($url)) {
            return null;
        }
        $json['environment'] = !is_string($environment) ? null : strtolower($environment);
        $this->data = $json;
        return $this->data;
    }

    /**
     * Check if the development server URI is valid.
     *
     * @return bool Returns true if the URI is valid, false otherwise.
     */
    public function isValidUri() : bool
    {
        if (isset($this->validUrl)) {
            return $this->validUrl;
        }
        $this->validUrl = false;
        $configPort = $this->getPort();
        $configHost = $this->getHost();
        $url = $this->getUrl();
        if (!$url || !$configHost || !$configPort) {
            return false;
        }
        $parsed = parse_url($url);
        if (!is_array($parsed)) {
            return false;
        }
        $scheme = $parsed['scheme'] ?? null;
        $host = $parsed['host'] ?? null;
        $port = $parsed['port'] ?? null;
        return $this->validUrl = !($host !== $configHost || $port !== $configPort || !in_array($scheme, ['http', 'https'], true));
    }

    public function getUrl() : ?string
    {
        $data = $this->getData()??[];
        $url = $data['url'] ?? null;
        return is_string($url) ? $url : null;
    }

    /**
     * Get the host of the development server.
     *
     * @return string|null Returns the host as a string, or null if not available.
     */
    public function getHost() : ?string
    {
        $data = $this->getData()??[];
        $host = $data['host'] ?? null;
        return is_string($host) ? $host : null;
    }

    /**
     * Get the port of the development server.
     *
     * @return int|null Returns the port as an integer, or null if not available.
     */
    public function getPort() : ?int
    {
        $data = $this->getData()??[];
        $port = $data['port'] ?? null;
        return is_int($port) ? $port : null;
    }

    /**
     * Get the environment of the development server.
     *
     * @return string|null Returns the environment as a string ("development" or "production"), or null if not available.
     */
    public function getEnvironment() : ?string
    {
        $data = $this->getData()??[];
        $environment = $data['environment'] ?? null;
        return is_string($environment) ? $environment : null;
    }

    /**
     * Get the status of the development server.
     *
     * @return array Returns an array containing the host, port, and running status.
     */
    public function getStatus(): array
    {
        if (isset($this->status)) {
            return $this->status;
        }
        $this->status = [
            'host' => $this->getHost(),
            'port' => $this->getPort(),
            'url' => $this->getUrl(),
            'running' => false,
        ];
        if (!$this->isValidUri()) {
            return $this->status;
        }
        if (!$this->status['host'] || !$this->status['port']) {
            return $this->status;
        }
        $running = $this->testConnection($this->status['host'], $this->status['port']);
        $this->status['running'] = $running;
        return $this->status;
    }

    /**
     * Check if the development server is running.
     *
     * @return bool Returns true if the development server is running, false otherwise.
     */
    public function isRunning() : bool
    {
        return $this->getStatus()['running'];
    }

    /**
     * Test the connection to the development server.
     *
     * @param string $host The host of the development server.
     * @param int $port The port of the development server.
     * @param float|int $timeout The timeout in seconds for the connection test (default is 1 second).
     * @return bool Returns true if the connection is successful, false otherwise.
     */
    public function testConnection(string $host, int $port, float|int $timeout = 1): bool
    {
        // just early return false if the port is not valid
        if ($port < 1 || $port > 65535) {
            return false;
        }
        $errno = null;
        $errstr = null;
        $sock = Callback::apply('fsockopen', $host, $port, $errno, $errstr, (float) $timeout);
        if (!$sock) {
            return false;
        }
        fclose($sock);
        return true;
    }

    public function __set(string $name, $value): void
    {
        // void
    }

    public function __unset(string $name): void
    {
        // void
    }

    public function __isset(string $name): bool
    {
        return $this->__get($name) !== null;
    }

    public function __get(string $name)
    {
        return match ($name) {
            'host' => $this->getHost(),
            'port' => $this->getPort(),
            'url' => $this->getUrl(),
            'environment' => $this->getEnvironment(),
            'status' => $this->getStatus(),
            'running' => $this->isRunning(),
            default => $this->getData()[$name]??null,
        };
    }
}
