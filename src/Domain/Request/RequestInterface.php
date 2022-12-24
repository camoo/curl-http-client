<?php

declare(strict_types=1);

namespace Camoo\Http\Curl\Domain\Request;

use CurlHandle;

interface RequestInterface extends \Psr\Http\Message\RequestInterface
{
    public function getRequestHandle(): false|CurlHandle;
}
