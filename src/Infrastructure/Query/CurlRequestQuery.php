<?php

declare(strict_types=1);

namespace Camoo\Http\Curl\Infrastructure\Query;

use Camoo\Http\Curl\Application\Query\CurlQueryInterface;
use Camoo\Http\Curl\Infrastructure\Exception\ClientException;
use CurlHandle;

final class CurlRequestQuery implements CurlQueryInterface
{
    public function __construct(private CurlHandle|bool|null $handle = null)
    {
        $this->validateHandle();
    }

    public function execute(): bool|string
    {
        if ($this->handle === null || $this->handle === false) {
            return false;
        }

        return curl_exec($this->handle);
    }

    public function getContent(): bool|string
    {
        if (null === $this->handle || false === $this->handle) {
            return false;
        }

        return curl_multi_getcontent($this->handle);
    }

    public function getRawHandle(): mixed
    {
        return $this->handle;
    }

    /**
     * A query is used as a prototype by MultiCurl. Each clone needs its own
     * native handle; cloning a CurlHandle would otherwise make requests share
     * the same underlying transfer.
     */
    public function __clone()
    {
        $this->handle = curl_init();
        if (false === $this->handle) {
            throw new ClientException('Request Handle was not initiated successfully !');
        }
    }

    public function close(): void
    {
        $this->handle = null;
    }

    public function getInfo(?int $option = null): mixed
    {
        if ($this->handle === null || $this->handle === false) {
            return false;
        }

        return curl_getinfo($this->handle, $option);
    }

    public function setOption(int $option, mixed $value): bool
    {
        if ($this->handle === null || $this->handle === false) {
            return false;
        }

        return curl_setopt($this->handle, $option, $value);
    }

    public function getErrorNumber(): int
    {
        if ($this->handle === null || $this->handle === false) {
            return 0;
        }

        return curl_errno($this->handle);
    }

    public function getErrorMessage(): string
    {
        if ($this->handle === null || $this->handle === false) {
            return '';
        }

        return curl_error($this->handle);
    }

    private function validateHandle(): void
    {
        if (!function_exists('curl_version')) {
            throw new ClientException('PHP-Curl module is missing!', E_USER_ERROR);
        }
        $this->handle = $this->handle ?? curl_init();
        if (false === $this->handle) {
            throw new ClientException('Request Handle was not initiated successfully !');
        }
    }
}
