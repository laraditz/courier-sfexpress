<?php

namespace Laraditz\Courier\SfExpress\Mappers;

use Laraditz\Courier\DTOs\Results\LabelResult;

class LabelMapper
{
    public static function map(array $data): LabelResult
    {
        $format = strtolower($data['labelType'] ?? 'pdf');

        return new LabelResult(
            waybillNumber: $data['waybillNo'],
            format: $format,
            content: $data['labelContent'],
            meta: ['raw_label_type' => $data['labelType'] ?? null],
        );
    }
}
