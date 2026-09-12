<?php

declare(strict_types=1);

namespace Camoo\Http\Curl\Test\Fixture;

use Camoo\Http\Curl\Application\Query\MultiCurlQueryInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

final class MultiCurlQueryMock
{
    public function __construct(private TestCase $testCase)
    {
    }

    public static function create(TestCase $testCase): self
    {
        return new self($testCase);
    }

    public function getMock(): MultiCurlQueryInterface|MockObject
    {
        return $this->testCase->getMockBuilder(MultiCurlQueryInterface::class)
            ->onlyMethods([
                'addHandle',
                'removeHandle',
                'exec',
                'select',
                'close',
                'infoRead',
            ])->getMock();
    }
}
