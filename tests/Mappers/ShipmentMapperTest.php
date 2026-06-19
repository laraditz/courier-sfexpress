<?php

namespace Laraditz\Courier\SfExpress\Tests\Mappers;

use Laraditz\Courier\DTOs\Results\ShipmentResult;
use Laraditz\Courier\SfExpress\Mappers\ShipmentMapper;
use Laraditz\Courier\SfExpress\Tests\TestCase;

class ShipmentMapperTest extends TestCase
{
    public function test_maps_success_data_to_shipment_result(): void
    {
        $data = [
            'sfWaybillNo'     => 'MYIU1234715622',
            'labelUrl'        => 'https://storage.example.com/label.pdf',
            'customerOrderNo' => '1713259580917094',
        ];

        $result = ShipmentMapper::map($data);

        $this->assertInstanceOf(ShipmentResult::class, $result);
        $this->assertSame('MYIU1234715622', $result->waybillNumber);
        $this->assertSame('pending', $result->status);
        $this->assertNull($result->estimatedDelivery);
        $this->assertSame('https://storage.example.com/label.pdf', $result->meta()['label_url']);
        $this->assertSame('1713259580917094', $result->meta()['customer_order_no']);
    }

    public function test_handles_missing_optional_fields(): void
    {
        $data = ['sfWaybillNo' => 'MYIU999'];

        $result = ShipmentMapper::map($data);

        $this->assertSame('MYIU999', $result->waybillNumber);
        $this->assertNull($result->meta()['label_url']);
        $this->assertNull($result->meta()['customer_order_no']);
    }
}
