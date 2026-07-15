<?php

namespace Laraditz\Courier\SfExpress;

use Laraditz\Courier\Contracts\CourierDriver;
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
use Laraditz\Courier\Exceptions\AuthenticationException;
use Laraditz\Courier\Exceptions\CourierException;
use Laraditz\Courier\Exceptions\ShipmentNotFoundException;
use Laraditz\Courier\SfExpress\Http\SfExpressClient;
use Laraditz\Courier\SfExpress\Mappers\AvailabilityMapper;
use Laraditz\Courier\SfExpress\Mappers\CancelMapper;
use Laraditz\Courier\SfExpress\Mappers\LabelMapper;
use Laraditz\Courier\SfExpress\Mappers\RateMapper;
use Laraditz\Courier\SfExpress\Mappers\ShipmentMapper;
use Laraditz\Courier\SfExpress\Mappers\TrackingMapper;

class SfExpressDriver implements CourierDriver
{
    private SfExpressClient $client;

    public function __construct(array $config, ?SfExpressClient $client = null)
    {
        $this->client = $client ?? new SfExpressClient($config);
    }

    public function createShipment(ShipmentPayload $payload): ShipmentResult
    {
        $inner = $this->client->dispatch('IUOP_OS_CREATE_ORDER', [
            'customerCode'      => $this->client->customerCode(),
            'interProductCode'  => $payload->serviceCode,
            'parcelQuantity'    => $payload->parcel->quantity ?? 1,
            'parcelTotalWeight' => $payload->parcel->weight,
            'parcelWeightUnit'  => 'KG',
            'parcelVolumeUnit'  => 'CM',
            'parcelTotalLength' => $payload->parcel->length,
            'parcelTotalWidth'  => $payload->parcel->width,
            'parcelTotalHeight' => $payload->parcel->height,
            'remark'            => $payload->remarks ?? '',
            'pickupType'        => 0,
            'paymentInfo'       => [
                'payMethod'    => '1',
                'payMonthCard' => $this->client->payMonthCard(),
            ],
            'parcelInfoList'    => [[
                'name'   => $payload->parcel->description ?? 'Parcel',
                'weight' => $payload->parcel->weight,
            ]],
            'senderInfo'        => $this->formatAddress($payload->sender),
            'receiverInfo'      => $this->formatAddress($payload->recipient),
        ]);

        return ShipmentMapper::map($inner['data']);
    }

    public function getShipment(string $reference): ShipmentResult
    {
        throw new \Laraditz\Courier\Exceptions\UnsupportedOperationException(
            'SF Express does not support order inquiry.'
        );
    }

    public function track(string $trackingNumber): TrackingResult
    {
        try {
            $inner = $this->client->dispatch('IUOP_OS_QUERY_TRACK', [
                'customerCode' => $this->client->customerCode(),
                'sfWaybillNos' => [$trackingNumber],
            ]);
        } catch (AuthenticationException $e) {
            throw $e;
        } catch (CourierException $e) {
            throw new ShipmentNotFoundException(
                "Waybill [{$trackingNumber}] not found.",
                previous: $e
            );
        }

        return TrackingMapper::map($inner['data'], $trackingNumber);
    }

    public function getLabel(string $waybillNumber, ?string $reference = null): LabelResult
    {
        $inner = $this->client->dispatch('IUOP_OS_PRINT_ORDER', [
            'customerCode'          => $this->client->customerCode(),
            'printType'             => 1,
            'printWaybillNoDtoList' => [['sfWaybillNo' => $waybillNumber]],
        ]);

        return LabelMapper::map($inner['data'], $waybillNumber);
    }

    public function cancelShipment(string $waybillNumber, ?string $reference = null): CancelResult
    {
        $inner = $this->client->dispatch('IUOP_OS_CANCEL_ORDER', [
            'customerCode' => $this->client->customerCode(),
            'sfWaybillNo'  => $waybillNumber,
        ]);

        return CancelMapper::map($inner);
    }

    public function getRates(RatePayload $payload): RateCollection
    {
        return RateMapper::map([]);
    }

    public function getAvailability(AvailabilityPayload $payload): ServiceCollection
    {
        return AvailabilityMapper::map([]);
    }

    private function formatAddress(Address $address): array
    {
        return [
            'contact'      => $address->name,
            'country'      => $address->country,
            'phoneNo'      => $address->phone ?? '',
            'postCode'     => $address->postcode,
            'address'      => implode(', ', array_filter([$address->line1, $address->line2, $address->line3])),
            'regionFirst'  => $address->state ?? '',
            'regionSecond' => $address->city ?? '',
            'regionThird'  => '',
            'email'        => $address->email ?? '',
        ];
    }
}
