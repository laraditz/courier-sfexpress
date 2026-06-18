<?php

namespace Laraditz\Courier\SfExpress\Tests\Mappers;

use Laraditz\Courier\DTOs\Results\ShipmentResult;
use Laraditz\Courier\SfExpress\Mappers\ShipmentMapper;
use Laraditz\Courier\SfExpress\Tests\TestCase;

class ShipmentMapperTest extends TestCase
{
    public function test_maps_success_response_to_shipment_result(): void
    {
        $data = $this->fixture('create-shipment-success')['apiResultData'];

        $result = ShipmentMapper::map($data);

        $this->assertInstanceOf(ShipmentResult::class, $result);
        $this->assertSame('SF1234567890', $result->waybillNumber);
        $this->assertSame('pending', $result->status);
        $this->assertNotNull($result->estimatedDelivery);
        $this->assertSame('2026-06-19', $result->estimatedDelivery->format('Y-m-d'));
        $this->assertSame('SF1234567890', $result->meta()['raw_waybill_no']);
    }

    public function test_handles_missing_estimated_delivery(): void
    {
        $data = ['waybillNo' => 'SF999', 'routeInfo' => []];

        $result = ShipmentMapper::map($data);

        $this->assertSame('SF999', $result->waybillNumber);
        $this->assertNull($result->estimatedDelivery);
    }
}
