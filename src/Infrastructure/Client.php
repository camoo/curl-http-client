<?php

declare(strict_types=1);

namespace Camoo\Http\Curl\Infrastructure;

use Camoo\Http\Curl\Application\Query\CurlQueryInterface;
use Camoo\Http\Curl\Domain\Client\ClientInterface;
use Camoo\Http\Curl\Domain\Entity\Configuration;
use Camoo\Http\Curl\Domain\Entity\Stream;
use Camoo\Http\Curl\Domain\Request\RequestInterface;
use Camoo\Http\Curl\Domain\Response\ResponseInterface;
use Camoo\Http\Curl\Infrastructure\Exception\ClientException;
use Psr\Http\Message\RequestInterface as PsrRequestInterface;

final readonly class Client implements ClientInterface
{
    private const GET = 'GET';

    private const HEAD = 'HEAD';

    private const POST = 'POST';

    private const PUT = 'PUT';

    private const PATCH = 'PATCH';

    private const DELETE = 'DELETE';

    public function __construct(
        private ?Configuration $configuration = null,
        private ?CurlQueryInterface $curlQuery = null
    ) {
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

    public function sendRequest(PsrRequestInterface $request): ResponseInterface
    {
        $request = $this->normalizeRequest($request);
        $handle = $request->getRequestHandle();

        $responses = $handle->execute();
        $status = $handle->getInfo();
        $errorNumber = $handle->getErrorNumber();
        $error = $handle->getErrorMessage();
        $handle->close();

        if (empty($responses)) {
            throw new ClientException($error);
        }

        $headers = substr($responses, 0, $status['header_size']);
        $headerResponse = new HeaderResponse($headers);
        $body = substr($responses, $status['header_size']);

        if ($errorNumber !== 0 || !isset($status['http_code'])) {
            throw new ClientException($error);
        }
        return (new Response($headerResponse, new Stream($body)))
            ->withStatus((int)$status['http_code'], $headerResponse->getHeaderEntity()->getMessage());
    }

    private function normalizeRequest(PsrRequestInterface $request): RequestInterface
    {
        if ($request instanceof RequestInterface) {
            return $request;
        }

        $config = $this->configuration ?? Configuration::create();

        return new Request(
            $config,
            $request->getUri(),
            $request->getHeaders(),
            [],
            $request->getMethod(),
            null,
            $request->getBody(),
            $this->curlQuery
        );
    }

    private function buildRequest(
        string $url,
        array $headers = [],
        array $data = [],
        string $method = self::GET
    ): RequestInterface {
        $config = $this->configuration ?? Configuration::create();

        return new Request($config, $url, $headers, $data, $method, null, null, $this->curlQuery);
    }
}
