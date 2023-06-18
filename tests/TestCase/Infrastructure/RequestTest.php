<?php

declare(strict_types=1);

namespace Camoo\Http\Curl\Test\TestCase\Infrastructure;

use BFunky\HttpParser\Entity\HttpField;
use BFunky\HttpParser\Entity\HttpResponseHeader;
use Camoo\Http\Curl\Application\Query\CurlQueryInterface;
use Camoo\Http\Curl\Domain\Entity\Configuration;
use Camoo\Http\Curl\Domain\Entity\Uri;
use Camoo\Http\Curl\Domain\Exception\InvalidArgumentException;
use Camoo\Http\Curl\Domain\Header\HeaderResponseInterface;
use Camoo\Http\Curl\Domain\Request\RequestInterface;
use Camoo\Http\Curl\Infrastructure\Request;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\StreamInterface;
use Psr\Http\Message\UriInterface;

class RequestTest extends TestCase
{
    private ?RequestInterface $request;

    protected function setUp(): void
    {
        parent::setUp();
        $this->request = new Request(Configuration::create(), 'http://localhost', [], [], 'POST', null, '{"unit": "test"}');
    }

    protected function tearDown(): void
    {
        parent::tearDown();
        $this->request = null;
    }

    public function testCanCreateInstance(): void
    {
        $this->assertInstanceOf(Request::class, $this->request);
        $this->assertSame('POST', $this->request->getMethod());
        $this->assertInstanceOf(UriInterface::class, $this->request->getUri());
        $this->assertInstanceOf(Request::class, $this->request->withUri(new Uri('https://www.google.com')));
    }

    public function testCanCreateInstanceThrowsError(): void
    {
        $this->expectException(InvalidArgumentException::class);
        new Request(Configuration::create(), 'http://localhost', [], [], '', null, '{"unit": "test"}');
    }

    public function testWithMethodThrowsError(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->request->withMethod('');
    }

    public function testWithRequestTargetThrowsError(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->request->withRequestTarget('/ /foo-bar');
    }

    public function testCanHandleRequestTarget(): void
    {
        $newRequest = $this->request->withRequestTarget('/new-target');
        $this->assertNotSame($this->request, $newRequest);
        $this->assertSame('/new-target', $newRequest->getRequestTarget());
    }

    public function testRequestTargetWithQuery(): void
    {
        $uri = new Uri('https://example.com?page=1');
        $headers = [];
        $data = [];
        $method = 'GET';

        $request = new Request(new Configuration(), $uri, $headers, $data, $method);
        $newRequest = $request->withMethod('POST');

        $this->assertSame('/?page=1', $newRequest->getRequestTarget());
    }

    public function testGetMethod(): void
    {
        $uri = 'https://example.com';
        $headers = [];
        $data = ['foo' => 'bar'];
        $method = 'GET';
        $headerResponse = $this->createMock(HeaderResponseInterface::class);
        $body = null;
        $curlQuery = $this->createMock(CurlQueryInterface::class);

        $request = new Request(new Configuration(), $uri, $headers, $data, $method, $headerResponse, $body, $curlQuery);

        $this->assertSame('GET', $request->getMethod());
    }

    public function testWithMethod(): void
    {
        $uri = new Uri('https://example.com');
        $headers = [];
        $data = [];
        $method = 'GET';
        $headerResponse = $this->createMock(HeaderResponseInterface::class);
        $body = null;
        $curlQuery = $this->createMock(CurlQueryInterface::class);

        $request = new Request(new Configuration(), $uri, $headers, $data, $method, $headerResponse, $body, $curlQuery);
        $newRequest = $request->withMethod('POST');

        $this->assertNotSame($request, $newRequest);
        $this->assertSame('POST', $newRequest->getMethod());
    }

    public function testGetUri(): void
    {
        $uri = new Uri('https://example.com');
        $headers = [];
        $data = [];
        $method = 'GET';
        $headerResponse = $this->createMock(HeaderResponseInterface::class);
        $body = null;
        $curlQuery = $this->createMock(CurlQueryInterface::class);

        $request = new Request(new Configuration(), $uri, $headers, $data, $method, $headerResponse, $body, $curlQuery);

        $this->assertSame($uri, $request->getUri());
    }

