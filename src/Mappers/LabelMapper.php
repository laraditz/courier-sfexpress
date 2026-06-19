<?php

namespace Laraditz\Courier\SfExpress\Mappers;

use Illuminate\Support\Facades\Http;
use Laraditz\Courier\DTOs\Results\LabelResult;
use Laraditz\Courier\Exceptions\CourierException;

class LabelMapper
{
    public static function map(array $data, string $waybillNumber): LabelResult
    {
        $url      = $data['url'];
        $response = Http::get($url);

        if ($response->failed()) {
            throw new CourierException('Failed to download SF Express label from: ' . $url);
        }

        return new LabelResult(
            waybillNumber: $waybillNumber,
            format: 'pdf',
            content: $response->body(),
            meta: ['label_url' => $url],
        );
    }
}
