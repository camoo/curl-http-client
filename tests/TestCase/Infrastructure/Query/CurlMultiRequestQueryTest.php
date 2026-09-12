<?php

namespace Camoo\Http\Curl\Test\TestCase\Infrastructure\Query;

use Camoo\Http\Curl\Application\Query\CurlQueryInterface;
use Camoo\Http\Curl\Infrastructure\Exception\ClientException;
use Camoo\Http\Curl\Infrastructure\Query\CurlMultiRequestQuery;
use PHPUnit\Framework\TestCase;

class CurlMultiRequestQueryTest extends TestCase
{
    public function testCreateInstanceThrowsExceptionOnInvalidHandle(): void
    {
        $this->expectException(ClientException::class);
        new CurlMultiRequestQuery(false);
    }

    public function testAddAndRemoveHandleWithInvalidQuery(): void
    {
        $multiQuery = new CurlMultiRequestQuery();
        $query = $this->createMock(CurlQueryInterface::class);
        $query->method('getRawHandle')->willReturn(null);

        $this->assertSame(CURLM_BAD_HANDLE, $multiQuery->addHandle($query));
        $this->assertSame(CURLM_BAD_HANDLE, $multiQuery->removeHandle($query));
        $multiQuery->close();
    }

    public function testCanHandleMultiCurlOperations(): void
    {
        $multiQuery = new CurlMultiRequestQuery();
        $query = $this->createMock(CurlQueryInterface::class);
        $handle = curl_init();
        $query->method('getRawHandle')->willReturn($handle);

        $this->assertSame(0, $multiQuery->addHandle($query));

        $stillRunning = 0;
        $execResult = $multiQuery->exec($stillRunning);
        $this->assertSame(0, $execResult);

        $selectResult = $multiQuery->select(0.01);
        $this->assertIsInt($selectResult);

        $info = $multiQuery->infoRead();
        $this->assertTrue($info === false || is_array($info));

        $this->assertSame(0, $multiQuery->removeHandle($query));
        // CurlHandle instances are released automatically when no longer referenced.
        $handle = null;
        $multiQuery->close();
    }

    public function testOperationsOnClosedQuery(): void
    {
        $multiQuery = new CurlMultiRequestQuery();
        $multiQuery->close();

        $query = $this->createMock(CurlQueryInterface::class);
        $query->method('getRawHandle')->willReturn(curl_init());

        $stillRunning = 0;
        $this->assertSame(CURLM_BAD_HANDLE, $multiQuery->exec($stillRunning));
        $this->assertSame(-1, $multiQuery->select());
        $this->assertFalse($multiQuery->infoRead());
    }
}
