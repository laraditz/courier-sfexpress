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

    public function __construct(array $config)
    {
        $this->client = new SfExpressClient($config);
    }

    public function createShipment(ShipmentPayload $payload): ShipmentResult
    {
        $data = $this->client->post('/shipment/create', [
            'language'    => 'en',
            'partnerID'   => $this->client->account(),
            'serviceCode' => $payload->serviceCode,
            'sender'      => $this->formatAddress($payload->sender),
            'recipient'   => $this->formatAddress($payload->recipient),
            'cargo' => [
                'weight'        => $payload->parcel->weight,
                'length'        => $payload->parcel->length,
                'width'         => $payload->parcel->width,
                'height'        => $payload->parcel->height,
                'declaredValue' => $payload->parcel->declaredValue,
                'goodsDesc'     => $payload->parcel->description,
                'quantity'      => $payload->parcel->quantity,
            ],
            'remark' => $payload->remarks,
        ]);

        return ShipmentMapper::map($data);
    }

    public function track(string $trackingNumber): TrackingResult
    {
        try {
            $data = $this->client->post('/shipment/route', [
                'language'       => 'en',
                'trackingType'   => 1,
                'trackingNumber' => [$trackingNumber],
            ]);
        } catch (CourierException $e) {
            if (str_contains($e->getMessage(), 'A2002') || str_contains($e->getMessage(), 'not found')) {
                throw new ShipmentNotFoundException(
                    "Waybill [{$trackingNumber}] not found.",
                    previous: $e
                );
            }
            throw $e;
        }

        return TrackingMapper::map($data);
    }

    public function getRates(RatePayload $payload): RateCollection
    {
        $data = $this->client->post('/shipment/queryFreight', [
            'language' => 'en',
            'originAddress' => [
                'country'  => $payload->origin->country,
                'province' => $payload->origin->state,
                'city'     => $payload->origin->city,
                'postcode' => $payload->origin->postcode,
            ],
            'destAddress' => [
                'country'  => $payload->destination->country,
                'province' => $payload->destination->state,
                'city'     => $payload->destination->city,
                'postcode' => $payload->destination->postcode,
            ],
            'weight' => $payload->parcel->weight,
        ]);

        return RateMapper::map($data);
    }

    public function cancelShipment(string $waybillNumber): CancelResult
    {
        $data = $this->client->post('/shipment/cancel', [
            'language'  => 'en',
            'waybillNo' => $waybillNumber,
        ]);

        return CancelMapper::map($data);
    }

    public function getLabel(string $waybillNumber): LabelResult
    {
        $data = $this->client->post('/shipment/label', [
            'language'  => 'en',
            'waybillNo' => $waybillNumber,
            'labelType' => 'PDF',
        ]);

        return LabelMapper::map($data);
    }

    public function getAvailability(AvailabilityPayload $payload): ServiceCollection
    {
        $data = $this->client->post('/service/queryByAddress', [
            'language' => 'en',
            'originAddress' => [
                'country'  => $payload->origin->country,
                'province' => $payload->origin->state,
                'city'     => $payload->origin->city,
                'postcode' => $payload->origin->postcode,
            ],
            'destAddress' => [
                'country'  => $payload->destination->country,
                'province' => $payload->destination->state,
                'city'     => $payload->destination->city,
                'postcode' => $payload->destination->postcode,
            ],
        ]);

        return AvailabilityMapper::map($data);
    }

    private function formatAddress(Address $address): array
    {
        return [
            'name'     => $address->name,
            'mobile'   => $address->phone ?? '',
            'address'  => implode(', ', array_filter([$address->line1, $address->line2, $address->line3])),
            'city'     => $address->city,
            'province' => $address->state,
            'postcode' => $address->postcode,
            'country'  => $address->country,
        ];
    }
}
