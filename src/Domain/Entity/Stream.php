<?php

declare(strict_types=1);

namespace Camoo\Http\Curl\Domain\Entity;

use Camoo\Http\Curl\Domain\Exception\Exception;
use Camoo\Http\Curl\Domain\Exception\StreamException;
use Camoo\Http\Curl\Domain\Exception\StreamInvalidArgumentException;
use LogicException;
use Psr\Http\Message\StreamInterface;
use RuntimeException;
use Throwable;

final class Stream implements StreamInterface
{
    private const ERROR_MESSAGE = 'ResourceStream::$stream must be a stream.';

    /** @param string|resource|null $stream */
    public function __construct(private $stream, string $accessMode = 'r+')
    {
        if (is_string($this->stream)) {
            $body = $this->stream;
            $this->stream = fopen('php://memory', $accessMode);
            fwrite($this->stream, $body);
            rewind($this->stream);
        }

        if (!is_resource($this->stream) || 'stream' !== get_resource_type($this->stream)) {
            throw new StreamInvalidArgumentException(
                'Invalid stream provided; must be a string stream identifier or stream resource'
            );
        }
    }

    public function __toString(): string
    {
        if (!$this->isReadable()) {
            return '';
        }
        try {
            if ($this->isSeekable()) {
                $this->rewind();
            }

            return $this->getContents();
        } catch (RuntimeException) {
            return '';
        }
    }

    public function close(): void
    {
        if ($this->stream != null) {
            fclose($this->stream);
        }
    }

    /** @return StreamInterface|resource|string */
    public function detach()
    {
        fclose($this->stream);

        return $this->stream;
    }

    public function getSize(): ?int
    {
        $length = null;

        if (is_resource($this->stream)) {
            /** @var array{"size": string} $stat */
            $stat = fstat($this->stream);
            $length = (int)$stat['size'];
        }

        return $length;
    }

    public function tell(): int
    {
        try {
            if ($this->stream === null || get_resource_type($this->stream) !== 'stream') {
                throw new StreamException(self::ERROR_MESSAGE, 2);
            }

            return ftell($this->stream) ?: 0;
        } catch (Throwable $e) {
            throw new StreamException('', $e->getCode(), $e);
        }
    }

    public function eof(): bool
    {
        return $this->tell() === $this->getSize();
    }

    public function isSeekable(): bool
    {
        if (!is_resource($this->stream)) {
            return false;
        }

        return $this->getMetadata('seekable') === true;
    }

    public function seek($offset, $whence = SEEK_SET): void
    {
        try {
            if ($this->stream === null || get_resource_type($this->stream) !== 'stream') {
                throw new Exception(self::ERROR_MESSAGE, 2);
            }
            fseek($this->stream, $offset, $whence);
        } catch (Throwable $e) {
            throw new StreamException('', $e->getCode(), $e);
        }
    }

    public function rewind(): void
    {
        try {
            if ($this->stream === null || get_resource_type($this->stream) !== 'stream') {
                throw new Exception(self::ERROR_MESSAGE, 2);
            }
            rewind($this->stream);
        } catch (Exception $e) {
            throw new StreamException('', $e->getCode(), $e);
        }
    }

    public function isWritable(): bool
    {
        if (!is_resource($this->stream)) {
            return false;
        }
        $metadata = $this->getMetadata('mode');
        if (!is_string($metadata)) {
            return false;
        }

        return stristr($metadata, 'w') !== false;
    }

    public function write($string): int
    {
        try {
            if ($this->stream === null || get_resource_type($this->stream) !== 'stream') {
                throw new Exception(self::ERROR_MESSAGE, 2);
            }

            return fwrite($this->stream, $string) ?: 0;
        } catch (Throwable $e) {
            throw new StreamException('', $e->getCode(), $e);
        }
    }

    public function isReadable(): bool
    {
        if (!is_resource($this->stream)) {
            return false;
        }

        $mode = $this->getMetadata('mode');
        if (!is_string($mode)) {
            return false;
        }

        return stristr($mode, 'w+') !== false
            || stristr($mode, 'r') !== false;
    }

    public function read($length): string
    {
        try {
            if ($this->stream === null || get_resource_type($this->stream) !== 'stream') {
                throw new Exception(self::ERROR_MESSAGE, 2);
            }
            if ($length < 0) {
                throw new LogicException('Length must not be negative.');
            }

            return fread($this->stream, $length) ?: '';
        } catch (Throwable $e) {
            throw new StreamException('', $e->getCode(), $e);
        }
    }

    public function getContents(): string
    {
        return $this->read($this->getSize() - $this->tell());
    }

    public function getMetadata($key = null): mixed
    {
        if (!is_resource($this->stream) || $key === null) {
            return null;
        }

        $metaData = stream_get_meta_data($this->stream);

        return $metaData[$key] ?? null;
    }
}
