<?php

namespace Laraditz\Courier\SfExpress\Tests\Mappers;

use Laraditz\Courier\DTOs\Results\TrackingEvent;
use Laraditz\Courier\DTOs\Results\TrackingResult;
use Laraditz\Courier\SfExpress\Mappers\TrackingMapper;
use Laraditz\Courier\SfExpress\Tests\TestCase;

class TrackingMapperTest extends TestCase
{
    public function test_maps_tracking_response(): void
    {
        $data = $this->fixture('track-success')['apiResultData'];

        $result = TrackingMapper::map($data);

        $this->assertInstanceOf(TrackingResult::class, $result);
        $this->assertSame('SF1234567890', $result->waybillNumber);
        $this->assertCount(2, $result->events);
        $this->assertInstanceOf(TrackingEvent::class, $result->events[0]);
        $this->assertSame('Kuala Lumpur Hub', $result->events[0]->location);
        $this->assertSame('picked_up', $result->events[0]->status);
        $this->assertSame('in_transit', $result->events[1]->status);
    }

    public function test_maps_op_codes_to_normalized_statuses(): void
    {
        $data = [
            'waybillNo' => 'SF001',
            'routeSteps' => [
                ['acceptTime' => '2026-06-17 10:00:00', 'acceptAddress' => 'Hub', 'remark' => 'Picked up', 'opCode' => '50'],
                ['acceptTime' => '2026-06-17 15:00:00', 'acceptAddress' => 'Depot', 'remark' => 'Delivered', 'opCode' => '80'],
            ],
        ];

        $result = TrackingMapper::map($data);

        $this->assertSame('delivered', $result->status);
        $this->assertSame('picked_up', $result->events[0]->status);
        $this->assertSame('delivered', $result->events[1]->status);
    }
}
