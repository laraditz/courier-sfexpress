<?php

namespace Laraditz\Courier\SfExpress\Tests\Mappers;

use Laraditz\Courier\DTOs\Results\LabelResult;
use Laraditz\Courier\SfExpress\Mappers\LabelMapper;
use Laraditz\Courier\SfExpress\Tests\TestCase;

class LabelMapperTest extends TestCase
{
    public function test_maps_label_response_as_pdf(): void
    {
        $data = $this->fixture('get-label-success')['apiResultData'];

        $result = LabelMapper::map($data);

        $this->assertInstanceOf(LabelResult::class, $result);
        $this->assertSame('SF1234567890', $result->waybillNumber);
        $this->assertSame('pdf', $result->format);
        $this->assertSame('AAAABBBBCCCCDDDD', $result->content);
    }

    public function test_maps_zpl_label(): void
    {
        $data = ['waybillNo' => 'SF001', 'labelType' => 'ZPL', 'labelContent' => base64_encode('^XA^XZ')];

        $result = LabelMapper::map($data);

        $this->assertSame('zpl', $result->format);
        $this->assertSame(base64_encode('^XA^XZ'), $result->content);
    }
}
