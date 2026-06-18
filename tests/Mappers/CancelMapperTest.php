<?php

namespace Laraditz\Courier\SfExpress\Tests\Mappers;

use Laraditz\Courier\DTOs\Results\CancelResult;
use Laraditz\Courier\SfExpress\Mappers\CancelMapper;
use Laraditz\Courier\SfExpress\Tests\TestCase;

class CancelMapperTest extends TestCase
{
    public function test_maps_cancel_success(): void
    {
        $data = $this->fixture('cancel-success')['apiResultData'];

        $result = CancelMapper::map($data);

        $this->assertInstanceOf(CancelResult::class, $result);
        $this->assertTrue($result->success);
        $this->assertSame('SUCCESS', $result->meta()['cancel_result']);
    }

    public function test_maps_cancel_failure(): void
    {
        $data = ['waybillNo' => 'SF001', 'cancelResult' => 'FAILURE'];

        $result = CancelMapper::map($data);

        $this->assertFalse($result->success);
    }
}
