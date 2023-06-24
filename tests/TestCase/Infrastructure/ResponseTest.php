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
}
