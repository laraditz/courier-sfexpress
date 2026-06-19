<?php

namespace Laraditz\Courier\SfExpress\Tests\Mappers;

use Laraditz\Courier\Exceptions\UnsupportedOperationException;
use Laraditz\Courier\SfExpress\Mappers\RateMapper;
use Laraditz\Courier\SfExpress\Tests\TestCase;

class RateMapperTest extends TestCase
{
    public function test_throws_unsupported_operation_exception(): void
    {
        $this->expectException(UnsupportedOperationException::class);
        $this->expectExceptionMessageMatches('/Not supported/');

        RateMapper::map([]);
    }
}
