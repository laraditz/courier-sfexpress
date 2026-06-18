<?php

namespace Laraditz\Courier\SfExpress\Mappers;

use Carbon\Carbon;
use Laraditz\Courier\DTOs\Results\ShipmentResult;

class ShipmentMapper
{
    public static function map(array $data): ShipmentResult
    {
        $estimatedDelivery = null;
        $rawDate = $data['routeInfo']['estimatedDeliveryDate'] ?? null;
        if ($rawDate) {
            $estimatedDelivery = Carbon::parse($rawDate);
        }

        return new ShipmentResult(
            waybillNumber: $data['waybillNo'],
            status: 'pending',
            estimatedDelivery: $estimatedDelivery,
            meta: ['raw_waybill_no' => $data['waybillNo']],
        );
    }
}
