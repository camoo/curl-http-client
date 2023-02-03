<?php

declare(strict_types=1);

namespace Camoo\Http\Curl\Infrastructure;

use Camoo\Http\Curl\Domain\Entity\Configuration;
use Camoo\Http\Curl\Domain\Entity\Stream;
use Camoo\Http\Curl\Domain\Entity\Uri;
use Camoo\Http\Curl\Domain\Exception\InvalidArgumentException;
use Camoo\Http\Curl\Domain\Header\HeaderResponseInterface;
use Camoo\Http\Curl\Domain\Request\RequestInterface;
use Camoo\Http\Curl\Domain\Trait\MessageTrait;
use Camoo\Http\Curl\Infrastructure\Exception\ClientException;
use CurlHandle;
use Psr\Http\Message\StreamInterface;
use Psr\Http\Message\UriInterface;

class Request implements RequestInterface
{
    use MessageTrait;

    private const GET = 'GET';

    private const HEAD = 'HEAD';

    private const POST = 'POST';

    private const PUT = 'PUT';

    private const PATCH = 'PATCH';

    private const DELETE = 'DELETE';

    private false|CurlHandle $handle;

    private ?string $requestTarget = null;

    public function __construct(
        private Configuration $config,
        private string|UriInterface $uri,
        private array $headers,
        private array $data,
        private string $method,
        private ?HeaderResponseInterface $headerResponse = null,
        private StreamInterface|string|null $body = null,
    ) {
        $this->handle = curl_init();
        $this->validateMethod($this->method);
        $this->ensureUri();
        if (is_string($this->body)) {
            $this->body = new Stream($this->body);
        }
    }

    public function getRequestTarget(): string
    {
        if ($this->requestTarget !== null) {
            return $this->requestTarget;
        }

        $target = $this->uri->getPath();
        if ($target === '') {
            $target = '/';
        }
        if ($this->uri->getQuery() != '') {
            $target .= '?' . $this->uri->getQuery();
        }

        return $target;
    }

    public function withRequestTarget($requestTarget): self
    {
        if (preg_match('#\s#', $requestTarget)) {
            throw new InvalidArgumentException(
                'Invalid request target provided; cannot contain whitespace'
            );
        }

        $new = clone $this;
        $new->requestTarget = $requestTarget;

        return $new;
    }

    public function getMethod(): string
    {
        return $this->method;
    }

    public function withMethod($method): self
    {
        $this->validateMethod($method);
        $new = clone $this;
        $new->method = strtoupper($method);

        return $new;
    }

    public function getUri(): UriInterface
    {
        return $this->uri;
    }

    public function withUri(UriInterface $uri, $preserveHost = false): self
    {
        if ($uri === $this->uri) {
            return $this;
        }

        $new = clone $this;
        $new->uri = $uri;

        return $new;
    }

    public function getRequestHandle(): CurlHandle|false
    {
        if (!$this->handle) {
            return false;
        }

        $this->setRequest();

        return $this->handle;
    }

    protected function mapTypeHeader(string $type): array
    {
        if (str_contains($type, '/')) {
            return [
                'Accept' => $type,
                'Content-Type' => $type,
            ];
        }

        $typeMap = [
            'json' => 'application/json',
            'xml' => 'application/xml',
        ];
        if (!isset($typeMap[$type])) {
            throw new ClientException(sprintf('Unknown type alias \'%s\'.', $type));
        }

        return [
            'Accept' => $typeMap[$type],
            'Content-Type' => $typeMap[$type],
        ];
    }

    private function ensureUri(): void
    {
        if ($this->method !== self::GET) {
            return;
        }

        if ($this->uri instanceof UriInterface) {
            return;
        }

        if (empty($this->data)) {
            return;
        }

        $uri = $this->uri;

        $query = !str_contains($uri, '?') ? '?' : '&';
        $uri .= $query;
        $uri .= http_build_query($this->data);

        $this->uri = new Uri($uri);
    }

