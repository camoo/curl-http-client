<?php

declare(strict_types=1);

namespace Camoo\Http\Curl\Test\TestCase\Infrastructure;

use Camoo\Http\Curl\Domain\Entity\Stream;
use Camoo\Http\Curl\Domain\Response\ResponseInterface;
use Camoo\Http\Curl\Infrastructure\Exception\JsonResponseException;
use Camoo\Http\Curl\Infrastructure\HeaderResponse;
use Camoo\Http\Curl\Infrastructure\Response;
use Camoo\Http\Curl\Test\Fixture\CurlQueryMock;
use PHPUnit\Framework\TestCase;
use stdClass;

class ResponseTest extends TestCase
{
    private ?CurlQueryMock $curlMock;

    protected function setUp(): void
    {
        parent::setUp();
        $this->curlMock = CurlQueryMock::create($this);
    }

    protected function tearDown(): void
    {
        parent::tearDown();
        $this->curlMock = null;
    }

    public function testHandleResponse(): void
    {
        $fixture = $this->curlMock->getFixture();
        $header = $fixture->getResponse();
        $headerResponse = new HeaderResponse($header);
        $response = new Response($headerResponse);
        $this->assertSame('', $response->getReasonPhrase());
        $this->assertSame(200, $response->getStatusCode());
        $status = $response->withStatus(404, 'KO');
        $this->assertInstanceOf(ResponseInterface::class, $status);
        $this->assertSame(404, $status->getStatusCode());
        $this->assertSame('KO', $status->getReasonPhrase());
    }

    public function testCanGetJson(): void
    {
        $fixture = $this->curlMock->getFixture();
        $header = $fixture->getResponse();
        $headerResponse = new HeaderResponse($header);
        $response = new Response($headerResponse);
        $newResponse = $response->withBody(new Stream('{"test": "OK"}'));
        $this->assertInstanceOf(ResponseInterface::class, $newResponse);
        $this->assertSame(['test' => 'OK'], $newResponse->getJson());
        $object = $newResponse->getJson(false);
        $this->assertInstanceOf(stdClass::class, $object);
        $this->assertSame('OK', $object->test);

        $forbiddenResponse = $newResponse->withStatus(403, 'KO');
        $this->assertNull($forbiddenResponse->getJson());
    }

    public function testGetJsonThrowsException(): void
    {
        $this->expectException(JsonResponseException::class);
        $fixture = $this->curlMock->getFixture();
        $header = $fixture->getResponse();
        $headerResponse = new HeaderResponse($header);
        $response = new Response($headerResponse);
        $newResponse = $response->withBody(new Stream('wrong json'));
        $newResponse->getJson();
    }

    public function testNewResponseWithNoArguments(): void
    {
        $response = new Response();
        $this->assertSame(0, $response->getStatusCode());
        $this->assertSame('', $response->getReasonPhrase());
        $this->assertSame('1.1', $response->getProtocolVersion());
        $this->assertSame([], $response->getHeaders());
        $this->assertSame([], $response->getHeader('foo'));
        $this->assertSame('', $response->getHeaderLine('foo'));
        $this->assertFalse($response->hasHeader('foo'));
        $this->assertSame('', (string)$response->getBody());
    }

    public function testResponseImmutability(): void
    {
        $response = new Response();
        $withStatus = $response->withStatus(200, 'OK');
        $this->assertNotSame($response, $withStatus);
        $this->assertSame(0, $response->getStatusCode());
        $this->assertSame(200, $withStatus->getStatusCode());
        $this->assertSame('OK', $withStatus->getReasonPhrase());

        $withHeader = $response->withHeader('X-Custom', 'val');
        $this->assertNotSame($response, $withHeader);
        $this->assertFalse($response->hasHeader('X-Custom'));
        $this->assertTrue($withHeader->hasHeader('X-Custom'));

        $withAdded = $withHeader->withAddedHeader('X-Custom', 'val2');
        $this->assertNotSame($withHeader, $withAdded);
        $this->assertSame(['val'], $withHeader->getHeader('X-Custom'));
        $this->assertSame(['val', 'val2'], $withAdded->getHeader('X-Custom'));

        $without = $withAdded->withoutHeader('X-Custom');
        $this->assertNotSame($withAdded, $without);
        $this->assertTrue($withAdded->hasHeader('X-Custom'));
        $this->assertFalse($without->hasHeader('X-Custom'));

        $withProtocol = $response->withProtocolVersion('2.0');
        $this->assertNotSame($response, $withProtocol);
        $this->assertSame('2.0', $withProtocol->getProtocolVersion());
    }
}
