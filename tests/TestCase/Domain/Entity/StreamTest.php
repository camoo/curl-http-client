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

    public function testCannotTell(): void
    {
        $this->expectException(StreamException::class);
        $stream = new Stream('{"unit": "tell"}');
        $stream->detach();
        $stream->tell();
    }

    public function testCannotSeek(): void
    {
        $this->expectException(StreamException::class);
        $stream = new Stream('{"unit": "seek"}');
        $stream->detach();
        $stream->seek(2);
    }

    public function testCannotWrite(): void
    {
        $this->expectException(StreamException::class);
        $stream = new Stream('{"unit": "write"}');
        $stream->detach();
        $stream->write('foo');
    }

    public function testCannotReadByNegativeLength(): void
    {
        $this->expectException(StreamException::class);
        $stream = new Stream('{"unit": "read"}');
        $stream->read(-1);
    }

    public function testGetMetadataReturnsNull(): void
    {
        $stream = new Stream('{"unit": "meta"}');
        $stream->detach();
        $this->assertNull($stream->getMetadata());
        $this->assertNull($stream->getSize());
    }

    public function testIsReadableReturnsFalse(): void
    {
        $stream = new Stream('{"unit": "isReadable"}');
        $stream->detach();
        $this->assertFalse($stream->isReadable());
        $this->assertFalse($stream->isSeekable());
        $this->assertFalse($stream->isWritable());
    }

    public function testCannotToString(): void
    {
        $stream = new Stream('{"unit": "toString"}');
        $stream->detach();
        $this->assertSame('', $stream->__toString());
    }

    public function testDetachReturnsOpenResourceWithoutClosingIt(): void
    {
        $stream = new Stream('detach content');
        $resource = $stream->detach();
        $this->assertIsResource($resource);
        $this->assertSame('stream', get_resource_type($resource));

        // Resource is still open and usable
        rewind($resource);
        $this->assertSame('detach content', stream_get_contents($resource));
        fclose($resource);

        // Subsequent detach returns null
        $this->assertNull($stream->detach());
        $this->assertNull($stream->getSize());
        $this->assertFalse($stream->isReadable());
        $this->assertFalse($stream->isWritable());
        $this->assertFalse($stream->isSeekable());
    }

    /**
     * @dataProvider modeProvider
     */
    public function testReadableAndWritableModes(string $mode, bool $expectedReadable, bool $expectedWritable): void
    {
        $tempFile = tempnam(sys_get_temp_dir(), 'stream_test_');
        $handle = fopen($tempFile, $mode);
        $stream = new Stream($handle);

        $this->assertSame($expectedReadable, $stream->isReadable(), "Mode {$mode} readable assertion failed");
        $this->assertSame($expectedWritable, $stream->isWritable(), "Mode {$mode} writable assertion failed");

        $stream->close();
        if (file_exists($tempFile)) {
            @unlink($tempFile);
        }
    }

    public function modeProvider(): array
    {
        return [
            ['r', true, false],
            ['r+', true, true],
            ['w', false, true],
            ['w+', true, true],
            ['a', false, true],
            ['a+', true, true],
            ['c', false, true],
            ['c+', true, true],
        ];
    }

    public function testReadPayloadZero(): void
    {
        $stream = new Stream('0');
        $this->assertSame('0', $stream->read(1));
        $stream->rewind();
        $this->assertSame('0', $stream->getContents());
        $this->assertSame('0', (string)$stream);
    }

    public function testNonSeekableStreamWithUnknownSize(): void
    {
        $sockets = stream_socket_pair(STREAM_PF_UNIX, STREAM_SOCK_STREAM, STREAM_IPPROTO_IP);
        fwrite($sockets[0], 'streaming payload without known size');
        fclose($sockets[0]);

        $stream = new Stream($sockets[1]);
        $this->assertFalse($stream->isSeekable());
        $this->assertFalse($stream->eof());
        $this->assertSame('streaming payload without known size', $stream->getContents());
        $this->assertTrue($stream->eof());
        $stream->close();
    }
}
