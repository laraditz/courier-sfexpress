<?php

namespace Laraditz\Courier\SfExpress\Tests\Mappers;

use Laraditz\Courier\DTOs\Results\CancelResult;
use Laraditz\Courier\SfExpress\Mappers\CancelMapper;
use Laraditz\Courier\SfExpress\Tests\TestCase;

class CancelMapperTest extends TestCase
{
    public function test_maps_successful_cancellation(): void
    {
        $inner = ['success' => true, 'code' => '0', 'msg' => 'success'];

        $result = CancelMapper::map($inner);

        $this->assertInstanceOf(CancelResult::class, $result);
        $this->assertTrue($result->success);
        $this->assertSame('success', $result->message);
        $this->assertSame('0', $result->meta()['code']);
    }

    public function test_maps_failed_cancellation(): void
    {
        $inner = ['success' => false, 'code' => '9001', 'msg' => 'Shipment already picked up'];

        $result = CancelMapper::map($inner);

        $this->assertFalse($result->success);
        $this->assertSame('Shipment already picked up', $result->message);
    }

    public function test_falls_back_to_default_message_when_msg_missing(): void
    {
        $inner = ['success' => true, 'code' => '0'];

        $result = CancelMapper::map($inner);

        $this->assertTrue($result->success);
        $this->assertSame('Cancelled.', $result->message);
    }
}