    public function testWithUri(): void
    {
        $newUri = new Uri('https://new-example.com');
        $newRequest = $this->request->withUri($newUri);

        $this->assertNotSame($this->request, $newRequest);
        $this->assertSame($newUri, $newRequest->getUri());
    }

    public function testWithSameUri(): void
    {
        $newUri = new Uri('http://localhost');
        $newRequest = $this->request->withUri($newUri);

        $this->assertSame($this->request, $newRequest);
        $this->assertSame((string)$newUri, (string)$newRequest->getUri());
    }

    public function testGetHeaders(): void
    {
        $uri = new Uri('https://example.com');
        $headers = ['Content-Type' => 'application/json'];
        $data = [];
        $method = 'GET';
        $headerResponse = $this->createMock(HeaderResponseInterface::class);
        $body = null;
        $curlQuery = $this->createMock(CurlQueryInterface::class);

        $request = new Request(new Configuration(), $uri, $headers, $data, $method, $headerResponse, $body, $curlQuery);
        $headerResponse->expects($this->once())->method('getHeaders')->willReturn($headers);

        $this->assertSame($headers, $request->getHeaders());
    }

    public function testGetHeader(): void
    {
        $uri = new Uri('https://example.com');
        $headers = ['Content-Type' => 'application/json'];
        $data = [];
        $method = 'GET';
        $headerResponse = $this->createMock(HeaderResponseInterface::class);
        $body = null;
        $curlQuery = $this->createMock(CurlQueryInterface::class);

        $request = new Request(new Configuration(), $uri, $headers, $data, $method, $headerResponse, $body, $curlQuery);

        $headerResponse->expects($this->any())->method('getHeader')->willReturnCallback(function (string $line) {
            if ($line === 'Content-Type') {
                return new HttpField($line, 'application/json');
            }

            return  new HttpField($line, '');
        });
        $this->assertSame(['Content-Type' => 'application/json'], $request->getHeader('Content-Type'));
        $this->assertSame(['Authorization' => ''], $request->getHeader('Authorization'));
    }

    public function testWithHeader(): void
    {
        $uri = new Uri('https://example.com');
        $headers = ['Content-Type' => 'application/json'];
        $newHeader = ['Authorization' => 'Bearer token'];
        $data = [];
        $method = 'GET';
        $headerResponse = $this->createMock(HeaderResponseInterface::class);
        $body = null;
        $curlQuery = $this->createMock(CurlQueryInterface::class);

        $request = new Request(new Configuration(), $uri, $headers, $data, $method, $headerResponse, $body, $curlQuery);
        $headerResponse->expects($this->exactly(2))->method('exists')->with('Authorization')->willReturn(true);

        $newRequest = $request->withHeader('Authorization', 'Bearer token');

        $headerResponse->expects($this->once())->method('getHeaders')->willReturn($newHeader);

        $this->assertSame($request, $newRequest);
        $this->assertSame($newHeader, $newRequest->getHeaders());
        $this->assertTrue($newRequest->hasHeader('Authorization'));
    }

    public function testWithAddedHeader(): void
    {
        $uri = new Uri('https://example.com');
        $headers = ['Content-Type' => 'application/json'];
        $newHeader = ['Authorization' => 'Bearer token'];
        $expectedHeaders = ['Content-Type' => 'application/json', 'Authorization' => 'Bearer token'];
        $data = [];
        $method = 'GET';
        $headerResponse = $this->createMock(HeaderResponseInterface::class);
        $body = null;
        $curlQuery = $this->createMock(CurlQueryInterface::class);

        $request = new Request(new Configuration(), $uri, $headers, $data, $method, $headerResponse, $body, $curlQuery);
        $newRequest = $request->withAddedHeader('Authorization', 'Bearer token');
        $headerResponse->expects($this->once())->method('getHeaders')->willReturn(array_merge($headers, $newHeader));

        $this->assertSame($request, $newRequest);
        $this->assertSame($expectedHeaders, $newRequest->getHeaders());
    }

