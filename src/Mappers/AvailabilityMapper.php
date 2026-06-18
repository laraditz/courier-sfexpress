<?php

namespace Laraditz\Courier\SfExpress\Mappers;

use Laraditz\Courier\DTOs\Results\ServiceCollection;
use Laraditz\Courier\DTOs\Results\ServiceOption;

class AvailabilityMapper
{
    public static function map(array $data): ServiceCollection
    {
        $items = array_map(fn (array $item) => new ServiceOption(
            code: $item['serviceCode'],
            name: $item['serviceName'],
            description: $item['description'] ?? '',
            estimatedDays: isset($item['promisedDeliveryDays']) ? (int) $item['promisedDeliveryDays'] : null,
        ), $data['serviceList'] ?? []);

        return new ServiceCollection($items);
    }
}
