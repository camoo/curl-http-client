<?php

declare(strict_types=1);

namespace Camoo\Http\Curl\Domain\Entity;

use Camoo\Http\Curl\Domain\Exception\Exception;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\UriInterface;

class UriTest extends TestCase
{
    public function testConstructorWithEmptyUri(): void
    {
        $uri = new Uri();
        $this->assertSame('', $uri->__toString());
    }

    public function testConstructorWithInvalidUri(): void
    {
        $this->expectException(Exception::class);
        new Uri('http://:80');
    }

    public function testGetScheme(): void
    {
        $uri = new Uri('https://example.com');
        $this->assertSame('https', $uri->getScheme());
    }

    public function testGetAuthority(): void
    {
        $uri = new Uri('https://example.com:8080');
        $this->assertSame('example.com:8080', $uri->getAuthority());
    }

    public function testGetUserInfo(): void
    {
        $uri = new Uri('https://user:pass@example.com');
        $this->assertSame('user:pass', $uri->getUserInfo());
    }

    public function testGetHost(): void
    {
        $uri = new Uri('https://example.com');
        $this->assertSame('example.com', $uri->getHost());
    }

    public function testGetPort(): void
    {
        $uri = new Uri('https://example.com:8080');
        $this->assertSame(8080, $uri->getPort());
    }

    public function testGetPath(): void
    {
        $uri = new Uri('https://example.com/path');
        $this->assertSame('/path', $uri->getPath());
    }

    public function testGetQuery(): void
    {
        $uri = new Uri('https://example.com/?key=value');
        $this->assertSame('key=value', $uri->getQuery());
    }

    public function testGetFragment(): void
    {
        $uri = new Uri('https://example.com/#section');
        $this->assertSame('section', $uri->getFragment());
    }

    public function testWithUserInfo(): void
    {
        $uri = new Uri('https://example.com');
        $newUri = $uri->withUserInfo('user', 'pass');
        $this->assertSame('https://user:pass@example.com', $newUri->__toString());
    }

    public function testWithHost(): void
    {
        $uri = new Uri('https://example.com');
        $newUri = $uri->withHost('newhost.com');
        $this->assertSame('https://newhost.com', $newUri->__toString());
    }

    public function testWithSameHost(): void
    {
        $uri = new Uri('https://example.com');
        $newUri = $uri->withHost('example.com');
        $this->assertSame($uri, $newUri);
    }

    public function testWithPort(): void
    {
        $uri = new Uri('https://example.com');
        $newUri = $uri->withPort(8080);
        $this->assertSame('https://example.com:8080', $newUri->__toString());
    }

    public function testWithSamePort(): void
    {
        $uri = new Uri('https://example.com:8080');
        $newUri = $uri->withPort(8080);
        $this->assertSame($uri, $newUri);
    }

    public function testWithPath(): void
    {
        $uri = new Uri('https://example.com');
        $newUri = $uri->withPath('/newpath');
        $this->assertSame('https://example.com/newpath', $newUri->__toString());
    }

    public function testWithSamePath(): void
    {
        $uri = new Uri('https://example.com/newpath');
        $newUri = $uri->withPath('/newpath');
        $this->assertSame($uri, $newUri);
    }

    public function testCanHandleConstructValidUri(): void
    {
        $uriString = 'https://example.com/path?query=value#fragment';
        $uri = new Uri($uriString);

        $this->assertInstanceOf(UriInterface::class, $uri);
        $this->assertSame($uriString, $uri->__toString());
        $this->assertSame('https', $uri->getScheme());
        $this->assertSame('example.com', $uri->getHost());
        $this->assertSame('/path', $uri->getPath());
        $this->assertSame('query=value', $uri->getQuery());
        $this->assertSame('fragment', $uri->getFragment());
        $this->assertSame('https://example.com/path?query=value#fragment', $uri->jsonSerialize());
    }

    public function testWithScheme(): void
    {
        $uri = new Uri('http://example.com');
        $newUri = $uri->withScheme('https');

        $this->assertNotSame($uri, $newUri);
        $this->assertSame('http', $uri->getScheme());
        $this->assertSame('https', $newUri->getScheme());
    }

    public function testWithQuery(): void
    {
        $uri = new Uri('http://example.com');
        $newUri = $uri->withQuery('query=value');

        $this->assertNotSame($uri, $newUri);
        $this->assertSame('', $uri->getQuery());
        $this->assertSame('query=value', $newUri->getQuery());
    }

    public function testWithFragment(): void
    {
        $uri = new Uri('http://example.com');
        $newUri = $uri->withFragment('fragment');

        $this->assertNotSame($uri, $newUri);
        $this->assertSame('', $uri->getFragment());
        $this->assertSame('fragment', $newUri->getFragment());
    }

    public function testWithSameQuery(): void
    {
        $uri = new Uri('http://example.com?query=value');
        $newUri = $uri->withQuery('query=value');

        $this->assertSame($uri, $newUri);
    }

    public function testWithSameFragment(): void
    {
        $uri = new Uri('http://example.com#fragment');
        $newUri = $uri->withFragment('fragment');

        $this->assertSame($uri, $newUri);
    }

    public function testWithSameScheme(): void
    {
        $uri = new Uri('http://example.com#scheme');
        $newUri = $uri->withScheme('http');

        $this->assertSame($uri, $newUri);
    }

    public function testWithSameUserInfo(): void
    {
        $uri = new Uri('https://user:pass@example.com');
        $newUri = $uri->withUserInfo('user', 'pass');
        $this->assertSame($uri, $newUri);
    }
}
