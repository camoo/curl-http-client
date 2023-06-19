<?php

declare(strict_types=1);

namespace Camoo\Http\Curl\Domain\Collection;

use ArrayIterator;
use BFunky\HttpParser\Entity\HttpFieldCollection;
use IteratorAggregate;

final class Headers extends HttpFieldCollection implements IteratorAggregate
{
    public static function default(): self
    {
        return new self();
    }

    public function getIterator(): ArrayIterator
    {
        return new ArrayIterator($this->getHttpFields());
    }
}
