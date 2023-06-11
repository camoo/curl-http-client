<?php

declare(strict_types=1);

namespace Camoo\Http\Curl\Test\TestCase\Infrastructure;

use Camoo\Http\Curl\Application\Query\CurlQueryInterface;
use Camoo\Http\Curl\Domain\Client\ClientInterface;
use Camoo\Http\Curl\Domain\Request\RequestInterface;
use Camoo\Http\Curl\Domain\Response\ResponseInterface;
use Camoo\Http\Curl\Infrastructure\Client;
use Camoo\Http\Curl\Infrastructure\Exception\ClientException;
use Camoo\Http\Curl\Test\Fixture\CurlQueryMock;
use PHPUnit\Framework\TestCase;

class ClientTest extends TestCase
{
    private ?CurlQueryInterface $curlQuery;

    private ?ClientInterface $client;

    private ?RequestInterface $request;

    private string $url = 'https://localhost';

    private ?CurlQueryMock $curlQueryMock;

    protected function setUp(): void
    {
        parent::setUp();
        $this->curlQueryMock = CurlQueryMock::create($this);
        $this->curlQuery = $this->curlQueryMock->getMock();
        $this->client = new Client(null, $this->curlQuery);
        $this->request = $this->createMock(RequestInterface::class);
    }

    protected function tearDown(): void
    {
        parent::tearDown();
        $this->curlQuery = null;
        $this->client = null;
        $this->request = null;
        $this->curlQueryMock = null;
    }

    public function testCanApplyRequests(): void
    {
        $this->curlQuery->expects(self::any())->method('setOption')->willReturn(true);
        $this->curlQuery->method('execute')->willReturn($this->curlQueryMock->getFixture()->getResponse());
        $this->curlQuery->method('getInfo')->willReturn($this->curlQueryMock->getFixture()->getInfo());
        $this->curlQuery->method('getErrorMessage')->willReturn('');
        $this->curlQuery->method('getErrorNumber')->willReturn(0);
        $this->curlQuery->method('close');

        $this->assertInstanceOf(ResponseInterface::class, $this->client->head($this->url));
        $this->assertInstanceOf(ResponseInterface::class, $this->client->get($this->url, []));
        $this->assertInstanceOf(ResponseInterface::class, $this->client->post($this->url, []));
        $this->assertInstanceOf(ResponseInterface::class, $this->client->put($this->url));
        $this->assertInstanceOf(ResponseInterface::class, $this->client->delete($this->url));
        $this->assertInstanceOf(ResponseInterface::class, $this->client->patch($this->url));
    }

    public function testSendRequestThrowsException(): void
    {
        $this->expectException(ClientException::class);
        $this->expectExceptionMessage('Failed Error Message');
        $fixture = $this->curlQueryMock->getFixture(404);
        $this->curlQuery->method('execute')->willReturn($fixture->getResponse());
        $this->curlQuery->method('getInfo')->willReturn($fixture->getInfo());
        $this->curlQuery->method('getErrorMessage')->willReturn('Failed Error Message');
        $this->curlQuery->method('getErrorNumber')->willReturn(1);
        $this->curlQuery->method('close');
        $this->request->expects($this->once())->method('getRequestHandle')->willReturn($this->curlQuery);
        $this->client->sendRequest($this->request);
    }

    public function testCanSendRequest(): void
    {
        $fixture = $this->curlQueryMock->getFixture();
        $this->curlQuery->method('execute')->willReturn($fixture->getResponse());
        $this->curlQuery->method('getInfo')->willReturn($fixture->getInfo());
        $this->curlQuery->method('getErrorMessage')->willReturn('');
        $this->curlQuery->method('getErrorNumber')->willReturn(0);
        $this->curlQuery->method('close');
        $this->request->expects($this->once())->method('getRequestHandle')->willReturn($this->curlQuery);
        $response = $this->client->sendRequest($this->request);
        $this->assertInstanceOf(ResponseInterface::class, $response);
        $this->assertSame(200, $response->getStatusCode());
        $this->assertSame('HTTP/2', $response->getProtocolVersion());
        $this->assertSame('OK', $response->getReasonPhrase());
        $this->assertSame('camooCloud', $response->getHeaderLine('server'));
    }
}
