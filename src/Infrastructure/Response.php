<?php

declare(strict_types=1);

namespace Camoo\Http\Curl\Infrastructure;

use Camoo\Http\Curl\Domain\Header\HeaderResponseInterface;
use Camoo\Http\Curl\Domain\Response\ResponseInterface;
use Camoo\Http\Curl\Domain\Trait\MessageTrait;
use Psr\Http\Message\StreamInterface;

class Response implements ResponseInterface
{
    use MessageTrait;

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
}
