<?php

namespace Laraditz\Courier\SfExpress\Tests\Mappers;

use Laraditz\Courier\DTOs\Results\RateCollection;
use Laraditz\Courier\SfExpress\Mappers\RateMapper;
use Laraditz\Courier\SfExpress\Tests\TestCase;

class RateMapperTest extends TestCase
{
    public function test_maps_rates_response(): void
    {
        $data = $this->fixture('get-rates-success')['apiResultData'];

        $collection = RateMapper::map($data);

        $this->assertInstanceOf(RateCollection::class, $collection);
        $this->assertCount(2, $collection->items);
        $this->assertSame('STANDARD', $collection->items[0]->serviceCode);
        $this->assertSame(12.50, $collection->items[0]->price);
        $this->assertSame('MYR', $collection->items[0]->currency);
        $this->assertSame(3, $collection->items[0]->estimatedDays);
    }

    public function test_returns_empty_collection_when_no_results(): void
    {
        $collection = RateMapper::map(['queryResult' => []]);

        $this->assertCount(0, $collection->items);
    }
}
