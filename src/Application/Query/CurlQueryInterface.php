<?php

declare(strict_types=1);

namespace Camoo\Http\Curl\Application\Query;

interface CurlQueryInterface
{
    public function execute(): bool|string;

    public function close(): void;

    public function getInfo(?int $option = null): mixed;

    public function setOption(int $option, mixed $value): bool;

    public function getErrorNumber(): int;

    public function getErrorMessage(): string;
}
