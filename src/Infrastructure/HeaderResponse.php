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

    private ?HttpResponseHeader $headerEntity = null;

    public function __construct(
        private string $header,
        private Headers|HttpFieldCollection|null $headerCollection = null
    ) {
        $this->headerCollection = $this->headerCollection ?? Headers::default();
        $this->parser = new HttpResponseParser($this->headerCollection);
        if ($this->header !== '') {
            $this->parser->parse($this->header);
            if (method_exists($this->parser, 'getHttpFieldCollection')) {
                $this->headerCollection = $this->parser->getHttpFieldCollection();
            }
            $this->normalizeParsedFieldValues();
        }
    }

    public function __clone()
    {
        $headerEntity = $this->getHeaderEntity();
        if ($this->headerCollection !== null) {
            $this->headerCollection = clone $this->headerCollection;
        }
        $this->parser = new HttpResponseParser($this->headerCollection);
        $this->headerEntity = clone $headerEntity;
    }

    public function withHeader(HttpField $field): self
    {
        $keys = $this->findKeys($field->getName());
        if (!empty($keys)) {
            $field = new HttpField($keys[0], $field->getValue());
        }
        $this->headerCollection->add($field);

        return $this;
    }

    public function getHeaders(): array
    {
        $headers = [];
        foreach ($this->headerCollection->getHttpFields() as $name => $field) {
            $displayName = is_array($field)
                ? $field[0]->getName()
                : $field->getName();

            if (is_array($field)) {
                $values = array_values(array_map(
                    static fn (HttpField $line): string => trim($line->getValue()),
                    $field
                ));
            } else {
                $values = [trim($field->getValue())];
            }

            $matchedKey = null;
            foreach (array_keys($headers) as $existingKey) {
                if (strcasecmp((string)$existingKey, $displayName) === 0) {
                    $matchedKey = $existingKey;
                    break;
                }
            }

            if ($matchedKey !== null) {
                $headers[$matchedKey] = array_merge($headers[$matchedKey], $values);
            } else {
                $headers[$displayName] = $values;
            }
        }

        return $headers;
    }

    public function getHeader(string $name): HttpField|array|null
    {
        $keys = $this->findKeys($name);
        if (empty($keys)) {
            return null;
        }

        $fields = [];
        foreach ($keys as $key) {
            try {
                $field = $this->headerCollection->get($key);
                if (is_array($field)) {
                    $fields = array_merge($fields, $field);
                } else {
                    $fields[] = $field;
                }
            } catch (HttpFieldNotFoundOnCollection) {
            }
        }

        if (empty($fields)) {
            return null;
        }

        return count($fields) === 1 ? $fields[0] : $fields;
    }

    public function getHeaderLine(string $name): ?string
    {
        $header = $this->getHeader($name);
        if ($header === null) {
            return null;
        }

        if (is_array($header)) {
            $values = array_map(static fn (HttpField $line): string => trim($line->getValue()), $header);

            return implode(', ', $values);
        }

        return trim($header->getValue());
    }

    /** @throws HttpFieldNotFoundOnCollection */
    public function getContentLength(): string
    {
        $header = $this->getHeaderLine('content-length');
        if ($header === null) {
            throw new HttpFieldNotFoundOnCollection('Field content-length not found');
        }

        return $header;
    }

    /** @throws HttpFieldNotFoundOnCollection */
    public function getContentType(): string
    {
        $header = $this->getHeaderLine('content-type');
        if ($header === null) {
            throw new HttpFieldNotFoundOnCollection('Field content-type not found');
        }

        return $header;
    }

    /** @throws HttpFieldNotFoundOnCollection */
    public function getServer(): string
    {
        $header = $this->getHeaderLine('server');
        if ($header === null) {
            throw new HttpFieldNotFoundOnCollection('Field server not found');
        }

        return $header;
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

    public function getHeaderEntity(): HttpResponseHeader
    {
        if ($this->headerEntity !== null) {
            return $this->headerEntity;
        }

        try {
            /** @var HttpResponseHeader $header */
            $header = $this->parser->getHeader();

            return $this->headerEntity = $header;
        } catch (\Throwable|\Error) {
            return $this->headerEntity = new HttpResponseHeader('1.1', '0', '');
        }
    }

    public function exists(string $name): bool
    {
        return !empty($this->findKeys($name));
    }

    public function remove(string $name): void
    {
        foreach ($this->findKeys($name) as $key) {
            try {
                $this->headerCollection->delete($key);
            } catch (HttpFieldNotFoundOnCollection) {
            }
        }
    }

    /** @return string[] */
    private function findKeys(string $name): array
    {
        $lower = strtolower($name);
        $keys = [];
        foreach (array_keys($this->headerCollection->getHttpFields()) as $key) {
            if (strtolower((string)$key) === $lower) {
                $keys[] = (string)$key;
            }
        }

        return $keys;
    }

    private function normalizeParsedFieldValues(): void
    {
        foreach ($this->headerCollection->getHttpFields() as $field) {
            $fields = is_array($field) ? $field : [$field];
            foreach ($fields as $line) {
                $line->setValue(trim($line->getValue()));
            }
        }
    }
}
