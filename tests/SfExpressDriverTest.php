<?php

namespace Laraditz\Courier\SfExpress\Tests;

use Illuminate\Support\Facades\Http;
use Laraditz\Courier\DTOs\Payloads\AvailabilityPayload;
use Laraditz\Courier\DTOs\Payloads\RatePayload;
use Laraditz\Courier\DTOs\Payloads\ShipmentPayload;
use Laraditz\Courier\DTOs\Results\CancelResult;
use Laraditz\Courier\DTOs\Results\LabelResult;
use Laraditz\Courier\DTOs\Results\ShipmentResult;
use Laraditz\Courier\DTOs\Results\TrackingResult;
use Laraditz\Courier\DTOs\Shared\Address;
use Laraditz\Courier\DTOs\Shared\Location;
use Laraditz\Courier\DTOs\Shared\Parcel;
use Laraditz\Courier\Exceptions\ShipmentNotFoundException;
use Laraditz\Courier\Exceptions\UnsupportedOperationException;
use Laraditz\Courier\SfExpress\Http\SfExpressClient;
use Laraditz\Courier\SfExpress\SfExpressDriver;

class SfExpressDriverTest extends TestCase
{
    private function makeAddress(): Address
    {
        return new Address('Farhan', '+60123456789', null, 'No 1 Jalan Test', null, null, 'KL', 'WP', '50000', 'MY');
    }

    private function makeParcel(): Parcel
    {
        return new Parcel(1.5, 20.0, 15.0, 10.0, 100.0, 'Goods', 1);
    }

    private function makeClient(array $dispatchReturn = []): SfExpressClient
    {
        $client = $this->createMock(SfExpressClient::class);
        $client->method('customerCode')->willReturn('TEST-CUSTOMER-CODE');
        $client->method('payMonthCard')->willReturn('TESTJACK0004');
        $client->method('dispatch')->willReturn($dispatchReturn);

        return $client;
    }

    private function makeDriver(array $dispatchReturn = []): SfExpressDriver
    {
        return new SfExpressDriver([], $this->makeClient($dispatchReturn));
    }

    public function test_create_shipment_returns_shipment_result(): void
    {
        $driver = $this->makeDriver([
            'success' => true,
            'code'    => '0',
            'msg'     => 'ok',
            'data'    => [
                'sfWaybillNo'     => 'MYIU1234715622',
                'labelUrl'        => 'https://example.com/label.pdf',
                'customerOrderNo' => '1713259580917094',
            ],
        ]);

        $result = $driver->createShipment(new ShipmentPayload(
            sender: $this->makeAddress(),
            recipient: $this->makeAddress(),
            parcel: $this->makeParcel(),
            serviceCode: 'M102',
        ));

        $this->assertInstanceOf(ShipmentResult::class, $result);
        $this->assertSame('MYIU1234715622', $result->waybillNumber);
        $this->assertSame('pending', $result->status);
        $this->assertSame('https://example.com/label.pdf', $result->meta()['label_url']);
    }

    public function test_create_shipment_sends_correct_msg_type(): void
    {
        $client = $this->createMock(SfExpressClient::class);
        $client->method('customerCode')->willReturn('TEST-CODE');
        $client->method('payMonthCard')->willReturn('TESTJACK0004');
        $client->expects($this->once())
            ->method('dispatch')
            ->with('IUOP_OS_CREATE_ORDER', $this->arrayHasKey('interProductCode'))
            ->willReturn([
                'success' => true, 'code' => '0', 'msg' => 'ok',
                'data'    => ['sfWaybillNo' => 'MYIU001', 'labelUrl' => null, 'customerOrderNo' => null],
            ]);

        $driver = new SfExpressDriver([], $client);
        $driver->createShipment(new ShipmentPayload(
            sender: $this->makeAddress(),
            recipient: $this->makeAddress(),
            parcel: $this->makeParcel(),
            serviceCode: 'M102',
        ));
    }

    public function test_track_returns_tracking_result(): void
    {
        $driver = $this->makeDriver([
            'success' => true,
            'code'    => '0',
            'msg'     => 'ok',
            'data'    => [[
                'sfWaybillNo'  => 'MYIU1234703282',
                'trackSummary' => '',
                'trackList'    => [
                    [
                        'opCode'            => '50',
                        'opCodeTranslation' => 'Picked up',
                        'localTm'           => '2024-03-26 10:00:00',
                        'trackOutRemark'    => 'Parcel picked up',
                    ],
                ],
            ]],
        ]);

        $result = $driver->track('MYIU1234703282');

        $this->assertInstanceOf(TrackingResult::class, $result);
        $this->assertSame('MYIU1234703282', $result->waybillNumber);
        $this->assertCount(1, $result->events);
        $this->assertSame('picked_up', $result->status);
    }

    public function test_track_throws_shipment_not_found_when_waybill_unknown(): void
    {
        $driver = $this->makeDriver([
            'success' => true,
            'code'    => '0',
            'msg'     => 'ok',
            'data'    => [[
                'sfWaybillNo'  => 'MYIU-INVALID',
                'trackSummary' => '运单号[MYIU-INVALID]不属于当前客户',
                'trackList'    => [],
            ]],
        ]);

        $this->expectException(ShipmentNotFoundException::class);

        $driver->track('MYIU-INVALID');
    }

    public function test_get_label_downloads_pdf_and_returns_label_result(): void
    {
        Http::fake([
            'https://example.com/label.pdf' => Http::response('%PDF-1.4 content', 200),
        ]);

        $driver = $this->makeDriver([
            'success' => true,
            'code'    => '0',
            'msg'     => 'ok',
            'data'    => ['url' => 'https://example.com/label.pdf'],
        ]);

        $result = $driver->getLabel('MYIU1234715622');

        $this->assertInstanceOf(LabelResult::class, $result);
        $this->assertSame('MYIU1234715622', $result->waybillNumber);
        $this->assertSame('pdf', $result->format);
        $this->assertSame('%PDF-1.4 content', $result->content);
        $this->assertSame('https://example.com/label.pdf', $result->meta()['label_url']);
    }

    public function test_cancel_shipment_returns_cancel_result(): void
    {
        $driver = $this->makeDriver([
            'success' => true,
            'code'    => '0',
            'msg'     => 'success',
        ]);

        $result = $driver->cancelShipment('MYIU1234715622');

        $this->assertInstanceOf(CancelResult::class, $result);
        $this->assertTrue($result->success);
    }

    public function test_get_shipment_throws_unsupported_operation_exception(): void
    {
        $this->expectException(UnsupportedOperationException::class);

        $driver = new SfExpressDriver([], $this->makeClient());
        $driver->getShipment('MYIU1234715622');
    }

    public function test_get_rates_throws_unsupported_operation_exception(): void
    {
        $this->expectException(UnsupportedOperationException::class);

        $driver = new SfExpressDriver([], $this->makeClient());
        $driver->getRates(new RatePayload(
            origin: new Location('50000', 'KL', 'WP', 'MY'),
            destination: new Location('10000', 'Georgetown', 'Penang', 'MY'),
            parcel: $this->makeParcel(),
        ));
    }

    public function test_get_availability_throws_unsupported_operation_exception(): void
    {
        $this->expectException(UnsupportedOperationException::class);

        $driver = new SfExpressDriver([], $this->makeClient());
        $driver->getAvailability(new AvailabilityPayload(
            origin: new Location('50000', 'KL', 'WP', 'MY'),
            destination: new Location('10000', 'Georgetown', 'Penang', 'MY'),
        ));
    }
}
