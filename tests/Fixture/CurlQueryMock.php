<?php

declare(strict_types=1);

namespace Camoo\Http\Curl\Test\Fixture;

use Camoo\Http\Curl\Application\Query\CurlQueryInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

final class CurlQueryMock
{
    private const URL = 'localhost';

    private const DEFAULT_HTTP_CODE = 200;

    public function __construct(private TestCase $testCase)
    {
    }

    public static function create(TestCase $testCase): self
    {
        return new self($testCase);
    }

    public function getMock(): CurlQueryInterface|MockObject
    {
        return $this->testCase->getMockBuilder(CurlQueryInterface::class)
            ->onlyMethods([
                'execute',
                'getInfo',
                'getErrorMessage',
                'getErrorNumber',
                'close',
                'setOption',
            ])->getMock();
    }

    public function getFixture(?int $httpCode = null): CurlClientFixture
    {
        $httpCode = $httpCode ?? self::DEFAULT_HTTP_CODE;

        return new CurlClientFixture(self::URL, $httpCode);
    }
}
