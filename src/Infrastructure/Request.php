<?php

declare(strict_types=1);

namespace Camoo\Http\Curl\Infrastructure;

use Camoo\Http\Curl\Application\Query\CurlQueryInterface;
use Camoo\Http\Curl\Domain\Entity\Configuration;
use Camoo\Http\Curl\Domain\Entity\Stream;
use Camoo\Http\Curl\Domain\Entity\Uri;
use Camoo\Http\Curl\Domain\Exception\InvalidArgumentException;
use Camoo\Http\Curl\Domain\Header\HeaderResponseInterface;
use Camoo\Http\Curl\Domain\Request\RequestInterface;
use Camoo\Http\Curl\Domain\Trait\MessageTrait;
use Camoo\Http\Curl\Infrastructure\Exception\ClientException;
use Camoo\Http\Curl\Infrastructure\Query\CurlRequestQuery;
use Psr\Http\Message\StreamInterface;
use Psr\Http\Message\UriInterface;

class Request implements RequestInterface
{
    use MessageTrait;

    private const GET = 'GET';

    private const POST = 'POST';

    private const PUT = 'PUT';

    private const PATCH = 'PATCH';

    private ?string $requestTarget = null;

    public function __construct(
        private Configuration $config,
        private string|UriInterface $uri,
        private array $headers,
        private array $data,
        private string $method,
        private ?HeaderResponseInterface $headerResponse = null,
        private StreamInterface|string|null $body = null,
        private ?CurlQueryInterface $curlQuery = null
    ) {
        $this->curlQuery = $this->curlQuery ?? new CurlRequestQuery();
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

    public function withRequestTarget(string $requestTarget): self
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

    public function withMethod(string $method): self
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

    public function withUri(UriInterface $uri, bool $preserveHost = false): self
    {
        if ((string)$uri === (string)$this->uri) {
            return $this;
        }

        $new = clone $this;
        $new->uri = $uri;

        return $new;
    }

    public function getRequestHandle(): CurlQueryInterface
    {
        $this->setRequest();

        return $this->curlQuery;
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
        if ($this->uri instanceof UriInterface) {
            return;
        }

        if (empty($this->data) || $this->method !== self::GET) {
            $this->uri = new Uri($this->uri);

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
        $auth = null;
        if (array_key_exists('auth', $this->headers)) {
            $auth = $this->headers['auth'];
            unset($this->headers['auth']);
        }
        $headers = $this->headers;

        if (isset($headers['type'])) {
            $newHeaders = $this->mapTypeHeader($headers['type']);
            unset($headers['type']);
            $headers = array_merge($headers, $newHeaders);
        }

        $isJson = false;
        $requestData = $this->data;
        $contentType = null;
        if (isset($headers['Content-Type']) || isset($headers['content-type'])) {
            $contentType = $headers['Content-Type'] ?? $headers['content-type'];
            $isJson = $contentType === 'application/json';
        }
        if ($contentType === 'application/x-www-form-urlencoded') {
            $requestData = http_build_query($this->data);
        }

        $url = (string)$this->uri;
        if (!empty($this->headers)) {
            $curlHeaders = array_map(
                fn (string $val, mixed $key) => trim($key) . ': ' . trim($val),
                $headers,
                array_keys($headers)
            );
            $this->curlQuery->setOption(CURLOPT_HTTPHEADER, $curlHeaders);
        }

        $this->applyCurlHttps($url);
        $this->curlQuery->setOption(CURLOPT_RETURNTRANSFER, 1);
        $this->curlQuery->setOption(CURLOPT_FOLLOWLOCATION, true);
        $this->curlQuery->setOption(CURLOPT_MAXREDIRS, 1);
        $this->curlQuery->setOption(CURLOPT_USERAGENT, $userAgent);
        $this->curlQuery->setOption(CURLOPT_HEADER, true);
        $this->applyHttpAuth($auth);
        $this->curlQuery->setOption(CURLOPT_NOBODY, 0);
        $this->curlQuery->setOption(CURLOPT_URL, $url);
        $this->addRequestData($this->method, $requestData, $isJson);
        $this->parseOptions();
    }

    private function applyHttpAuth(?array $auth): void
    {
        if (empty($auth)) {
            return;
        }
        $type = $auth['type'] ?? '';
        if (empty($type)) {
            $this->curlQuery->setOption(CURLOPT_HTTPAUTH, CURLAUTH_NONE);
        }
        $type = strtolower($type);
        if ($type === 'basic') {
            $this->curlQuery->setOption(CURLOPT_HTTPAUTH, CURLAUTH_BASIC);
        }
        if ($type === 'digest') {
            $this->curlQuery->setOption(CURLOPT_HTTPAUTH, CURLAUTH_DIGEST);
        }
        $username = $auth['username'] ?? '';
        $password = $auth['password'] ?? '';
        $this->curlQuery->setOption(CURLOPT_USERPWD, $username . ':' . $password);
    }

    private function addRequestData(string $method, array|string $data, bool $isJson = false): void
    {
        $this->curlQuery->setOption(CURLOPT_CUSTOMREQUEST, $method);
        if ($method === self::POST) {
            $this->curlQuery->setOption(CURLOPT_POST, 1);
        }

        if (!in_array($method, [self::POST, self::PUT, self::PATCH], true)) {
            return;
        }
        $postData = $data;

        if ($isJson && !is_string($data)) {
            $postData = json_encode($data);
        }
        if ($this->body instanceof StreamInterface) {
            $postData = $this->body->getContents();
        }
        if (!empty($data)) {
            $this->curlQuery->setOption(CURLOPT_POSTFIELDS, $postData);
        }
    }

    private function applyCurlHttps(string $url): void
    {
        if (stripos($url, 'https://') === false) {
            return;
        }

        $this->curlQuery->setOption(CURLOPT_SSL_VERIFYPEER, 1);
        $this->curlQuery->setOption(CURLOPT_SSL_VERIFYHOST, 2);
    }

    private function parseOptions(): void
    {
        $this->curlQuery->setOption(CURLOPT_TIMEOUT, $this->config->getTimeout());

        if ($this->config->getUsername() && $this->config->getPassword()) {
            $this->curlQuery->setOption(CURLOPT_USERNAME, $this->config->getUsername());
            $this->curlQuery->setOption(CURLOPT_PASSWORD, $this->config->getPassword());
        }

        if ($this->config->getReferer()) {
            $this->curlQuery->setOption(CURLOPT_REFERER, $this->config->getReferer());
        }

        $this->debug();
    }

    private function debug(): void
    {
        if (!$this->config->getDebug()) {
            return;
        }

        $this->curlQuery->setOption(CURLOPT_VERBOSE, true);
        $streamVerboseHandle = fopen($this->config->getDebugFile(), 'a');
        $this->curlQuery->setOption(CURLOPT_STDERR, $streamVerboseHandle);
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
