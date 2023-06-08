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
        return curl_exec($this->handle);
    }

    public function close(): void
    {
        curl_close($this->handle);
    }

    public function getInfo(?int $option = null): mixed
    {
        return curl_getinfo($this->handle, $option);
    }

    public function setOption(int $option, mixed $value): bool
    {
        return curl_setopt($this->handle, $option, $value);
    }

    public function getErrorNumber(): int
    {
        return curl_errno($this->handle);
    }

    public function getErrorMessage(): string
    {
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
