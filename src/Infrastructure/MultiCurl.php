<?php

declare(strict_types=1);

namespace Camoo\Http\Curl\Infrastructure;

use Camoo\Http\Curl\Application\Query\CurlQueryInterface;
use Camoo\Http\Curl\Application\Query\MultiCurlQueryInterface;
use Camoo\Http\Curl\Domain\Client\MultiCurlInterface;
use Camoo\Http\Curl\Domain\Entity\Configuration;
use Camoo\Http\Curl\Domain\Entity\Stream;
use Camoo\Http\Curl\Domain\Request\RequestInterface;
use Camoo\Http\Curl\Infrastructure\Exception\ClientException;
use Camoo\Http\Curl\Infrastructure\Query\CurlMultiRequestQuery;

final class MultiCurl implements MultiCurlInterface
{
    private const GET = 'GET';

    private const HEAD = 'HEAD';

    private const POST = 'POST';

    private const PUT = 'PUT';

    private const PATCH = 'PATCH';

    private const DELETE = 'DELETE';

    /** @var array<string|int, MultiCurlPromise> */
    private array $promises = [];

    public function __construct(
        private ?Configuration $configuration = null,
        private ?MultiCurlQueryInterface $multiCurlQuery = null,
        private ?CurlQueryInterface $curlQuery = null
    ) {
        $this->multiCurlQuery = $this->multiCurlQuery ?? new CurlMultiRequestQuery();
    }

    public function head(string $url, array $headers = []): MultiCurlPromise
    {
        return $this->add($this->buildRequest($url, $headers, [], self::HEAD));
    }

    public function get(string $url, array $headers = []): MultiCurlPromise
    {
        return $this->add($this->buildRequest($url, $headers));
    }

    public function post(string $url, array $data = [], array $headers = []): MultiCurlPromise
    {
        return $this->add($this->buildRequest($url, $headers, $data, self::POST));
    }

    public function put(string $url, array $data = [], array $headers = []): MultiCurlPromise
    {
        return $this->add($this->buildRequest($url, $headers, $data, self::PUT));
    }

    public function patch(string $url, array $data = [], array $headers = []): MultiCurlPromise
    {
        return $this->add($this->buildRequest($url, $headers, $data, self::PATCH));
    }

    public function delete(string $url, array $headers = []): MultiCurlPromise
    {
        return $this->add($this->buildRequest($url, $headers, [], self::DELETE));
    }

    public function add(RequestInterface $request): MultiCurlPromise
    {
        return $this->addRequest($request);
    }

    public function addRequest(RequestInterface $request): MultiCurlPromise
    {
        $promise = new MultiCurlPromise($request, $this);
        $this->promises[] = $promise;

        return $promise;
    }

    public function addPool(array $requests): array
    {
        $added = [];
        foreach ($requests as $key => $request) {
            $promise = new MultiCurlPromise($request, $this);
            $this->promises[$key] = $promise;
            $added[$key] = $promise;
        }

        return $added;
    }

    /** @return array<string|int, MultiCurlPromise> */
    public function getPromises(): array
    {
        return $this->promises;
    }

    public function send(): array
    {
        return $this->execute();
    }

    public function execute(): array
    {
        $pendingPromises = array_filter(
            $this->promises,
            fn (MultiCurlPromise $p) => $p->isPending()
        );

        if (empty($pendingPromises)) {
            return $this->getSettledResponses();
        }

        /** @var array<string|int, array{key: string|int, promise: MultiCurlPromise, query: CurlQueryInterface}> $map */
        $map = [];

        foreach ($pendingPromises as $key => $promise) {
            $request = $promise->getRequest();
            $query = $request->getRequestHandle();
            $result = $this->multiCurlQuery->addHandle($query);
            if ($result !== CURLM_OK) {
                $promise->reject(new ClientException(sprintf('Unable to add cURL handle (%d).', $result)));
                continue;
            }

            $id = $this->getHandleId($query->getRawHandle());

            $map[$id] = [
                'key' => $key,
                'promise' => $promise,
                'query' => $query,
            ];
        }

        $stillRunning = 0;
        do {
            $status = $this->multiCurlQuery->exec($stillRunning);
            if ($status === CURLM_OK && $stillRunning > 0) {
                $this->multiCurlQuery->select(0.1);
            }
        } while ($stillRunning > 0 && ($status === CURLM_OK || $this->isPerformingCallMultiPerform($status)));

        $multiStatus = $status;
        if ($multiStatus !== CURLM_OK) {
            $error = new ClientException(sprintf('cURL multi execution failed (%d).', $status));
            foreach ($map as $item) {
                $item['promise']->reject($error);
            }
        }

        foreach ($map as $item) {
            /** @var MultiCurlPromise $promise */
            $promise = $item['promise'];
            /** @var CurlQueryInterface $query */
            $query = $item['query'];

            try {
                $responseStr = $query->getContent();
                $status = $query->getInfo();
                $errorNumber = $query->getErrorNumber();
                $error = $query->getErrorMessage();
                $this->multiCurlQuery->removeHandle($query);

                if ($multiStatus !== CURLM_OK) {
                    continue;
                }

                if ($errorNumber !== 0 || !is_array($status) || !isset($status['http_code'])) {
                    throw new ClientException($error !== '' ? $error : 'cURL request failed.');
                }

                $headerSize = is_array($status) && isset($status['header_size']) ? $status['header_size'] : 0;
                $responseStr = $responseStr === false ? '' : $responseStr;
                $headers = substr($responseStr, 0, $headerSize);
                $headerResponse = new HeaderResponse($headers);
                $body = substr($responseStr, $headerSize);

                $response = new Response($headerResponse, new Stream($body));
                $response->withStatus((int)$status['http_code'], $headerResponse->getHeaderEntity()->getMessage());

                $promise->resolve($response);
            } catch (\Throwable $e) {
                $promise->reject($e);
            } finally {
                $query->close();
            }
        }

        $this->multiCurlQuery->close();

        return $this->getSettledResponses();
    }

    private function buildRequest(
        string $url,
        array $headers = [],
        array $data = [],
        string $method = self::GET
    ): RequestInterface {
        $config = $this->configuration ?? Configuration::create();

        $curlQuery = $this->curlQuery === null ? null : clone $this->curlQuery;

        return new Request($config, $url, $headers, $data, $method, null, null, $curlQuery);
    }

    private function getHandleId(mixed $rawHandle): string|int
    {
        if (is_object($rawHandle)) {
            return spl_object_id($rawHandle);
        }
        if (is_resource($rawHandle)) {
            return (int)$rawHandle;
        }

        return (string)$rawHandle;
    }

    private function isPerformingCallMultiPerform(int $status): bool
    {
        return defined('CURLM_CALL_MULTI_PERFORM') && $status === CURLM_CALL_MULTI_PERFORM;
    }

    private function getSettledResponses(): array
    {
        $responses = [];
        foreach ($this->promises as $key => $promise) {
            if ($promise->isFulfilled()) {
                $responses[$key] = $promise->getResponse();
            }
        }

        return $responses;
    }
}
