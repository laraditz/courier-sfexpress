<?php

namespace Laraditz\Courier\SfExpress\Tests\Mappers;

use Laraditz\Courier\DTOs\Results\ServiceCollection;
use Laraditz\Courier\SfExpress\Mappers\AvailabilityMapper;
use Laraditz\Courier\SfExpress\Tests\TestCase;

class AvailabilityMapperTest extends TestCase
{
    public function test_maps_availability_response(): void
    {
        $data = $this->fixture('get-availability-success')['apiResultData'];

        $collection = AvailabilityMapper::map($data);

        $this->assertInstanceOf(ServiceCollection::class, $collection);
        $this->assertCount(2, $collection->items);
        $this->assertSame('STANDARD', $collection->items[0]->code);
        $this->assertSame('Standard Delivery', $collection->items[0]->name);
        $this->assertSame(4, $collection->items[0]->estimatedDays);
    }
}
