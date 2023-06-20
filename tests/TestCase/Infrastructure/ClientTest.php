<?php

declare(strict_types=1);

namespace Camoo\Http\Curl\Test\TestCase\Infrastructure;

use Camoo\Http\Curl\Application\Query\CurlQueryInterface;
use Camoo\Http\Curl\Domain\Client\ClientInterface;
use Camoo\Http\Curl\Domain\Entity\Configuration;
use Camoo\Http\Curl\Domain\Entity\Uri;
use Camoo\Http\Curl\Domain\Header\HeaderResponseInterface;
use Camoo\Http\Curl\Domain\Request\RequestInterface;
use Camoo\Http\Curl\Domain\Response\ResponseInterface;
use Camoo\Http\Curl\Infrastructure\Client;
use Camoo\Http\Curl\Infrastructure\Exception\ClientException;
use Camoo\Http\Curl\Infrastructure\Request;
use Camoo\Http\Curl\Test\Fixture\CurlQueryMock;
use PHPUnit\Framework\TestCase;

class ClientTest extends TestCase
{
    private ?CurlQueryInterface $curlQuery;

    private ?ClientInterface $client;

    private ?RequestInterface $request;

    private string $url = 'http://localhost';

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

    public function testSendRequestThrowsExceptionOnEmptyResponse(): void
    {
        $this->expectException(ClientException::class);
        $this->expectExceptionMessage('Could not resolve host: www.tld.com');

        $fixture = $this->curlQueryMock->getFixture(404);
        $this->curlQuery->method('execute')->willReturn(false);
        $this->curlQuery->method('getInfo')->willReturn($fixture->getInfo());
        $this->curlQuery->method('getErrorMessage')->willReturn('Could not resolve host: www.tld.com');
        $this->curlQuery->method('getErrorNumber')->willReturn(1);
        $this->curlQuery->method('close');
        $this->request->expects($this->once())->method('getRequestHandle')->willReturn($this->curlQuery);
        $this->client->sendRequest($this->request);
    }

    public function testCanSendRequest(): void
    {
        $fixture = $this->curlQueryMock->getFixture();
        $uri = new Uri('https://example.com');
        $auth = ['type' => 'Basic', 'username' => 'username', 'password' => 'password'];
        $headers = ['type' => 'json', 'Authorization' => 'My token', 'auth' => $auth];
        $data = [];
        $method = 'GET';
        $headerResponse = $this->createMock(HeaderResponseInterface::class);
        $body = null;
        $this->curlQuery->method('execute')->willReturn($fixture->getResponse());
        $this->curlQuery->method('getInfo')->willReturn($fixture->getInfo());
        $this->curlQuery->method('getErrorMessage')->willReturn('');
        $this->curlQuery->method('getErrorNumber')->willReturn(0);
        $this->curlQuery->method('close');

        $request = new Request(new Configuration(), $uri, $headers, $data, $method, $headerResponse, $body, $this->curlQuery);
        $response = $this->client->sendRequest($request);
        $this->assertInstanceOf(ResponseInterface::class, $response);
        $this->assertSame(200, $response->getStatusCode());
        $this->assertSame('HTTP/2', $response->getProtocolVersion());
        $this->assertSame('OK', $response->getReasonPhrase());
        $this->assertSame('camooCloud', $response->getHeaderLine('server'));
        $this->assertEquals(['set-cookie' => [
            'localhost=0bcpoc8vq6gu4opv4o573940f; expires=Mon, ' . gmdate('d-M-Y') . ' GMT; Max-Age=900; path=/; domain=localhost',
            'PHPSESSID=6sf8fa8rlm8c44avk33hhcegt0; path=/; HttpOnly',
        ]], $response->getHeader('set-cookie'));
    }

    public function testWithWrongHeaderTypeThrowsException(): void
    {
        $this->expectException(ClientException::class);
        $uri = new Uri('http://example.com');
        $headers = ['type' => 'error'];
        $data = [];
        $method = 'GET';
        $headerResponse = $this->createMock(HeaderResponseInterface::class);
        $body = null;

        $request = new Request(new Configuration(), $uri, $headers, $data, $method, $headerResponse, $body, $this->curlQuery);
        $this->client->sendRequest($request);
    }

    public function testWithFullValidHeaderType(): void
    {
        $fixture = $this->curlQueryMock->getFixture();
        $uri = new Uri('https://example.com');
        $headers = ['type' => 'application/xml'];
        $data = [];
        $method = 'GET';
        $headerResponse = $this->createMock(HeaderResponseInterface::class);
        $body = null;
        $this->curlQuery->method('execute')->willReturn($fixture->getResponse());
        $this->curlQuery->method('getInfo')->willReturn($fixture->getInfo());
        $this->curlQuery->method('getErrorMessage')->willReturn('');
        $this->curlQuery->method('getErrorNumber')->willReturn(0);
        $this->curlQuery->method('close');

        $request = new Request(new Configuration(), $uri, $headers, $data, $method, $headerResponse, $body, $this->curlQuery);
        $response = $this->client->sendRequest($request);
        $this->assertInstanceOf(ResponseInterface::class, $response);
    }

    public function testCanDebug(): void
    {
        $fixture = $this->curlQueryMock->getFixture();
        $uri = new Uri('https://example.com');
        $method = 'PATCH';
        $headerResponse = $this->createMock(HeaderResponseInterface::class);
        $body = null;
        $this->curlQuery->method('execute')->willReturn($fixture->getResponse());
        $this->curlQuery->method('getInfo')->willReturn($fixture->getInfo());
        $this->curlQuery->method('getErrorMessage')->willReturn('');
        $this->curlQuery->method('getErrorNumber')->willReturn(0);
        $this->curlQuery->method('close');
        $request = new Request(
            new Configuration(10, '', '', 'https://referer.camoo', 'camoo', true),
            $uri,
            ['type' => 'application/xml'],
            ['age' => 10],
            $method,
            $headerResponse,
            $body,
            $this->curlQuery
        );
        $response = $this->client->sendRequest($request);
        $this->assertInstanceOf(ResponseInterface::class, $response);
    }
}
