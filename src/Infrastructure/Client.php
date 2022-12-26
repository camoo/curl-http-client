<?php

declare(strict_types=1);

namespace Camoo\Http\Curl\Infrastructure;

use Camoo\Http\Curl\Domain\Client\ClientInterface;
use Camoo\Http\Curl\Domain\Entity\Configuration;
use Camoo\Http\Curl\Domain\Entity\Stream;
use Camoo\Http\Curl\Domain\Request\RequestInterface;
use Camoo\Http\Curl\Domain\Response\ResponseInterface;
use Camoo\Http\Curl\Infrastructure\Exception\ClientException;

final class Client implements ClientInterface
{
    private const GET = 'GET';

    private const HEAD = 'HEAD';

    private const POST = 'POST';

    private const PUT = 'PUT';

    private const PATCH = 'PATCH';

    private const DELETE = 'DELETE';

    public function __construct(private ?Configuration $configuration = null)
    {
    }

    public function head(string $url, array $headers = []): ResponseInterface
    {
        return $this->sendRequest($this->buildRequest($url, $headers, [], self::HEAD));
    }

    public function get(string $url, array $headers = []): ResponseInterface
    {
        return $this->sendRequest($this->buildRequest($url, $headers));
    }

    public function post(string $url, array $data = [], array $headers = []): ResponseInterface
    {
        return $this->sendRequest($this->buildRequest($url, $headers, $data, self::POST));
    }

    public function put(string $url, array $data = [], array $headers = []): ResponseInterface
    {
        return $this->sendRequest($this->buildRequest($url, $headers, $data, self::PUT));
    }

    public function patch(string $url, array $data = [], array $headers = []): ResponseInterface
    {
        return $this->sendRequest($this->buildRequest($url, $headers, $data, self::PATCH));
    }

    public function delete(string $url, array $headers = []): ResponseInterface
    {
        return $this->sendRequest($this->buildRequest($url, $headers, [], self::DELETE));
    }

    public function sendRequest(RequestInterface $request): ResponseInterface
    {
        $handle = $request->getRequestHandle();
        if (false === $handle) {
            throw new ClientException('Request Handle was not initiated successfully !');
        }

        $responses = curl_exec($handle);
        $status = curl_getinfo($handle);
        $errno = curl_errno($handle);
        $error = curl_error($handle);
        curl_close($handle);

        $headers = substr($responses, 0, $status['header_size']);
        $headerResponse = new HeaderResponse($headers);
        $body = substr($responses, $status['header_size']);

        if ($errno !== 0 || !isset($status['http_code'])) {
            throw new ClientException($error);
        }
        $response = new Response($headerResponse, new Stream($body));
        $response->withStatus((int)$status['http_code'], $headerResponse->getHeaderEntity()->getMessage());

        return $response;
    }

    private function buildRequest(
        string $url,
        array $headers = [],
        array $data = [],
        string $method = self::GET
    ): RequestInterface {
        if (!function_exists('curl_version')) {
            throw new ClientException('PHP-Curl module is missing!', E_USER_ERROR);
        }
        $config = $this->configuration ?? Configuration::create();

        return new Request($config, $url, $headers, $data, $method);
    }
}
