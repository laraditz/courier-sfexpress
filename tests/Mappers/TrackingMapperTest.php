<?php

namespace Laraditz\Courier\SfExpress\Tests\Mappers;

use Laraditz\Courier\DTOs\Results\TrackingEvent;
use Laraditz\Courier\DTOs\Results\TrackingResult;
use Laraditz\Courier\Exceptions\ShipmentNotFoundException;
use Laraditz\Courier\SfExpress\Mappers\TrackingMapper;
use Laraditz\Courier\SfExpress\Tests\TestCase;

class TrackingMapperTest extends TestCase
{
    private array $successData;

    protected function setUp(): void
    {
        parent::setUp();
        $this->successData = [
            [
                'sfWaybillNo'  => 'MYIU1234703282',
                'trackSummary' => '',
                'trackList'    => [
                    [
                        'opCode'            => '50',
                        'opCodeTranslation' => 'One Ticket One Piece',
                        'localTm'           => '2024-03-26 10:00:00',
                        'trackOutRemark'    => 'Parcel picked up',
                    ],
                    [
                        'opCode'            => '70',
                        'opCodeTranslation' => 'Parcel Out for Delivery',
                        'localTm'           => '2024-03-26 21:41:09',
                        'trackOutRemark'    => 'Parcel out for delivery',
                    ],
                ],
            ],
        ];
    }

    public function test_maps_track_list_to_tracking_events(): void
    {
        $result = TrackingMapper::map($this->successData, 'MYIU1234703282');

        $this->assertInstanceOf(TrackingResult::class, $result);
        $this->assertSame('MYIU1234703282', $result->waybillNumber);
        $this->assertCount(2, $result->events);
        $this->assertInstanceOf(TrackingEvent::class, $result->events[0]);
    }

    public function test_maps_op_codes_to_normalized_statuses(): void
    {
        $result = TrackingMapper::map($this->successData, 'MYIU1234703282');

        $this->assertSame('picked_up', $result->events[0]->status);
        $this->assertSame('out_for_delivery', $result->events[1]->status);
        $this->assertSame('out_for_delivery', $result->status);
    }

    public function test_uses_track_out_remark_as_description(): void
    {
        $result = TrackingMapper::map($this->successData, 'MYIU1234703282');

        $this->assertSame('Parcel picked up', $result->events[0]->description);
    }

    public function test_falls_back_to_op_code_translation_when_remark_is_empty(): void
    {
        $data = [[
            'sfWaybillNo'  => 'MYIU999',
            'trackSummary' => '',
            'trackList'    => [[
                'opCode'            => '80',
                'opCodeTranslation' => 'Delivered',
                'localTm'           => '2024-03-27 12:00:00',
                'trackOutRemark'    => '',
            ]],
        ]];

        $result = TrackingMapper::map($data, 'MYIU999');

        $this->assertSame('Delivered', $result->events[0]->description);
        $this->assertSame('delivered', $result->events[0]->status);
    }

    public function test_throws_shipment_not_found_when_track_list_empty_and_summary_non_empty(): void
    {
        $data = [[
            'sfWaybillNo'  => 'MYIU1234713791',
            'trackSummary' => '运单号[MYIU1234713791]不属于当前客户',
            'trackList'    => [],
        ]];

        $this->expectException(ShipmentNotFoundException::class);
        $this->expectExceptionMessageMatches('/MYIU1234713791/');

        TrackingMapper::map($data, 'MYIU1234713791');
    }

    public function test_returns_unknown_status_for_unrecognised_op_code(): void
    {
        $data = [[
            'sfWaybillNo'  => 'MYIU001',
            'trackSummary' => '',
            'trackList'    => [[
                'opCode'            => '99',
                'opCodeTranslation' => 'Some unknown status',
                'localTm'           => '2024-03-27 12:00:00',
                'trackOutRemark'    => 'Unknown',
            ]],
        ]];

        $result = TrackingMapper::map($data, 'MYIU001');

        $this->assertSame('unknown', $result->events[0]->status);
    }
}
