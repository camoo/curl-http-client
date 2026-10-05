<?php

declare(strict_types=1);

namespace Camoo\Http\Curl\Domain\Trait;

use BFunky\HttpParser\Entity\HttpField;
use Camoo\Http\Curl\Domain\Entity\Stream;
use Camoo\Http\Curl\Domain\Header\HeaderResponseInterface;
use Camoo\Http\Curl\Infrastructure\HeaderResponse;
use Psr\Http\Message\StreamInterface;

trait MessageTrait
{
    public function __clone()
    {
        if (isset($this->headerResponse) && $this->headerResponse instanceof HeaderResponseInterface) {
            $this->headerResponse = clone $this->headerResponse;
        }
    }

    public function getProtocolVersion(): string
    {
        if (!isset($this->headerResponse) || $this->headerResponse === null) {
            return '1.1';
        }

        return $this->headerResponse->getHeaderEntity()->getProtocol();
    }

    public function withProtocolVersion(string $version): self
    {
        $new = clone $this;
        $new->headerResponse ??= new HeaderResponse('');
        $new->headerResponse->getHeaderEntity()->setProtocol($version);

        return $new;
    }

    public function getHeaders(): array
    {
        if (!isset($this->headerResponse) || $this->headerResponse === null) {
            return [];
        }

        return $this->headerResponse->getHeaders();
    }

    public function hasHeader(string $name): bool
    {
        if (!isset($this->headerResponse) || $this->headerResponse === null) {
            return false;
        }

        return $this->headerResponse->exists($name);
    }

    public function getHeader(string $name): array
    {
        if (!isset($this->headerResponse) || $this->headerResponse === null) {
            return [];
        }

        $header = $this->headerResponse->getHeader($name);
        if ($header === null) {
            return [];
        }

        if (is_array($header)) {
            return array_values(array_map(
                static fn (HttpField $line): string => trim($line->getValue()),
                $header
            ));
        }

        return [trim($header->getValue())];
    }

    public function getHeaderLine(string $name): string
    {
        $values = $this->getHeader($name);

        return empty($values) ? '' : implode(', ', $values);
    }

    public function withHeader(string $name, $value): self
    {
        $new = clone $this;
        $new->headerResponse ??= new HeaderResponse('');
        if ($new->headerResponse->exists($name)) {
            $new->headerResponse->remove($name);
        }

        $values = is_array($value) ? $value : [$value];
        foreach ($values as $val) {
            $field = new HttpField($name, (string)$val);
            $new->headerResponse->withHeader($field);
        }

        if (property_exists($new, 'headers') && is_array($new->headers)) {
            foreach (array_keys($new->headers) as $k) {
                if (strcasecmp((string)$k, $name) === 0) {
                    unset($new->headers[$k]);
                }
            }
            $new->headers[$name] = is_array($value) ? implode(', ', $value) : (string)$value;
        }

        return $new;
    }

    public function withAddedHeader(string $name, $value): self
    {
        $new = clone $this;
        $new->headerResponse ??= new HeaderResponse('');
        $values = is_array($value) ? $value : [$value];
        foreach ($values as $val) {
            $field = new HttpField($name, (string)$val);
            $new->headerResponse->withHeader($field);
        }

        if (property_exists($new, 'headers') && is_array($new->headers)) {
            $existing = null;
            $existingKey = $name;
            foreach ($new->headers as $k => $v) {
                if (strcasecmp((string)$k, $name) === 0) {
                    $existing = $v;
                    $existingKey = $k;
                    break;
                }
            }
            $addedStr = is_array($value) ? implode(', ', $value) : (string)$value;
            $existingStr = is_array($existing) ? implode(', ', $existing) : (string)$existing;
            $new->headers[$existingKey] = $existing !== null ? $existingStr . ', ' . $addedStr : $addedStr;
        }

        return $new;
    }

    public function withoutHeader(string $name): self
    {
        $new = clone $this;
        if (isset($new->headerResponse) && $new->headerResponse !== null && $new->headerResponse->exists($name)) {
            $new->headerResponse->remove($name);
        }

        if (property_exists($new, 'headers') && is_array($new->headers)) {
            foreach (array_keys($new->headers) as $k) {
                if (strcasecmp((string)$k, $name) === 0) {
                    unset($new->headers[$k]);
                }
            }
        }

        return $new;
    }

    public function getBody(): StreamInterface
    {
        return $this->body ??= new Stream('');
    }

    public function withBody(StreamInterface $body): self
    {
        if ($body === $this->body) {
            return $this;
        }

        $new = clone $this;
        $new->body = $body;
        if (property_exists($new, 'hasExplicitBody')) {
            $new->hasExplicitBody = true;
        }

        return $new;
    }
}
