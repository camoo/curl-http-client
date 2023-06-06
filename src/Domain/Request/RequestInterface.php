<?php

declare(strict_types=1);

namespace Camoo\Http\Curl\Domain\Request;

use Camoo\Http\Curl\Application\Query\CurlQueryInterface;

interface RequestInterface extends \Psr\Http\Message\RequestInterface
{
    public function getRequestHandle(): CurlQueryInterface;
}
