<?php

declare(strict_types=1);

namespace Camoo\Http\Curl\Test\TestCase\Infrastructure;

use Camoo\Http\Curl\Domain\Client\MultiCurlInterface;
use Camoo\Http\Curl\Domain\Request\RequestInterface;
use Camoo\Http\Curl\Domain\Response\ResponseInterface;
use Camoo\Http\Curl\Infrastructure\Exception\ClientException;
use Camoo\Http\Curl\Infrastructure\MultiCurlPromise;
use Exception;
use PHPUnit\Framework\TestCase;

class MultiCurlPromiseTest extends TestCase
{
    private ?RequestInterface $request;

    protected function setUp(): void
    {
        parent::setUp();
        $this->request = $this->createMock(RequestInterface::class);
    }

    protected function tearDown(): void
    {
        parent::tearDown();
        $this->request = null;
    }

    public function testInitialState(): void
    {
        $promise = new MultiCurlPromise($this->request);

        $this->assertSame($this->request, $promise->getRequest());
        $this->assertSame(MultiCurlPromise::PENDING, $promise->getState());
        $this->assertTrue($promise->isPending());
        $this->assertFalse($promise->isFulfilled());
        $this->assertFalse($promise->isRejected());
        $this->assertNull($promise->getResponse());
        $this->assertNull($promise->getReason());
    }

    public function testResolvePromise(): void
    {
        $promise = new MultiCurlPromise($this->request);
        $response = $this->createMock(ResponseInterface::class);

        $fulfilledCalled = false;
        $promise->then(function ($res) use (&$fulfilledCalled, $response) {
            $fulfilledCalled = true;
            $this->assertSame($response, $res);
        });

        $promise->resolve($response);

        $this->assertSame(MultiCurlPromise::FULFILLED, $promise->getState());
        $this->assertFalse($promise->isPending());
        $this->assertTrue($promise->isFulfilled());
        $this->assertSame($response, $promise->getResponse());
        $this->assertTrue($fulfilledCalled);

        // Resolving again should be no-op
        $otherResponse = $this->createMock(ResponseInterface::class);
        $promise->resolve($otherResponse);
        $this->assertSame($response, $promise->getResponse());
    }

    public function testThenAfterResolve(): void
    {
        $promise = new MultiCurlPromise($this->request);
        $response = $this->createMock(ResponseInterface::class);
        $promise->resolve($response);

        $called = false;
        $promise->then(function ($res) use (&$called, $response) {
            $called = true;
            $this->assertSame($response, $res);
        });

        $this->assertTrue($called);
    }

    public function testRejectPromise(): void
    {
        $promise = new MultiCurlPromise($this->request);
        $exception = new Exception('Failed request');

        $rejectedCalled = false;
        $promise->catch(function ($reason) use (&$rejectedCalled, $exception) {
            $rejectedCalled = true;
            $this->assertSame($exception, $reason);
        });

        $promise->reject($exception);

        $this->assertSame(MultiCurlPromise::REJECTED, $promise->getState());
        $this->assertFalse($promise->isPending());
        $this->assertTrue($promise->isRejected());
        $this->assertSame($exception, $promise->getReason());
        $this->assertTrue($rejectedCalled);
    }

    public function testThenAfterReject(): void
    {
        $promise = new MultiCurlPromise($this->request);
        $exception = new Exception('Error');
        $promise->reject($exception);

        $called = false;
        $promise->then(null, function ($reason) use (&$called, $exception) {
            $called = true;
            $this->assertSame($exception, $reason);
        });

        $this->assertTrue($called);
    }

    public function testWaitReturnsResponseWhenFulfilled(): void
    {
        $promise = new MultiCurlPromise($this->request);
        $response = $this->createMock(ResponseInterface::class);
        $promise->resolve($response);

        $this->assertSame($response, $promise->wait());
    }

    public function testWaitThrowsExceptionWhenRejected(): void
    {
        $this->expectException(Exception::class);
        $this->expectExceptionMessage('Connection error');

        $promise = new MultiCurlPromise($this->request);
        $promise->reject(new Exception('Connection error'));
        $promise->wait();
    }

    public function testWaitTriggersMultiCurlSend(): void
    {
        $multiCurl = $this->createMock(MultiCurlInterface::class);
        $response = $this->createMock(ResponseInterface::class);
        $promise = new MultiCurlPromise($this->request, $multiCurl);

        $multiCurl->expects($this->once())
            ->method('send')
            ->willReturnCallback(function () use ($promise, $response) {
                $promise->resolve($response);
                return [$response];
            });

        $result = $promise->wait();
        $this->assertSame($response, $result);
    }

    public function testWaitThrowsExceptionIfUnsettledAfterSend(): void
    {
        $this->expectException(ClientException::class);
        $this->expectExceptionMessage('Promise was not settled after execution.');

        $multiCurl = $this->createMock(MultiCurlInterface::class);
        $promise = new MultiCurlPromise($this->request, $multiCurl);

        $multiCurl->expects($this->once())->method('send');
        $promise->wait();
    }
}
