<?php

namespace Laraditz\Courier\SfExpress\Mappers;

use Laraditz\Courier\DTOs\Results\ShipmentResult;

class ShipmentMapper
{
    public static function map(array $data): ShipmentResult
    {
        return new ShipmentResult(
            waybillNumber: $data['sfWaybillNo'],
            status: 'pending',
            estimatedDelivery: null,
            meta: [
                'label_url'         => $data['labelUrl'] ?? null,
                'customer_order_no' => $data['customerOrderNo'] ?? null,
            ],
        );
    }
}
