<?php

namespace Laraditz\Courier\SfExpress\Mappers;

use Laraditz\Courier\Exceptions\UnsupportedOperationException;

class RateMapper
{
    public static function map(array $data): never
    {
        throw new UnsupportedOperationException(
            'Not supported for SF Express Domestic.'
        );
    }
}
