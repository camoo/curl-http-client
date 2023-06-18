<?php

declare(strict_types=1);

namespace Camoo\Http\Curl\Test\TestCase\Infrastructure;

use BFunky\HttpParser\Entity\HttpField;
use BFunky\HttpParser\Entity\HttpHeaderInterface;
use Camoo\Http\Curl\Domain\Header\HeaderResponseInterface;
use Camoo\Http\Curl\Infrastructure\HeaderResponse;
use Camoo\Http\Curl\Test\Fixture\CurlQueryMock;
use PHPUnit\Framework\TestCase;

class HeaderResponseTest extends TestCase
{
    public function testCanWithHeader(): void
    {
        $headerResponse = new HeaderResponse('');
        $result = $headerResponse->withHeader(new HttpField('phpunit', 'test'));
        $this->assertInstanceOf(HeaderResponseInterface::class, $result);
        $this->assertArrayHasKey('phpunit', $result->getHeaders());
        $this->assertNull($result->getHeader('foo'));
        $this->assertInstanceOf(HttpField::class, $result->getHeader('phpunit'));
        $this->assertNull($result->getHeaderLine('foo'));
        $this->assertSame('test', $result->getHeaderLine('phpunit'));
        $this->assertFalse($result->exists('foo'));
        $this->assertTrue($result->exists('phpunit'));
        $result->remove('phpunit');
        $this->assertFalse($result->exists('phpunit'));
    }

    public function testCanManageContent(): void
    {
        $curlMock = CurlQueryMock::create($this);
        $fixture = $curlMock->getFixture();
        $header = $fixture->getResponse();
        $headerResponse = new HeaderResponse($header);
        $this->assertEquals('1', $headerResponse->getContentLength());
        $this->assertSame('application/json; charset=UTF-8', $headerResponse->getContentType());
        $this->assertSame('camooCloud', $headerResponse->getServer());
        $this->assertEquals('200', $headerResponse->getCode());
        $this->assertSame('HTTP/2', $headerResponse->getProtocol());
        $this->assertSame('OK', $headerResponse->getMessage());
        $this->assertInstanceOf(HttpHeaderInterface::class, $headerResponse->getHeaderEntity());
        $this->assertCount(22, $headerResponse->getHeaders());
    }
}
