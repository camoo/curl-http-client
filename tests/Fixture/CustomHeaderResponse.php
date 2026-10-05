<?php

declare(strict_types=1);

namespace Camoo\Http\Curl\Test\Fixture;

use BFunky\HttpParser\Entity\HttpField;
use BFunky\HttpParser\Entity\HttpResponseHeader;
use Camoo\Http\Curl\Domain\Header\HeaderResponseInterface;

class CustomHeaderResponse implements HeaderResponseInterface
{
    public array $headers = [];

    public function getServer(): string
    {
        return '';
    }

    public function getContentType(): string
    {
        return '';
    }

    public function getContentLength(): string
    {
        return '';
    }

    public function getProtocol(): string
    {
        return '1.1';
    }

    public function getMessage(): string
    {
        return 'OK';
    }

    public function getCode(): string
    {
        return '200';
    }

    public function getHeaderLine(string $name): ?string
    {
        $h = $this->getHeader($name);

        return $h instanceof HttpField ? $h->getValue() : null;
    }

    public function withHeader(HttpField $field): self
    {
        $this->headers[$field->getName()] = $field;

        return $this;
    }

    public function getHeaderEntity(): HttpResponseHeader
    {
        return new HttpResponseHeader('1.1', '200', 'OK');
    }

    public function exists(string $name): bool
    {
        return array_key_exists($name, $this->headers);
    }

    public function remove(string $name): void
    {
        unset($this->headers[$name]);
    }

    public function getHeaders(): array
    {
        $res = [];
        foreach ($this->headers as $k => $f) {
            $res[$k] = [$f->getValue()];
        }

        return $res;
    }

    public function getHeader(string $name): HttpField|array|null
    {
        return $this->headers[$name] ?? null;
    }
}
