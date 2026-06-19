<?php

namespace Laraditz\Courier\SfExpress\Mappers;

use Carbon\Carbon;
use Laraditz\Courier\DTOs\Results\TrackingEvent;
use Laraditz\Courier\DTOs\Results\TrackingResult;
use Laraditz\Courier\Exceptions\ShipmentNotFoundException;

class TrackingMapper
{
    private static array $opCodeMap = [
        '50' => 'picked_up',
        '30' => 'in_transit',
        '70' => 'out_for_delivery',
        '80' => 'delivered',
        '90' => 'failed_delivery',
        '35' => 'returned',
    ];

    public static function map(array $data, string $trackingNumber): TrackingResult
    {
        $entry = $data[0] ?? [];

        if (empty($entry['trackList']) && !empty($entry['trackSummary'])) {
            throw new ShipmentNotFoundException(
                "Waybill [{$trackingNumber}] not found."
            );
        }

        $events = array_map(
            fn (array $step) => new TrackingEvent(
                timestamp:   Carbon::parse($step['localTm']),
                location:    '',
                description: $step['trackOutRemark'] ?: ($step['opCodeTranslation'] ?? ''),
                status:      self::$opCodeMap[$step['opCode'] ?? ''] ?? 'unknown',
            ),
            $entry['trackList'] ?? []
        );

        $latestStatus = !empty($events)
            ? $events[array_key_last($events)]->status
            : 'unknown';

        return new TrackingResult(
            waybillNumber: $entry['sfWaybillNo'] ?? $trackingNumber,
            status: $latestStatus,
            estimatedDelivery: null,
            events: $events,
            meta: ['track_summary' => $entry['trackSummary'] ?? null],
        );
    }
}
