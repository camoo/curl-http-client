<?php

declare(strict_types=1);

namespace Camoo\Http\Curl\Domain\Client;

use Camoo\Http\Curl\Domain\Response\ResponseInterface;
use Camoo\Http\Curl\Infrastructure\Exception\ClientException;
use Psr\Http\Message\RequestInterface;

interface ClientInterface extends \Psr\Http\Client\ClientInterface
{
    public function head(string $url, array $headers = []): ResponseInterface;

    public function get(string $url, array $headers = []): ResponseInterface;

    public function post(string $url, array $data = [], array $headers = []): ResponseInterface;

    public function put(string $url, array $data = [], array $headers = []): ResponseInterface;

    public function patch(string $url, array $data = [], array $headers = []): ResponseInterface;

    public function delete(string $url, array $headers = []): ResponseInterface;

    /** @throws ClientException */
    public function sendRequest(RequestInterface $request): ResponseInterface;
}
