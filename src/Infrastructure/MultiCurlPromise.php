<?php

declare(strict_types=1);

namespace Camoo\Http\Curl\Infrastructure;

use Camoo\Http\Curl\Domain\Client\MultiCurlInterface;
use Camoo\Http\Curl\Domain\Request\RequestInterface;
use Camoo\Http\Curl\Domain\Response\ResponseInterface;
use Camoo\Http\Curl\Infrastructure\Exception\ClientException;
use Throwable;

final class MultiCurlPromise
{
    public const PENDING = 'pending';
    public const FULFILLED = 'fulfilled';
    public const REJECTED = 'rejected';

    private string $state = self::PENDING;

    private ?ResponseInterface $response = null;

    private ?Throwable $reason = null;

    /** @var array<int, callable> */
    private array $onFulfilledCallbacks = [];

    /** @var array<int, callable> */
    private array $onRejectedCallbacks = [];

    public function __construct(
        private RequestInterface $request,
        private ?MultiCurlInterface $multiCurl = null
    ) {
    }

    public function getRequest(): RequestInterface
    {
        return $this->request;
    }

    public function getState(): string
    {
        return $this->state;
    }

    public function isPending(): bool
    {
        return $this->state === self::PENDING;
    }

    public function isFulfilled(): bool
    {
        return $this->state === self::FULFILLED;
    }

    public function isRejected(): bool
    {
        return $this->state === self::REJECTED;
    }

    public function getResponse(): ?ResponseInterface
    {
        return $this->response;
    }

    public function getReason(): ?Throwable
    {
        return $this->reason;
    }

    public function then(?callable $onFulfilled = null, ?callable $onRejected = null): self
    {
        if ($onFulfilled !== null) {
            if ($this->isFulfilled()) {
                $onFulfilled($this->response);
            } else {
                $this->onFulfilledCallbacks[] = $onFulfilled;
            }
        }

        if ($onRejected !== null) {
            if ($this->isRejected()) {
                $onRejected($this->reason);
            } else {
                $this->onRejectedCallbacks[] = $onRejected;
            }
        }

        return $this;
    }

    public function catch(callable $onRejected): self
    {
        return $this->then(null, $onRejected);
    }

    public function resolve(ResponseInterface $response): self
    {
        if (!$this->isPending()) {
            return $this;
        }

        $this->state = self::FULFILLED;
        $this->response = $response;

        foreach ($this->onFulfilledCallbacks as $callback) {
            $callback($response);
        }

        return $this;
    }

    public function reject(Throwable $reason): self
    {
        if (!$this->isPending()) {
            return $this;
        }

        $this->state = self::REJECTED;
        $this->reason = $reason;

        foreach ($this->onRejectedCallbacks as $callback) {
            $callback($reason);
        }

        return $this;
    }

    public function wait(): ResponseInterface
    {
        if ($this->isFulfilled()) {
            return $this->response;
        }

        if ($this->isRejected()) {
            throw $this->reason;
        }

        if ($this->multiCurl !== null) {
            $this->multiCurl->send();
        }

        if ($this->isFulfilled()) {
            return $this->response;
        }

        if ($this->isRejected()) {
            throw $this->reason;
        }

        throw new ClientException('Promise was not settled after execution.');
    }
}
