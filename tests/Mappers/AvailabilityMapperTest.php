<?php

namespace Laraditz\Courier\SfExpress\Tests\Mappers;

use Laraditz\Courier\Exceptions\UnsupportedOperationException;
use Laraditz\Courier\SfExpress\Mappers\AvailabilityMapper;
use Laraditz\Courier\SfExpress\Tests\TestCase;

class AvailabilityMapperTest extends TestCase
{
    public function test_throws_unsupported_operation_exception(): void
    {
        $this->expectException(UnsupportedOperationException::class);
        $this->expectExceptionMessageMatches('/Not supported/');

        AvailabilityMapper::map([]);
    }
}
