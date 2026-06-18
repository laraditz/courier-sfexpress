# laraditz/courier-sfexpress

SF Express driver for [laraditz/courier](https://github.com/laraditz/courier).

## Requirements

- PHP 8.1+
- Laravel 10, 11, 12, or 13
- `laraditz/courier` ^1.0

## Installation

```bash
composer require laraditz/courier-sfexpress
```

Both service providers are auto-discovered. Publish the config:

```bash
php artisan vendor:publish --tag=courier-config
php artisan vendor:publish --tag=courier-sfexpress-config
```

## Configuration

Add to your `.env`:

```env
COURIER_DRIVER=sfexpress

SFEXPRESS_ACCOUNT=your-account-number
SFEXPRESS_KEY=your-client-id
SFEXPRESS_SECRET=your-client-secret
SFEXPRESS_SANDBOX=true
```

`config/sfexpress.php` (published separately):

```php
return [
    'account'     => env('SFEXPRESS_ACCOUNT'),
    'key'         => env('SFEXPRESS_KEY'),
    'secret'      => env('SFEXPRESS_SECRET'),
    'sandbox'     => env('SFEXPRESS_SANDBOX', false),
    'base_url'    => 'https://bsp-oisp.sf-express.com/bsp-oisp/sfexpressService',
    'sandbox_url' => 'https://sfapi-sandbox.sf-express.com/std/service',
    'token_url'   => '/oauth2/accessToken',
    'timeout'     => 30,
];
```

## Available Methods

All six `CourierDriver` operations are supported:

| Method | Parameters | Returns | SF Express Endpoint |
|---|---|---|---|
| `createShipment` | `ShipmentPayload $payload` | `ShipmentResult` | `POST /shipment/create` |
| `track` | `string $trackingNumber` | `TrackingResult` | `POST /shipment/route` |
| `getRates` | `RatePayload $payload` | `RateCollection` | `POST /shipment/queryFreight` |
| `cancelShipment` | `string $waybillNumber` | `CancelResult` | `POST /shipment/cancel` |
| `getLabel` | `string $waybillNumber` | `LabelResult` | `POST /shipment/label` |
| `getAvailability` | `AvailabilityPayload $payload` | `ServiceCollection` | `POST /service/queryByAddress` |

Refer to the [laraditz/courier README](https://github.com/laraditz/courier) for payload/result DTO definitions and full usage examples.

## Usage

```php
use Laraditz\Courier\Facades\Courier;

Courier::createShipment($payload);   // ShipmentResult
Courier::track($waybillNumber);      // TrackingResult
Courier::getRates($payload);         // RateCollection
Courier::cancelShipment($waybill);   // CancelResult
Courier::getLabel($waybill);         // LabelResult (PDF, base64)
Courier::getAvailability($payload);  // ServiceCollection
```

## SF Express API Notes

- Authentication uses OAuth2 `client_credentials` grant. Tokens are fetched automatically before each request.
- Success responses carry `apiResultCode: 'A2000'`. Any other code is treated as an error and throws a `CourierException`.
- `track()` throws `ShipmentNotFoundException` when the API returns error code `A2002` (waybill not found).
- Labels are returned as base64-encoded PDF content.

## Status Mapping

SF Express `opCode` values are mapped to the [normalized status vocabulary](https://github.com/laraditz/courier#normalized-status-vocabulary):

| opCode | Status |
|---|---|
| `50` | `picked_up` |
| `30` | `in_transit` |
| `70` | `out_for_delivery` |
| `80` | `delivered` |
| `90` | `failed_delivery` |
| `35` | `returned` |
| other | `unknown` |

## License

MIT
