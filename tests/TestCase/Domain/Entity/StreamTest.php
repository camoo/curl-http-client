<?php

declare(strict_types=1);

namespace Camoo\Http\Curl\Test\TestCase\Domain\Entity;

use Camoo\Http\Curl\Domain\Entity\Stream;
use Camoo\Http\Curl\Domain\Exception\StreamException;
use Camoo\Http\Curl\Domain\Exception\StreamInvalidArgumentException;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\StreamInterface;

class StreamTest extends TestCase
{
    public function testCannotCreateInstance(): void
    {
        $this->expectException(StreamInvalidArgumentException::class);
        new Stream(null);
    }

    public function testCanHandleStream(): void
    {
        $stream = new Stream('{"unit": "test"}');
        $this->assertInstanceOf(StreamInterface::class, $stream);

        $this->assertSame('{"unit": "test"}', $stream->__toString());
        $this->assertSame(16, $stream->getSize());
        $this->assertSame(16, $stream->tell());
        $this->assertTrue($stream->eof());
        $this->assertTrue($stream->isSeekable());
        $stream->seek(10);
        $this->assertSame('test"}', $stream->getContents());
        $stream->rewind();
        $this->assertSame('{"unit": "test"}', $stream->getContents());
        $this->assertTrue($stream->isWritable());
        $this->assertSame(17, $stream->write('{"test": "write"}'));
        $this->assertSame('{"unit": "test"}{"test": "write"}', (string)$stream);
        $this->assertTrue($stream->isReadable());
        $this->assertSame('', $stream->read(16));
        $stream->rewind();
        $this->assertSame('{"unit": "test"}', $stream->read(16));

        $this->assertNull($stream->close());
    }

    public function testCanDetach(): void
    {
        $stream = new Stream('{"unit": "detach"}');
        $detach = $stream->detach();
        $this->assertIsResource($detach);
    }

    public function testCannotRewind(): void
    {
        $this->expectException(StreamException::class);
        $stream = new Stream('{"unit": "rewind"}');
        $stream->detach();
        $stream->rewind();
    }

    public function testCannotRead(): void
    {
        $this->expectException(StreamException::class);
        $stream = new Stream('{"unit": "read"}');
        $stream->detach();
        $stream->read(2);
    }
}
