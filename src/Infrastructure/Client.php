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
    /** @var string */
    private const GET = 'GET';

    /** @var string */
    private const HEAD = 'HEAD';

    /** @var string */
    private const POST = 'POST';

    /** @var string */
    private const PUT = 'PUT';

    /** @var string */
    private const PATCH = 'PATCH';

    /** @var string */
    private const DELETE = 'DELETE';

    public function __construct(private ?Configuration $configuration = null)
    {
    }

    public function head(string $url, array $headers = []): ResponseInterface
    {
        return $this->sendRequest($this->getRequest($url, $headers, [], self::HEAD));
    }

    public function get(string $url, array $headers = []): ResponseInterface
    {
        return $this->sendRequest($this->getRequest($url, $headers));
    }

    public function post(string $url, array $data = [], array $headers = []): ResponseInterface
    {
        return $this->sendRequest($this->getRequest($url, $headers, $data, self::POST));
    }

    public function put(string $url, array $data = [], array $headers = []): ResponseInterface
    {
        return $this->sendRequest($this->getRequest($url, $headers, $data, self::PUT));
    }

    public function patch(string $url, array $data = [], array $headers = []): ResponseInterface
    {
        return $this->sendRequest($this->getRequest($url, $headers, $data, self::PATCH));
    }

    public function delete(string $url, array $headers = []): ResponseInterface
    {
        return $this->sendRequest($this->getRequest($url, $headers, [], self::DELETE));
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

        $response = new Response($headerResponse);

        if ($errno !== 0 || !isset($status['http_code'])) {
            throw new ClientException($error);
        }

        $response->withBody(new Stream($body));
        $response->withStatus((int)$status['http_code'], $headerResponse->getHeaderEntity()->getMessage());

        var_export($response->getHeader('x-content-type-options'));
        die;

        return $response;
    }

    public function getRequest(
        string $url,
        array $headers = [],
        array $data = [],
        string $method = self::GET
    ): RequestInterface {
        $config = $this->configuration ?? Configuration::create();

        return new Request($config, $url, $headers, $data, $method);
    }
}
