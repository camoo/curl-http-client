<?php

declare(strict_types=1);

namespace Camoo\Http\Curl\Domain\Trait;

use BFunky\HttpParser\Entity\HttpField;
use Psr\Http\Message\StreamInterface;

trait MessageTrait
{
    public function getProtocolVersion(): string
    {
        return $this->headerResponse->getHeaderEntity()->getProtocol();
    }

    public function withProtocolVersion(string $version): self
    {
        $this->headerResponse->getHeaderEntity()->setProtocol($version);

        return $this;
    }

    public function getHeaders(): array
    {
        return $this->headerResponse->getHeaders();
    }

    public function hasHeader(string $name): bool
    {
        return $this->headerResponse->exists($name);
    }

    public function getHeader(string $name): array
    {
        $header = $this->headerResponse->getHeader($name);
        if (is_array($header)) {
            $values = array_map(
                fn (HttpField $line): string => trim($line->getValue()),
                $header
            );

            return [$name => $values];
        }

        return [
            $header->getName() => trim($header->getValue()),
        ];
    }

    public function getHeaderLine(string $name): string
    {
        return $this->headerResponse->getHeaderLine($name) ?? '';
    }

    public function withHeader(string $name, $value): self
    {
        if ($this->headerResponse->exists($name)) {
            $this->headerResponse->remove($name);
        }
        $field = new HttpField($name, $value);
        $this->headerResponse->withHeader($field);

        return $this;
    }

    public function withAddedHeader(string $name, $value): self
    {
        $field = new HttpField($name, $value);
        $this->headerResponse->withHeader($field);

        return $this;
    }

    public function withoutHeader(string $name): self
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

    public function withBody(StreamInterface $body): self
    {
        if ($body === $this->body) {
            return $this;
        }

        $new = clone $this;
        $new->body = $body;

        return $new;
    }
}
