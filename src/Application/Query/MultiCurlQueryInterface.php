<?php

declare(strict_types=1);

namespace Camoo\Http\Curl\Application\Query;

interface MultiCurlQueryInterface
{
    public function addHandle(CurlQueryInterface $query): int;

    public function removeHandle(CurlQueryInterface $query): int;

    public function exec(int &$stillRunning): int;

    public function select(float $timeout = 1.0): int;

    public function close(): void;

    public function infoRead(int &$msgsInQueue = 0): array|false;
}
