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
    public function __construct(private mixed $stream, string $accessMode = 'r+')
    {
        $this->applyStream($accessMode);

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
        if (is_resource($this->stream)) {
            fclose($this->stream);
        }
        $this->detach();
    }

    /** @return resource|null */
    public function detach()
    {
        $resource = $this->stream;
        $this->stream = null;

        return is_resource($resource) ? $resource : null;
    }

    public function getSize(): ?int
    {
        if (!is_resource($this->stream)) {
            return null;
        }

        if (!$this->isSeekable()) {
            return null;
        }

        $stat = fstat($this->stream);
        if ($stat === false || !isset($stat['size'])) {
            return null;
        }

        return (int)$stat['size'];
    }

    public function tell(): int
    {
        try {
            if (!is_resource($this->stream) || get_resource_type($this->stream) !== 'stream') {
                throw new StreamException(self::ERROR_MESSAGE, 2);
            }

            return ftell($this->stream) ?: 0;
        } catch (Throwable $exception) {
            throw new StreamException('', $exception->getCode(), $exception);
        }
    }

    public function eof(): bool
    {
        if (!is_resource($this->stream)) {
            return true;
        }

        if (feof($this->stream)) {
            return true;
        }

        if ($this->isSeekable()) {
            $size = $this->getSize();
            if ($size !== null && $this->tell() >= $size) {
                return true;
            }
        }

        return false;
    }

    public function isSeekable(): bool
    {
        if (!is_resource($this->stream)) {
            return false;
        }

        return $this->getMetadata('seekable') === true;
    }

    public function seek(int $offset, int $whence = SEEK_SET): void
    {
        try {
            if (!is_resource($this->stream) || get_resource_type($this->stream) !== 'stream') {
                throw new Exception(self::ERROR_MESSAGE, 2);
            }
            fseek($this->stream, $offset, $whence);
        } catch (Throwable $exception) {
            throw new StreamException('', $exception->getCode(), $exception);
        }
    }

    public function rewind(): void
    {
        try {
            if (!is_resource($this->stream) || get_resource_type($this->stream) !== 'stream') {
                throw new Exception(self::ERROR_MESSAGE, 2);
            }
            rewind($this->stream);
        } catch (Throwable $exception) {
            throw new StreamException('', $exception->getCode(), $exception);
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

        return preg_match('/[waxc+]/i', $metadata) === 1;
    }

    public function write(string $string): int
    {
        try {
            if (!is_resource($this->stream) || get_resource_type($this->stream) !== 'stream') {
                throw new Exception(self::ERROR_MESSAGE, 2);
            }

            return fwrite($this->stream, $string) ?: 0;
        } catch (Throwable $exception) {
            throw new StreamException('', $exception->getCode(), $exception);
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

        return preg_match('/[r+]/i', $mode) === 1;
    }

    public function read(int $length): string
    {
        if ($length === 0) {
            return '';
        }

        try {
            if (!is_resource($this->stream) || get_resource_type($this->stream) !== 'stream') {
                throw new Exception(self::ERROR_MESSAGE, 2);
            }
            if ($length < 0) {
                throw new LogicException('Length must not be negative.');
            }

            $result = fread($this->stream, $length);

            return $result === false ? '' : $result;
        } catch (Throwable $exception) {
            throw new StreamException('', $exception->getCode(), $exception);
        }
    }

    public function getContents(): string
    {
        if (!is_resource($this->stream) || get_resource_type($this->stream) !== 'stream') {
            throw new StreamException(self::ERROR_MESSAGE, 2);
        }

        try {
            $contents = stream_get_contents($this->stream);
            if ($contents === false) {
                throw new StreamException('Unable to read stream contents.');
            }

            return $contents;
        } catch (Throwable $exception) {
            throw new StreamException('', (int)$exception->getCode(), $exception);
        }
    }

    public function getMetadata(?string $key = null): mixed
    {
        if (!is_resource($this->stream)) {
            return null;
        }

        $metaData = stream_get_meta_data($this->stream);

        return $key === null ? $metaData : ($metaData[$key] ?? null);
    }

    private function applyStream(string $accessMode): void
    {
        if (!is_string($this->stream)) {
            return;
        }
        $body = $this->stream;
        $this->stream = fopen('php://memory', $accessMode);
        fwrite($this->stream, $body);
        rewind($this->stream);
    }
}
