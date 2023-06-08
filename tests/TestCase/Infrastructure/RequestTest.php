<?php

declare(strict_types=1);

namespace Camoo\Http\Curl\Test\TestCase\Infrastructure;

use Camoo\Http\Curl\Domain\Entity\Configuration;
use Camoo\Http\Curl\Domain\Entity\Uri;
use Camoo\Http\Curl\Infrastructure\Request;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\UriInterface;

class RequestTest extends TestCase
{
    public function testCanCreateInstance(): void
    {
        $request = new Request(Configuration::create(), 'http://localhost', [], [], 'POST', null, '{"unit": "test"}');
        $this->assertInstanceOf(Request::class, $request);
        $this->assertSame('POST', $request->getMethod());
        $this->assertInstanceOf(UriInterface::class, $request->getUri());
        $this->assertInstanceOf(Request::class, $request->withUri(new Uri('https://www.google.com')));
    }
}
