<?php

declare(strict_types=1);

namespace Camoo\Http\Curl\Infrastructure\Exception;

use Psr\Http\Client\ClientExceptionInterface;
use RuntimeException;

final class ClientException extends RuntimeException implements ClientExceptionInterface
{
}
