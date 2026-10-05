<?php

declare(strict_types=1);

namespace Camoo\Http\Curl\Test\TestCase\Infrastructure;

use BFunky\HttpParser\Entity\HttpField;
use BFunky\HttpParser\Entity\HttpResponseHeader;
use Camoo\Http\Curl\Application\Query\CurlQueryInterface;
use Camoo\Http\Curl\Domain\Entity\Configuration;
use Camoo\Http\Curl\Domain\Entity\Stream;
use Camoo\Http\Curl\Domain\Entity\Uri;
use Camoo\Http\Curl\Domain\Exception\InvalidArgumentException;
use Camoo\Http\Curl\Domain\Header\HeaderResponseInterface;
use Camoo\Http\Curl\Domain\Request\RequestInterface;
use Camoo\Http\Curl\Infrastructure\Request;
use Camoo\Http\Curl\Test\Fixture\CustomHeaderResponse;
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

    public function testHeadRequestDisablesResponseBody(): void
    {
        $curlQuery = $this->createMock(CurlQueryInterface::class);
        $curlQuery->method('setOption')->willReturnCallback(
            function (int $option, mixed $value): bool {
                if ($option === CURLOPT_NOBODY) {
                    $this->assertTrue($value);
                }

                return true;
            }
        );

        $request = new Request(new Configuration(), 'http://localhost', [], [], 'HEAD', null, null, $curlQuery);
        $request->getRequestHandle();
    }

    public function testNonHeadRequestAllowsResponseBody(): void
    {
        $curlQuery = $this->createMock(CurlQueryInterface::class);
        $curlQuery->method('setOption')->willReturnCallback(
            function (int $option, mixed $value): bool {
                if ($option === CURLOPT_NOBODY) {
                    $this->assertFalse($value);
                }

                return true;
            }
        );

        $request = new Request(new Configuration(), 'http://localhost', [], [], 'GET', null, null, $curlQuery);
        $request->getRequestHandle();
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
        $this->assertSame(['application/json'], $request->getHeader('Content-Type'));
        $this->assertSame([''], $request->getHeader('Authorization'));
    }

    public function testWithHeader(): void
    {
        $uri = new Uri('https://example.com');
        $headers = ['Content-Type' => 'application/json'];
        $data = [];
        $method = 'GET';
        $curlQuery = $this->createMock(CurlQueryInterface::class);

        $request = new Request(new Configuration(), $uri, $headers, $data, $method, null, null, $curlQuery);

        $newRequest = $request->withHeader('Authorization', 'Bearer token');

        $this->assertNotSame($request, $newRequest);
        $this->assertSame(['Bearer token'], $newRequest->getHeader('Authorization'));
        $this->assertTrue($newRequest->hasHeader('Authorization'));
        $this->assertFalse($request->hasHeader('Authorization'));
    }

    public function testWithAddedHeader(): void
    {
        $uri = new Uri('https://example.com');
        $headers = ['Content-Type' => 'application/json'];
        $data = [];
        $method = 'GET';
        $curlQuery = $this->createMock(CurlQueryInterface::class);

        $request = new Request(new Configuration(), $uri, $headers, $data, $method, null, null, $curlQuery);
        $newRequest = $request->withAddedHeader('Authorization', 'Bearer token');

        $this->assertNotSame($request, $newRequest);
        $this->assertSame(['application/json'], $newRequest->getHeader('Content-Type'));
        $this->assertSame(['Bearer token'], $newRequest->getHeader('Authorization'));
        $this->assertFalse($request->hasHeader('Authorization'));
    }

    public function testWithoutHeader(): void
    {
        $uri = new Uri('https://example.com');
        $headers = ['Content-Type' => 'application/json', 'Authorization' => 'Bearer token'];
        $data = [];
        $method = 'GET';
        $curlQuery = $this->createMock(CurlQueryInterface::class);

        $request = new Request(new Configuration(), $uri, $headers, $data, $method, null, null, $curlQuery);
        $newRequest = $request->withoutHeader('Content-Type');

        $this->assertNotSame($request, $newRequest);
        $this->assertFalse($newRequest->hasHeader('Content-Type'));
        $this->assertTrue($request->hasHeader('Content-Type'));
        $this->assertSame(['Bearer token'], $newRequest->getHeader('Authorization'));
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

        $this->assertNotSame($request, $newRequest);
        $this->assertSame('1.0', $newRequest->getProtocolVersion());
    }

    public function testMissingAndDuplicateHeaders(): void
    {
        $request = new Request(
            new Configuration(),
            'http://example.com',
            ['Accept' => ['application/json', 'text/html']],
            [],
            'GET'
        );

        $this->assertSame([], $request->getHeader('Non-Existent'));
        $this->assertSame('', $request->getHeaderLine('Non-Existent'));
        $this->assertFalse($request->hasHeader('Non-Existent'));

        $this->assertSame(['application/json', 'text/html'], $request->getHeader('Accept'));
        $this->assertSame('application/json, text/html', $request->getHeaderLine('Accept'));

        $newRequest = $request->withAddedHeader('Accept', 'text/plain');
        $this->assertNotSame($request, $newRequest);
        $this->assertSame(['application/json', 'text/html', 'text/plain'], $newRequest->getHeader('Accept'));
        $this->assertSame('application/json, text/html, text/plain', $newRequest->getHeaderLine('Accept'));
    }

    public function testPreserveHost(): void
    {
        $request = new Request(
            new Configuration(),
            'http://example.com',
            ['Host' => 'original.com'],
            [],
            'GET'
        );

        // Default preserveHost = false updates Host
        $updated = $request->withUri(new Uri('https://newhost.com/path'), false);
        $this->assertSame(['newhost.com'], $updated->getHeader('Host'));

        // preserveHost = true keeps existing Host
        $preserved = $request->withUri(new Uri('https://newhost.com/path'), true);
        $this->assertSame(['original.com'], $preserved->getHeader('Host'));

        // preserveHost = true updates Host if Host is missing
        $noHost = $request->withoutHeader('Host');
        $this->assertFalse($noHost->hasHeader('Host'));
        $withHost = $noHost->withUri(new Uri('https://anotherhost.com/path'), true);
        $this->assertSame(['anotherhost.com'], $withHost->getHeader('Host'));
    }

    public function testRepeatedRequestExecutionDoesNotMutateHeaders(): void
    {
        $curlQuery = $this->createMock(CurlQueryInterface::class);
        $optionsSet = [];
        $curlQuery->method('setOption')->willReturnCallback(function (int $opt, mixed $val) use (&$optionsSet): bool {
            $optionsSet[$opt] = $val;
            return true;
        });

        $config = new Configuration();
        $headers = [
            'auth' => ['type' => 'basic', 'username' => 'myuser', 'password' => 'mypass'],
            'user-agent' => 'CustomAgent/2.0',
            'X-Test-Header' => 'TestValue',
        ];

        $request = new Request($config, 'http://example.com', $headers, [], 'GET', null, null, $curlQuery);

        // First execution
        $handle1 = $request->getRequestHandle();
        $this->assertSame('CustomAgent/2.0', $optionsSet[CURLOPT_USERAGENT]);
        $this->assertSame('myuser:mypass', $optionsSet[CURLOPT_USERPWD]);
        $this->assertSame(CURLAUTH_BASIC, $optionsSet[CURLOPT_HTTPAUTH]);

        // Second execution on same request object
        $optionsSet = [];
        $handle2 = $request->getRequestHandle();
        $this->assertSame('CustomAgent/2.0', $optionsSet[CURLOPT_USERAGENT]);
        $this->assertSame('myuser:mypass', $optionsSet[CURLOPT_USERPWD]);
        $this->assertSame(CURLAUTH_BASIC, $optionsSet[CURLOPT_HTTPAUTH]);
        $this->assertSame(['TestValue'], $request->getHeader('X-Test-Header'));
    }

    public function testRedirectsAndAuthConfiguration(): void
    {
        $curlQuery = $this->createMock(CurlQueryInterface::class);
        $optionsSet = [];
        $curlQuery->method('setOption')->willReturnCallback(function (int $opt, mixed $val) use (&$optionsSet): bool {
            $optionsSet[$opt] = $val;
            return true;
        });

        $config = (new Configuration())
            ->setFollowRedirects(false)
            ->setMaxRedirects(3)
            ->setUnrestrictedAuth(true);

        $request = new Request($config, 'http://example.com', [], [], 'GET', null, null, $curlQuery);
        $request->getRequestHandle();

        $this->assertFalse($optionsSet[CURLOPT_FOLLOWLOCATION]);
        $this->assertSame(3, $optionsSet[CURLOPT_MAXREDIRS]);
        $this->assertTrue($optionsSet[CURLOPT_UNRESTRICTED_AUTH]);
    }

    public function testPostDataPreservedWhenNoExplicitBody(): void
    {
        $curlQuery = $this->createMock(CurlQueryInterface::class);
        $optionsSet = [];
        $curlQuery->method('setOption')->willReturnCallback(function (int $opt, mixed $val) use (&$optionsSet): bool {
            $optionsSet[$opt] = $val;

            return true;
        });

        $config = Configuration::create();
        $data = ['hello' => 'world'];
        $request = new Request($config, 'http://localhost/post', [], $data, 'POST', null, null, $curlQuery);
        $request->getRequestHandle();

        $this->assertSame($data, $optionsSet[CURLOPT_POSTFIELDS]);
    }

    public function testPostDataPreservedWithJsonHeader(): void
    {
        $curlQuery = $this->createMock(CurlQueryInterface::class);
        $optionsSet = [];
        $curlQuery->method('setOption')->willReturnCallback(function (int $opt, mixed $val) use (&$optionsSet): bool {
            $optionsSet[$opt] = $val;

            return true;
        });

        $config = Configuration::create();
        $data = ['hello' => 'world'];
        $request = new Request($config, 'http://localhost/post', ['Content-Type' => 'application/json'], $data, 'POST', null, null, $curlQuery);
        $request->getRequestHandle();

        $this->assertSame(json_encode($data), $optionsSet[CURLOPT_POSTFIELDS]);
    }

    public function testCustomHeaderResponseInterfaceImmutability(): void
    {
        $customHeaderResponse = new CustomHeaderResponse();
        $customHeaderResponse->withHeader(new HttpField('X-Initial', '1'));

        $request = new Request(
            Configuration::create(),
            'http://localhost',
            [],
            [],
            'GET',
            $customHeaderResponse
        );

        $newRequest = $request->withHeader('X-New', '2');

        $this->assertNotSame($request, $newRequest);
        $this->assertTrue($newRequest->hasHeader('X-New'));
        $this->assertFalse($request->hasHeader('X-New'));
    }

    public function testCaseInsensitiveWithHeaderReplacesExisting(): void
    {
        $request = new Request(
            Configuration::create(),
            'http://localhost',
            ['Content-Type' => 'text/html'],
            [],
            'GET'
        );

        $newRequest = $request->withHeader('content-type', 'application/json');

        $this->assertSame(['application/json'], $newRequest->getHeader('content-type'));
        $this->assertSame(['application/json'], $newRequest->getHeader('Content-Type'));
        $headers = $newRequest->getHeaders();
        $ctCount = 0;
        foreach (array_keys($headers) as $k) {
            if (strcasecmp((string)$k, 'content-type') === 0) {
                $ctCount++;
            }
        }
        $this->assertSame(1, $ctCount);
    }

    public function testGetDataWithUriInterfaceAppendsQuery(): void
    {
        $uri1 = new Uri('https://example.com');
        $request1 = new Request(Configuration::create(), $uri1, [], ['page' => 2], 'GET');
        $this->assertSame('https://example.com?page=2', (string)$request1->getUri());
        $this->assertSame('/?page=2', $request1->getRequestTarget());

        $uri2 = new Uri('https://example.com?sort=asc');
        $request2 = new Request(Configuration::create(), $uri2, [], ['page' => 2], 'GET');
        $this->assertSame('https://example.com?sort=asc&page=2', (string)$request2->getUri());
        $this->assertSame('/?sort=asc&page=2', $request2->getRequestTarget());
    }

    public function testJsonDetectionWithCharsetAndComplexContentType(): void
    {
        $optionsSet = [];
        $curlQuery = $this->createMock(CurlQueryInterface::class);
        $curlQuery->method('setOption')->willReturnCallback(function (int $opt, mixed $val) use (&$optionsSet): bool {
            $optionsSet[$opt] = $val;

            return true;
        });

        $config = Configuration::create();
        $data = ['hello' => 'world'];

        // Content-Type: application/json; charset=UTF-8
        $request = new Request($config, 'http://localhost/post', ['Content-Type' => 'application/json; charset=UTF-8'], $data, 'POST', null, null, $curlQuery);
        $request->getRequestHandle();
        $this->assertSame(json_encode($data), $optionsSet[CURLOPT_POSTFIELDS]);

        // Content-Type: application/problem+json; charset=utf-8
        $optionsSet = [];
        $request = new Request($config, 'http://localhost/post', ['Content-Type' => 'application/problem+json; charset=utf-8'], $data, 'POST', null, null, $curlQuery);
        $request->getRequestHandle();
        $this->assertSame(json_encode($data), $optionsSet[CURLOPT_POSTFIELDS]);

        // Case-insensitive content-type: APPLICATION/JSON
        $optionsSet = [];
        $request = new Request($config, 'http://localhost/post', ['content-type' => 'APPLICATION/JSON'], $data, 'POST', null, null, $curlQuery);
        $request->getRequestHandle();
        $this->assertSame(json_encode($data), $optionsSet[CURLOPT_POSTFIELDS]);
    }

    public function testExplicitBodyZeroPreserved(): void
    {
        $optionsSet = [];
        $curlQuery = $this->createMock(CurlQueryInterface::class);
        $curlQuery->method('setOption')->willReturnCallback(function (int $opt, mixed $val) use (&$optionsSet): bool {
            $optionsSet[$opt] = $val;

            return true;
        });

        $config = Configuration::create();

        // String body '0'
        $request = new Request($config, 'http://localhost/post', [], [], 'POST', null, '0', $curlQuery);
        $request->getRequestHandle();
        $this->assertArrayHasKey(CURLOPT_POSTFIELDS, $optionsSet);
        $this->assertSame('0', $optionsSet[CURLOPT_POSTFIELDS]);

        // Stream body with '0'
        $optionsSet = [];
        $request = new Request($config, 'http://localhost/post', [], [], 'POST', null, new Stream('0'), $curlQuery);
        $request->getRequestHandle();
        $this->assertArrayHasKey(CURLOPT_POSTFIELDS, $optionsSet);
        $this->assertSame('0', $optionsSet[CURLOPT_POSTFIELDS]);

        // withBody(new Stream('0'))
        $optionsSet = [];
        $request = (new Request($config, 'http://localhost/post', [], [], 'POST', null, null, $curlQuery))
            ->withBody(new Stream('0'));
        $request->getRequestHandle();
        $this->assertArrayHasKey(CURLOPT_POSTFIELDS, $optionsSet);
        $this->assertSame('0', $optionsSet[CURLOPT_POSTFIELDS]);
    }
}
