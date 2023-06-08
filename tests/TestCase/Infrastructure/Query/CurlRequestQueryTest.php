<?php

namespace Camoo\Http\Curl\Test\TestCase\Infrastructure\Query;

use Camoo\Http\Curl\Infrastructure\Exception\ClientException;
use Camoo\Http\Curl\Infrastructure\Query\CurlRequestQuery;
use PHPUnit\Framework\TestCase;

class CurlRequestQueryTest extends TestCase
{
    public function testCreateInstanceThrowsException(): void
    {
        $this->expectException(ClientException::class);
        new CurlRequestQuery(false);
    }

    public function testCanHandleCurl(): void
    {
        $handle = new CurlRequestQuery(curl_init());
        $this->assertTrue($handle->setOption(CURLOPT_USERAGENT, 'UnitTest'));
        $info = $handle->getInfo();
        $this->assertSame('', $info['url']);
        $this->assertSame(0, $info['http_code']);
        $this->assertSame(0, $info['header_size']);
        $this->assertSame(0, $info['request_size']);
        $this->assertFalse($handle->execute());
        $this->assertSame(3, $handle->getErrorNumber());
        $this->assertSame('No URL set!', $handle->getErrorMessage());
        $this->assertNull($handle->close());
    }
}
