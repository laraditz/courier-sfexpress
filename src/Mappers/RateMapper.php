<?php

namespace Laraditz\Courier\SfExpress\Mappers;

use Laraditz\Courier\DTOs\Results\RateCollection;
use Laraditz\Courier\DTOs\Results\RateOption;

class RateMapper
{
    public static function map(array $data): RateCollection
    {
        $items = array_map(fn (array $item) => new RateOption(
            serviceCode: $item['serviceCode'],
            serviceName: $item['serviceName'],
            price: (float) $item['totalCost'],
            currency: $item['currency'],
            estimatedDays: isset($item['promisedDeliveryDays']) ? (int) $item['promisedDeliveryDays'] : null,
        ), $data['queryResult'] ?? []);

        return new RateCollection($items);
    }
}
