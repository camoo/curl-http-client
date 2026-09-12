<?php

declare(strict_types=1);

namespace Camoo\Http\Curl\Test\TestCase\Infrastructure;

use Camoo\Http\Curl\Application\Query\CurlQueryInterface;
use Camoo\Http\Curl\Application\Query\MultiCurlQueryInterface;
use Camoo\Http\Curl\Domain\Entity\Configuration;
use Camoo\Http\Curl\Domain\Request\RequestInterface;
use Camoo\Http\Curl\Domain\Response\ResponseInterface;
use Camoo\Http\Curl\Infrastructure\Exception\ClientException;
use Camoo\Http\Curl\Infrastructure\MultiCurl;
use Camoo\Http\Curl\Infrastructure\MultiCurlPromise;
use Camoo\Http\Curl\Test\Fixture\CurlQueryMock;
use Camoo\Http\Curl\Test\Fixture\MultiCurlQueryMock;
use PHPUnit\Framework\TestCase;

class MultiCurlTest extends TestCase
{
    private ?CurlQueryInterface $curlQuery1;

    private ?CurlQueryInterface $curlQuery2;

    private ?MultiCurlQueryInterface $multiCurlQuery;

    private ?CurlQueryMock $curlQueryMock;

    private ?MultiCurlQueryMock $multiCurlQueryMock;

    protected function setUp(): void
    {
        parent::setUp();
        $this->curlQueryMock = CurlQueryMock::create($this);
        $this->multiCurlQueryMock = MultiCurlQueryMock::create($this);

        $this->curlQuery1 = $this->curlQueryMock->getMock();
        $this->curlQuery2 = $this->curlQueryMock->getMock();
        $this->multiCurlQuery = $this->multiCurlQueryMock->getMock();
    }

    protected function tearDown(): void
    {
        parent::tearDown();
        $this->curlQuery1 = null;
        $this->curlQuery2 = null;
        $this->multiCurlQuery = null;
        $this->curlQueryMock = null;
        $this->multiCurlQueryMock = null;
    }

    public function testCanAddRequestsViaConvenienceMethods(): void
    {
        $multiCurl = new MultiCurl(null, $this->multiCurlQuery, $this->curlQuery1);

        $pHead = $multiCurl->head('http://localhost/head');
        $pGet = $multiCurl->get('http://localhost/get');
        $pPost = $multiCurl->post('http://localhost/post', ['a' => 1]);
        $pPut = $multiCurl->put('http://localhost/put', ['b' => 2]);
        $pPatch = $multiCurl->patch('http://localhost/patch', ['c' => 3]);
        $pDelete = $multiCurl->delete('http://localhost/delete');

        $promises = $multiCurl->getPromises();
        $this->assertCount(6, $promises);
        $this->assertSame('HEAD', $pHead->getRequest()->getMethod());
        $this->assertSame('GET', $pGet->getRequest()->getMethod());
        $this->assertSame('POST', $pPost->getRequest()->getMethod());
        $this->assertSame('PUT', $pPut->getRequest()->getMethod());
        $this->assertSame('PATCH', $pPatch->getRequest()->getMethod());
        $this->assertSame('DELETE', $pDelete->getRequest()->getMethod());
    }

    public function testCanAddPool(): void
    {
        $multiCurl = new MultiCurl(null, $this->multiCurlQuery);

        $req1 = $this->createMock(RequestInterface::class);
        $req2 = $this->createMock(RequestInterface::class);

        $added = $multiCurl->addPool(['first' => $req1, 'second' => $req2]);

        $this->assertCount(2, $added);
        $this->assertArrayHasKey('first', $added);
        $this->assertArrayHasKey('second', $added);
        $this->assertSame($req1, $added['first']->getRequest());
        $this->assertSame($req2, $added['second']->getRequest());
    }

    public function testExecuteSuccessfulParallelRequests(): void
    {
        $multiCurl = new MultiCurl(null, $this->multiCurlQuery);

        $fixture1 = $this->curlQueryMock->getFixture(200);
        $fixture2 = $this->curlQueryMock->getFixture(201);

        $handleObj1 = new \stdClass();
        $handleObj2 = new \stdClass();

        $this->curlQuery1->method('getRawHandle')->willReturn($handleObj1);
        $this->curlQuery1->method('getContent')->willReturn($fixture1->getResponse());
        $this->curlQuery1->method('getInfo')->willReturn($fixture1->getInfo());
        $this->curlQuery1->method('getErrorNumber')->willReturn(0);
        $this->curlQuery1->method('getErrorMessage')->willReturn('');

        $this->curlQuery2->method('getRawHandle')->willReturn($handleObj2);
        $this->curlQuery2->method('getContent')->willReturn($fixture2->getResponse());
        $this->curlQuery2->method('getInfo')->willReturn(['http_code' => 201, 'header_size' => 120]);
        $this->curlQuery2->method('getErrorNumber')->willReturn(0);
        $this->curlQuery2->method('getErrorMessage')->willReturn('');

        $req1 = $this->createMock(RequestInterface::class);
        $req1->method('getRequestHandle')->willReturn($this->curlQuery1);

        $req2 = $this->createMock(RequestInterface::class);
        $req2->method('getRequestHandle')->willReturn($this->curlQuery2);

        $p1 = $multiCurl->add($req1);
        $p2 = $multiCurl->add($req2);

        $this->multiCurlQuery->expects($this->exactly(2))->method('addHandle');
        $this->multiCurlQuery->method('exec')->willReturn(0);
        $this->multiCurlQuery->expects($this->exactly(2))->method('removeHandle');
        $this->multiCurlQuery->expects($this->once())->method('close');

        $responses = $multiCurl->send();

        $this->assertCount(2, $responses);
        $this->assertInstanceOf(ResponseInterface::class, $responses[0]);
        $this->assertInstanceOf(ResponseInterface::class, $responses[1]);
        $this->assertTrue($p1->isFulfilled());
        $this->assertTrue($p2->isFulfilled());
        $this->assertSame(200, $p1->getResponse()->getStatusCode());
    }

    public function testExecuteHandlesRequestFailure(): void
    {
        $multiCurl = new MultiCurl(null, $this->multiCurlQuery);

        $handleObj = new \stdClass();
        $this->curlQuery1->method('getRawHandle')->willReturn($handleObj);
        $this->curlQuery1->method('getContent')->willReturn(false);
        $this->curlQuery1->method('getInfo')->willReturn(['http_code' => 0, 'header_size' => 0]);
        $this->curlQuery1->method('getErrorNumber')->willReturn(6);
        $this->curlQuery1->method('getErrorMessage')->willReturn('Could not resolve host');

        $req = $this->createMock(RequestInterface::class);
        $req->method('getRequestHandle')->willReturn($this->curlQuery1);

        $promise = $multiCurl->add($req);

        $this->multiCurlQuery->method('exec')->willReturn(0);

        $responses = $multiCurl->send();

        $this->assertEmpty($responses);
        $this->assertTrue($promise->isRejected());
        $this->assertInstanceOf(ClientException::class, $promise->getReason());
        $this->assertSame('Could not resolve host', $promise->getReason()->getMessage());
    }

    public function testExecuteWhenNoPendingPromises(): void
    {
        $multiCurl = new MultiCurl(null, $this->multiCurlQuery);
        $responses = $multiCurl->execute();

        $this->assertEmpty($responses);
    }
}
