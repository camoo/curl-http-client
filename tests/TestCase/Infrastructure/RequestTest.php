<?php

declare(strict_types=1);

namespace Camoo\Http\Curl\Test\TestCase\Infrastructure;

use Camoo\Http\Curl\Domain\Entity\Configuration;
use Camoo\Http\Curl\Infrastructure\Request;
use PHPUnit\Framework\TestCase;

class RequestTest extends TestCase
{
    public function testCanCreateInstance(): void
    {
        $request = new Request(Configuration::create(), 'http://localhost', [], [], 'POST', null, '{"unit": "test"}');
        $this->assertInstanceOf(Request::class, $request);
    }
}
