<?php

declare(strict_types=1);

namespace Camoo\Http\Curl\Domain\Response;

use Camoo\Http\Curl\Infrastructure\Exception\JsonResponseException;
use stdClass;

interface ResponseInterface extends \Psr\Http\Message\ResponseInterface
{
    /**
     * @throws JsonResponseException
     */
    public function getJson(bool $associative = true): array|stdClass|null;
}