    public function testWithoutHeader(): void
    {
        $uri = new Uri('https://example.com');
        $headers = ['Content-Type' => 'application/json', 'Authorization' => 'Bearer token'];
        $expectedHeaders = ['Authorization' => 'Bearer token'];
        $data = [];
        $method = 'GET';
        $headerResponse = $this->createMock(HeaderResponseInterface::class);
        $body = null;
        $curlQuery = $this->createMock(CurlQueryInterface::class);
        $headerResponse->expects($this->once())->method('exists')->with('Content-Type')->willReturn(true);

        $request = new Request(new Configuration(), $uri, $headers, $data, $method, $headerResponse, $body, $curlQuery);
        $newRequest = $request->withoutHeader('Content-Type');
        $headerResponse->expects($this->once())->method('getHeaders')->willReturn($expectedHeaders);

        $this->assertSame($request, $newRequest);
        $this->assertSame($expectedHeaders, $newRequest->getHeaders());
    }

    public function testGetBody(): void
    {
        $uri = new Uri('https://example.com');
        $headers = [];
        $data = [];
        $method = 'GET';
        $headerResponse = $this->createMock(HeaderResponseInterface::class);
        $body = $this->createMock(StreamInterface::class);
        $curlQuery = $this->createMock(CurlQueryInterface::class);

        $request = new Request(new Configuration(), $uri, $headers, $data, $method, $headerResponse, $body, $curlQuery);

        $this->assertSame($body, $request->getBody());
    }

    public function testWithBody(): void
    {
        $uri = new Uri('https://example.com');
        $headers = [];
        $data = [];
        $method = 'GET';
        $headerResponse = $this->createMock(HeaderResponseInterface::class);
        $body = $this->createMock(StreamInterface::class);
        $newBody = $this->createMock(StreamInterface::class);
        $curlQuery = $this->createMock(CurlQueryInterface::class);

        $request = new Request(new Configuration(), $uri, $headers, $data, $method, $headerResponse, $body, $curlQuery);
        $newRequest = $request->withBody($newBody);

        $this->assertNotSame($request, $newRequest);
        $this->assertSame($newBody, $newRequest->getBody());
    }

    public function testWithSameBody(): void
    {
        $uri = new Uri('https://example.com');
        $headers = [];
        $data = [];
        $method = 'GET';
        $headerResponse = $this->createMock(HeaderResponseInterface::class);
        $body = $this->createMock(StreamInterface::class);
        $curlQuery = $this->createMock(CurlQueryInterface::class);

        $request = new Request(new Configuration(), $uri, $headers, $data, $method, $headerResponse, $body, $curlQuery);
        $newRequest = $request->withBody($body);
        $this->assertSame($request, $newRequest);
    }

    public function testGetProtocolVersion(): void
    {
        $uri = new Uri('https://example.com');
        $headers = [];
        $data = [];
        $method = 'GET';
        $headerResponse = $this->createMock(HeaderResponseInterface::class);
        $body = $this->createMock(StreamInterface::class);
        $curlQuery = $this->createMock(CurlQueryInterface::class);

        $request = new Request(new Configuration(), $uri, $headers, $data, $method, $headerResponse, $body, $curlQuery);
        $headerResponse->expects($this->once())->method('getHeaderEntity')->willReturn(new HttpResponseHeader('1.1', '200', ''));

        $this->assertSame('1.1', $request->getProtocolVersion());
    }

    public function testWithProtocolVersion(): void
    {
        $uri = new Uri('https://example.com');
        $headers = [];
        $data = [];
        $method = 'GET';
        $headerResponse = $this->createMock(HeaderResponseInterface::class);
        $body = $this->createMock(StreamInterface::class);
        $curlQuery = $this->createMock(CurlQueryInterface::class);
        $headerResponse->expects($this->any())->method('getHeaderEntity')->willReturn(new HttpResponseHeader('1.1', '200', ''));

        $request = new Request(new Configuration(), $uri, $headers, $data, $method, $headerResponse, $body, $curlQuery);
        $newRequest = $request->withProtocolVersion('1.0');

        $this->assertSame($request, $newRequest);
        $this->assertSame('1.0', $newRequest->getProtocolVersion());
    }
}
