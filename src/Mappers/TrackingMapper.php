<?php

namespace Laraditz\Courier\SfExpress\Mappers;

use Carbon\Carbon;
use Laraditz\Courier\DTOs\Results\TrackingEvent;
use Laraditz\Courier\DTOs\Results\TrackingResult;

class TrackingMapper
{
    // SF Express opCode → normalized status vocabulary
    private static array $opCodeMap = [
        '50' => 'picked_up',
        '30' => 'in_transit',
        '70' => 'out_for_delivery',
        '80' => 'delivered',
        '90' => 'failed_delivery',
        '35' => 'returned',
    ];

    public static function map(array $data): TrackingResult
    {
        $steps = $data['routeSteps'] ?? [];
        $events = array_map(fn (array $step) => new TrackingEvent(
            timestamp: Carbon::parse($step['acceptTime']),
            location: $step['acceptAddress'] ?? '',
            description: $step['remark'] ?? '',
            status: self::$opCodeMap[$step['opCode'] ?? ''] ?? 'unknown',
        ), $steps);

        $latestStatus = !empty($events)
            ? $events[array_key_last($events)]->status
            : 'unknown';

        return new TrackingResult(
            waybillNumber: $data['waybillNo'],
            status: $latestStatus,
            estimatedDelivery: null,
            events: $events,
            meta: ['raw_steps' => $steps],
        );
    }
}
