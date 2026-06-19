<?php

namespace Laraditz\Courier\SfExpress\Mappers;

use Laraditz\Courier\DTOs\Results\CancelResult;

class CancelMapper
{
    public static function map(array $inner): CancelResult
    {
        $success = (bool) ($inner['success'] ?? false);

        return new CancelResult(
            success: $success,
            message: $inner['msg'] ?? ($success ? 'Cancelled.' : 'Cancellation failed.'),
            meta: ['code' => $inner['code'] ?? null],
        );
    }
}
