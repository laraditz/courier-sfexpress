<?php

namespace Laraditz\Courier\SfExpress\Mappers;

use Laraditz\Courier\DTOs\Results\CancelResult;

class CancelMapper
{
    public static function map(array $data): CancelResult
    {
        $success = ($data['cancelResult'] ?? '') === 'SUCCESS';

        return new CancelResult(
            success: $success,
            message: $success ? 'Shipment cancelled successfully.' : 'Cancellation failed.',
            meta: ['cancel_result' => $data['cancelResult'] ?? null],
        );
    }
}
