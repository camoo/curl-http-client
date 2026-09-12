<?php

declare(strict_types=1);

namespace Camoo\Http\Curl\Domain\Client;

use Camoo\Http\Curl\Domain\Request\RequestInterface;
use Camoo\Http\Curl\Infrastructure\MultiCurlPromise;

interface MultiCurlInterface
{
    public function head(string $url, array $headers = []): MultiCurlPromise;

    public function get(string $url, array $headers = []): MultiCurlPromise;

    public function post(string $url, array $data = [], array $headers = []): MultiCurlPromise;

    public function put(string $url, array $data = [], array $headers = []): MultiCurlPromise;

    public function patch(string $url, array $data = [], array $headers = []): MultiCurlPromise;

    public function delete(string $url, array $headers = []): MultiCurlPromise;

    public function add(RequestInterface $request): MultiCurlPromise;

    public function addRequest(RequestInterface $request): MultiCurlPromise;

    /**
     * @param array<string|int, RequestInterface> $requests
     * @return array<string|int, MultiCurlPromise>
     */
    public function addPool(array $requests): array;

    /**
     * @return array<string|int, \Camoo\Http\Curl\Domain\Response\ResponseInterface>
     */
    public function send(): array;

    /**
     * @return array<string|int, \Camoo\Http\Curl\Domain\Response\ResponseInterface>
     */
    public function execute(): array;
}
