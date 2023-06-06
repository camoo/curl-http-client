<?php

declare(strict_types=1);

namespace Camoo\Http\Curl\Test\TestCase\Infrastructure;

use Camoo\Http\Curl\Domain\Response\ResponseInterface;
use Camoo\Http\Curl\Infrastructure\HeaderResponse;
use Camoo\Http\Curl\Infrastructure\Response;
use Camoo\Http\Curl\Test\Fixture\CurlQueryMock;
use PHPUnit\Framework\TestCase;

class ResponseTest extends TestCase
{
    public function testHandleResponse(): void
    {
        $curlMock = CurlQueryMock::create($this);
        $fixture = $curlMock->getFixture();
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
}
