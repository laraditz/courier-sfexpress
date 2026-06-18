<?php

namespace Laraditz\Courier\SfExpress\Tests;

use Illuminate\Support\Facades\Http;
use Laraditz\Courier\DTOs\Payloads\AvailabilityPayload;
use Laraditz\Courier\DTOs\Payloads\RatePayload;
use Laraditz\Courier\DTOs\Payloads\ShipmentPayload;
use Laraditz\Courier\DTOs\Results\CancelResult;
use Laraditz\Courier\DTOs\Results\LabelResult;
use Laraditz\Courier\DTOs\Results\RateCollection;
use Laraditz\Courier\DTOs\Results\ServiceCollection;
use Laraditz\Courier\DTOs\Results\ShipmentResult;
use Laraditz\Courier\DTOs\Results\TrackingResult;
use Laraditz\Courier\DTOs\Shared\Address;
use Laraditz\Courier\DTOs\Shared\Location;
use Laraditz\Courier\DTOs\Shared\Parcel;
use Laraditz\Courier\Exceptions\ShipmentNotFoundException;
use Laraditz\Courier\SfExpress\SfExpressDriver;

class SfExpressDriverTest extends TestCase
{
    private SfExpressDriver $driver;

    protected function getEnvironmentSetUp($app): void
    {
        parent::getEnvironmentSetUp($app);
        $app['config']->set('courier.drivers.sfexpress.sandbox_url', 'https://sfapi-sandbox.sf-express.com/std/service');
    }

    protected function setUp(): void
    {
        parent::setUp();
        $this->driver = new SfExpressDriver(config('courier.drivers.sfexpress'));
    }

    private function makeAddress(): Address
    {
        return new Address('Farhan', '+60123456789', null, 'No 1 Jalan Test', null, null, 'KL', 'WP', '50000', 'MY');
    }

    private function makeParcel(): Parcel
    {
        return new Parcel(1.5, 20.0, 15.0, 10.0, 100.0, 'Goods', 1);
    }

    public function test_create_shipment_returns_shipment_result(): void
    {
        Http::fake([
            '*/oauth2/accessToken' => Http::response(['access_token' => 'fake-token'], 200),
            '*' => Http::response($this->fixture('create-shipment-success'), 200),
        ]);

        $result = $this->driver->createShipment(new ShipmentPayload(
            sender: $this->makeAddress(),
            recipient: $this->makeAddress(),
            parcel: $this->makeParcel(),
            serviceCode: 'STANDARD',
        ));

        $this->assertInstanceOf(ShipmentResult::class, $result);
        $this->assertSame('SF1234567890', $result->waybillNumber);
    }

    public function test_track_returns_tracking_result(): void
    {
        Http::fake([
            '*/oauth2/accessToken' => Http::response(['access_token' => 'fake-token'], 200),
            '*' => Http::response($this->fixture('track-success'), 200),
        ]);

        $result = $this->driver->track('SF1234567890');

        $this->assertInstanceOf(TrackingResult::class, $result);
        $this->assertSame('SF1234567890', $result->waybillNumber);
        $this->assertCount(2, $result->events);
    }

    public function test_track_throws_shipment_not_found_exception(): void
    {
        Http::fake([
            '*/oauth2/accessToken' => Http::response(['access_token' => 'fake-token'], 200),
            '*' => Http::response($this->fixture('track-not-found'), 200),
        ]);

        $this->expectException(ShipmentNotFoundException::class);

        $this->driver->track('SF-INVALID');
    }

    public function test_get_rates_returns_rate_collection(): void
    {
        Http::fake([
            '*/oauth2/accessToken' => Http::response(['access_token' => 'fake-token'], 200),
            '*' => Http::response($this->fixture('get-rates-success'), 200),
        ]);

        $result = $this->driver->getRates(new RatePayload(
            origin: new Location('50000', 'KL', 'WP', 'MY'),
            destination: new Location('10000', 'Georgetown', 'Penang', 'MY'),
            parcel: $this->makeParcel(),
        ));

        $this->assertInstanceOf(RateCollection::class, $result);
        $this->assertCount(2, $result->items);
    }

    public function test_cancel_shipment_returns_cancel_result(): void
    {
        Http::fake([
            '*/oauth2/accessToken' => Http::response(['access_token' => 'fake-token'], 200),
            '*' => Http::response($this->fixture('cancel-success'), 200),
        ]);

        $result = $this->driver->cancelShipment('SF1234567890');

        $this->assertInstanceOf(CancelResult::class, $result);
        $this->assertTrue($result->success);
    }

    public function test_get_label_returns_label_result(): void
    {
        Http::fake([
            '*/oauth2/accessToken' => Http::response(['access_token' => 'fake-token'], 200),
            '*' => Http::response($this->fixture('get-label-success'), 200),
        ]);

        $result = $this->driver->getLabel('SF1234567890');

        $this->assertInstanceOf(LabelResult::class, $result);
        $this->assertSame('pdf', $result->format);
    }

    public function test_get_availability_returns_service_collection(): void
    {
        Http::fake([
            '*/oauth2/accessToken' => Http::response(['access_token' => 'fake-token'], 200),
            '*' => Http::response($this->fixture('get-availability-success'), 200),
        ]);

        $result = $this->driver->getAvailability(new AvailabilityPayload(
            origin: new Location('50000', 'KL', 'WP', 'MY'),
            destination: new Location('10000', 'Georgetown', 'Penang', 'MY'),
        ));

        $this->assertInstanceOf(ServiceCollection::class, $result);
        $this->assertCount(2, $result->items);
    }
}
