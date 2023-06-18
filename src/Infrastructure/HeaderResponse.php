<?php

declare(strict_types=1);

namespace Camoo\Http\Curl\Infrastructure;

use BFunky\HttpParser\Entity\HttpField;
use BFunky\HttpParser\Entity\HttpFieldCollection;
use BFunky\HttpParser\Entity\HttpHeaderInterface;
use BFunky\HttpParser\Entity\HttpResponseHeader;
use BFunky\HttpParser\Exception\HttpFieldNotFoundOnCollection;
use BFunky\HttpParser\HttpResponseParser;
use Camoo\Http\Curl\Domain\Collection\Headers;
use Camoo\Http\Curl\Domain\Header\HeaderResponseInterface;

class HeaderResponse implements HeaderResponseInterface
{
    private HttpResponseParser $parser;

    public function __construct(
        private string $header,
        private Headers|HttpFieldCollection|null $headerCollection = null
    ) {
        $this->headerCollection = $this->headerCollection ?? Headers::default();
        $this->parser = new HttpResponseParser($this->headerCollection);
        $this->parser->parse($this->header);
    }

    public function withHeader(HttpField $field): self
    {
        $this->headerCollection->add($field);

        return $this;
    }

    public function getHeaders(): array
    {
        return array_map(function (HttpField|array $field): array {
            if (is_array($field)) {
                return array_map(
                    fn (HttpField $line) => [$line->getName() => trim($line->getValue())],
                    $field
                );
            }

            return [$field->getName() => trim($field->getValue())];
        }, $this->headerCollection->getIterator()->getArrayCopy());
    }

    public function getHeader(string $name): HttpField|array|null
    {
        try {
            $header = $this->headerCollection->get($name);
        } catch (HttpFieldNotFoundOnCollection) {
            return null;
        }

        return $header;
    }

    public function getHeaderLine(string $name): ?string
    {
        try {
            $line = $this->parser->get($name);
        } catch (HttpFieldNotFoundOnCollection) {
            return null;
        }

        return trim($line);
    }

    /** @throws HttpFieldNotFoundOnCollection */
    public function getContentLength(): string
    {
        return $this->parser->get('content-length');
    }

    /** @throws HttpFieldNotFoundOnCollection */
    public function getContentType(): string
    {
        return $this->parser->get('content-type');
    }

    /** @throws HttpFieldNotFoundOnCollection */
    public function getServer(): string
    {
        return $this->parser->get('server');
    }

    public function getCode(): string
    {
        return $this->getHeaderEntity()->getCode();
    }

    public function getProtocol(): string
    {
        return $this->getHeaderEntity()->getProtocol();
    }

    public function getMessage(): string
    {
        return $this->getHeaderEntity()->getMessage();
    }

    public function getHeaderEntity(): HttpHeaderInterface|HttpResponseHeader
    {
        return $this->parser->getHeader();
    }

    public function exists(string $name): bool
    {
        try {
            $this->parser->get($name);
        } catch (HttpFieldNotFoundOnCollection) {
            return false;
        }

        return true;
    }

    public function remove(string $name): void
    {
        $this->headerCollection->delete($name);
    }
}
