<?php

declare(strict_types=1);

namespace Camoo\Http\Curl\Domain\Header;

use BFunky\HttpParser\Entity\HttpField;
use BFunky\HttpParser\Entity\HttpHeaderInterface;
use BFunky\HttpParser\Entity\HttpResponseHeader;
use BFunky\HttpParser\Exception\HttpFieldNotFoundOnCollection;
use Throwable;

interface HeaderResponseInterface
{
    public function getServer(): string;

    public function getContentType(): string;

    public function getContentLength(): string;

    public function getProtocol(): string;

    public function getMessage(): string;

    public function getCode(): string;

    public function getHeaderLine(string $name): ?string;

    public function withHeader(HttpField $field): self;

    public function getHeaderEntity(): HttpHeaderInterface|HttpResponseHeader;

    public function exists(string $name): bool;

    /** @throws HttpFieldNotFoundOnCollection */
    public function remove(string $name): void;

    /** @throws Throwable */
    public function getHeaders(): array;

    public function getHeader(string $name): HttpField|array|null;
}
