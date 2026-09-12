<?php

declare(strict_types=1);

namespace Camoo\Http\Curl\Infrastructure\Query;

use Camoo\Http\Curl\Application\Query\CurlQueryInterface;
use Camoo\Http\Curl\Application\Query\MultiCurlQueryInterface;
use Camoo\Http\Curl\Infrastructure\Exception\ClientException;
use CurlMultiHandle;

final class CurlMultiRequestQuery implements MultiCurlQueryInterface
{
    private CurlMultiHandle|bool|null $multiHandle = null;

    public function __construct(CurlMultiHandle|bool|null $multiHandle = null)
    {
        $this->validateHandle($multiHandle);
    }

    public function addHandle(CurlQueryInterface $query): int
    {
        $rawHandle = $query->getRawHandle();
        if ($rawHandle === null || $rawHandle === false || $this->multiHandle === null) {
            return CURLM_BAD_HANDLE;
        }

        return curl_multi_add_handle($this->multiHandle, $rawHandle);
    }

    public function removeHandle(CurlQueryInterface $query): int
    {
        $rawHandle = $query->getRawHandle();
        if ($rawHandle === null || $rawHandle === false || $this->multiHandle === null) {
            return CURLM_BAD_HANDLE;
        }

        return curl_multi_remove_handle($this->multiHandle, $rawHandle);
    }

    public function exec(int &$stillRunning): int
    {
        if ($this->multiHandle === null) {
            return CURLM_BAD_HANDLE;
        }

        return curl_multi_exec($this->multiHandle, $stillRunning);
    }

    public function select(float $timeout = 1.0): int
    {
        if ($this->multiHandle === null) {
            return -1;
        }

        return curl_multi_select($this->multiHandle, $timeout);
    }

    public function close(): void
    {
        if ($this->multiHandle !== null && $this->multiHandle !== false) {
            curl_multi_close($this->multiHandle);
            $this->multiHandle = null;
        }
    }

    public function infoRead(int &$msgsInQueue = 0): array|false
    {
        if ($this->multiHandle === null) {
            return false;
        }

        return curl_multi_info_read($this->multiHandle, $msgsInQueue);
    }

    private function validateHandle(CurlMultiHandle|bool|null $multiHandle): void
    {
        if (!function_exists('curl_multi_init')) {
            throw new ClientException('PHP-Curl module is missing!', E_USER_ERROR);
        }
        $this->multiHandle = $multiHandle ?? curl_multi_init();
        if (false === $this->multiHandle) {
            throw new ClientException('Multi Request Handle was not initiated successfully !');
        }
    }
}