    private function setRequest(): void
    {
        $userAgent = $this->getUserAgent();
        $headers = $this->headers;

        if (isset($headers['type'])) {
            $headers = array_merge($headers, $this->mapTypeHeader($headers['type']));
        }

        $isJson = false;
        if (isset($headers['Content-Type']) || isset($headers['content-type'])) {
            $contentType = $headers['Content-Type'] ?? $headers['content-type'];
            $isJson = $contentType === 'application/json';
        }

        $url = (string)$this->uri;

        if (!empty($this->headers)) {
            $curlHeaders = array_map(
                fn (string $val, mixed $key) => trim($key) . ': ' . trim($val),
                $this->headers,
                array_keys($headers)
            );
            curl_setopt($this->handle, CURLOPT_HTTPHEADER, $curlHeaders);
        }

        self::applyCurlHttps($this->handle, $url);
        curl_setopt($this->handle, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($this->handle, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($this->handle, CURLOPT_MAXREDIRS, 1);
        curl_setopt($this->handle, CURLOPT_USERAGENT, $userAgent);
        curl_setopt($this->handle, CURLOPT_HEADER, true);
        curl_setopt($this->handle, CURLOPT_NOBODY, 0);
        curl_setopt($this->handle, CURLOPT_URL, $url);
        $this->addRequestData($this->handle, $this->method, $this->data, $isJson);
        $this->parseOptions($this->handle);
    }

    private function addRequestData(CurlHandle $handle, string $method, array $data, bool $isJson = false): void
    {
        curl_setopt($handle, CURLOPT_CUSTOMREQUEST, $method);
        if ($method === self::POST) {
            curl_setopt($handle, CURLOPT_POST, 1);
        }

        if (in_array($method, [self::POST, self::PUT, self::PATCH], true)) {
            $postData = $data;

            if ($isJson) {
                $postData = json_encode($data);
            }
            if ($this->body instanceof StreamInterface) {
                $postData = $this->body->getContents();
            }

            curl_setopt($handle, CURLOPT_POSTFIELDS, $postData);
        }
    }

    private static function applyCurlHttps(CurlHandle $handle, string $url): void
    {
        if (stripos($url, 'https://') === false) {
            return;
        }

        curl_setopt($handle, CURLOPT_SSL_VERIFYPEER, 1);
        curl_setopt($handle, CURLOPT_SSL_VERIFYHOST, 2);
    }

    private function parseOptions(CurlHandle $handle): void
    {
        curl_setopt($handle, CURLOPT_TIMEOUT, $this->config->getTimeout());

        if ($this->config->getUsername() && $this->config->getPassword()) {
            curl_setopt($handle, CURLOPT_USERNAME, $this->config->getUsername());
            curl_setopt($handle, CURLOPT_PASSWORD, $this->config->getPassword());
        }

        if ($this->config->getReferer()) {
            curl_setopt($handle, CURLOPT_REFERER, $this->config->getReferer());
        }

        $this->debug($handle);
    }

    private function debug(CurlHandle $handle): void
    {
        if (!$this->config->getDebug()) {
            return;
        }

        curl_setopt($handle, CURLOPT_VERBOSE, true);
        $streamVerboseHandle = fopen($this->config->getDebugFile(), 'a');
        curl_setopt($handle, CURLOPT_STDERR, $streamVerboseHandle);
    }

    private function validateMethod(string $method): void
    {
        if (trim($method) === '') {
            throw new InvalidArgumentException('Method must be a non-empty string.');
        }
    }

    private function getUserAgent(): string
    {
        $userAgent = $this->headers['user-agent'] ?? null;
        if (null === $userAgent) {
            $userAgent = $this->headers['User-Agent'] ?? null;
        } else {
            unset($this->headers['user-agent']);
        }

        if ($userAgent) {
            unset($this->headers['User-Agent']);
        }

        return $userAgent ?? $this->config->getUserAgent();
    }
}
