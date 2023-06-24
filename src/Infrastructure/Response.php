<?php

declare(strict_types=1);

namespace Camoo\Http\Curl\Infrastructure;

use Camoo\Http\Curl\Domain\Header\HeaderResponseInterface;
use Camoo\Http\Curl\Domain\Response\ResponseInterface;
use Camoo\Http\Curl\Domain\Trait\MessageTrait;
use Camoo\Http\Curl\Infrastructure\Exception\JsonResponseException;
use Psr\Http\Message\StreamInterface;
use stdClass;

class Response implements ResponseInterface
{
    use MessageTrait;

    private const MIN_HTTP_SUCCESS = 200;

    private const MAX_HTTP_SUCCESS = 299;

    public function __construct(
        private ?HeaderResponseInterface $headerResponse = null,
        private ?StreamInterface $body = null,
        private int $statusCode = 0,
        private string $reasonPhrase = '',
    ) {
    }

    public function getStatusCode(): int
    {
        return $this->statusCode ?: (int)$this->headerResponse->getCode();
    }

    public function withStatus(int $code, string $reasonPhrase = ''): self
    {
        $this->statusCode = $code;
        $this->reasonPhrase = $reasonPhrase;

        return $this;
    }

    public function getReasonPhrase(): string
    {
        return $this->reasonPhrase;
    }

    public function getJson(bool $associative = true): array|stdClass|null
    {
        if (!in_array($this->getStatusCode(), range(self::MIN_HTTP_SUCCESS, self::MAX_HTTP_SUCCESS), true)) {
            return null;
        }

        return $this->decodeJson((string)$this->getBody(), $associative);
    }

    protected function decodeJson(string $json, bool $associative): array|stdClass|null
    {
        $data = json_decode($json, $associative);

        if ($data === null && (json_last_error() !== JSON_ERROR_NONE)) {
            throw new JsonResponseException(json_last_error_msg());
        }

        return $data;
    }
}
