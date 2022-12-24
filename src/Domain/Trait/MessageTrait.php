<?php

declare(strict_types=1);

namespace Camoo\Http\Curl\Domain\Trait;

use BFunky\HttpParser\Entity\HttpField;
use Psr\Http\Message\MessageInterface;
use Psr\Http\Message\StreamInterface;

trait MessageTrait
{
    public function getProtocolVersion(): string
    {
        return $this->headerResponse->getHeaderEntity()->getProtocol();
    }

    public function withProtocolVersion($version): MessageInterface
    {
        $this->headerResponse->getHeaderEntity()->setProtocol($version);

        return $this;
    }

    public function getHeaders(): array
    {
        return $this->headerResponse->getHeaders();
    }

    public function hasHeader($name): bool
    {
        return $this->headerResponse->exists($name);
    }

    public function getHeader($name): array
    {
        $header = $this->headerResponse->getHeader($name);

        return [
            $header->getName() => $header->getValue(),
        ];
    }

    public function getHeaderLine($name): string
    {
        return $this->headerResponse->getHeaderLine($name) ?? '';
    }

    public function withHeader($name, $value): MessageInterface
    {
        if ($this->headerResponse->exists($name)) {
            $this->headerResponse->remove($name);
        }
        $field = new HttpField($name, $value);
        $this->headerResponse->withHeader($field);

        return $this;
    }

    public function withAddedHeader($name, $value): MessageInterface
    {
        $field = new HttpField($name, $value);
        $this->headerResponse->withHeader($field);

        return $this;
    }

    public function withoutHeader($name): MessageInterface
    {
        if ($this->headerResponse->exists($name)) {
            $this->headerResponse->remove($name);
        }

        return $this;
    }

    public function getBody(): StreamInterface
    {
        return $this->body;
    }

    public function withBody(StreamInterface $body): MessageInterface
    {
        if ($body === $this->body) {
            return $this;
        }

        $new = clone $this;
        $new->body = $body;

        return $new;
    }
}
