# laraditz/courier-sfexpress

SF Express driver for [laraditz/courier](https://github.com/laraditz/courier).

Targets the **SF Express International Open Platform** (`api-ifsp.sf.global`) — domestic shipping for Malaysia, Indonesia, and Vietnam.

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

SFEXPRESS_KEY=your-app-key
SFEXPRESS_SECRET=your-app-secret
SFEXPRESS_CUSTOMER_CODE=your-customer-code
SFEXPRESS_AES_KEY=your-43-char-encoding-aes-key
SFEXPRESS_PAY_MONTH_CARD=your-pay-month-card
SFEXPRESS_COUNTRY=MY
SFEXPRESS_SCOPE=OSMY
SFEXPRESS_SANDBOX=true
```

`config/sfexpress.php` (published separately):

```php
return [
    'key'              => env('SFEXPRESS_KEY'),
    'secret'           => env('SFEXPRESS_SECRET'),
    'customer_code'    => env('SFEXPRESS_CUSTOMER_CODE'),
    'encoding_aes_key' => env('SFEXPRESS_AES_KEY'),
    'pay_month_card'   => env('SFEXPRESS_PAY_MONTH_CARD'),
    'country'          => env('SFEXPRESS_COUNTRY', 'MY'),
    'scope_name'       => env('SFEXPRESS_SCOPE', 'OSMY'),
    'sandbox'          => env('SFEXPRESS_SANDBOX', false),
    'base_url'         => 'https://api-ifsp.sf.global',
    'sandbox_url'      => 'https://api-ifsp-sit.sf.global',
    'timeout'          => 30,
];
```

| Key | Description |
|---|---|
| `key` | App key from the SF Express Open Platform |
| `secret` | App secret for token generation |
| `customer_code` | Customer code assigned by SF Express (e.g. `OSMYICRM-OSMY00009Z38`) |
| `encoding_aes_key` | 43-character AES key for request/response encryption |
| `pay_month_card` | Monthly billing card number |
| `country` | 2-letter country code: `MY`, `ID`, or `VN` |
| `scope_name` | Scope assigned to your account (e.g. `OSMY`, `OSID`, `OSVN`) |

## Available Methods

| Method | Parameters | Returns | Notes |
|---|---|---|---|
| `createShipment` | `ShipmentPayload $payload` | `ShipmentResult` | `IUOP_OS_CREATE_ORDER` |
| `track` | `string $trackingNumber` | `TrackingResult` | `IUOP_OS_QUERY_TRACK` |
| `getLabel` | `string $waybillNumber` | `LabelResult` | `IUOP_OS_PRINT_ORDER` — PDF bytes |
| `cancelShipment` | `string $waybillNumber` | `CancelResult` | `IUOP_OS_CANCEL_ORDER` |
| `getRates` | `RatePayload $payload` | — | Throws `UnsupportedOperationException` |
| `getAvailability` | `AvailabilityPayload $payload` | — | Throws `UnsupportedOperationException` |

Rate queries and service availability checks are not supported by the SF Express domestic API.

Refer to the [laraditz/courier README](https://github.com/laraditz/courier) for payload/result DTO definitions and full usage examples.

## Usage

```php
use Laraditz\Courier\Facades\Courier;

Courier::createShipment($payload);   // ShipmentResult
Courier::track($waybillNumber);      // TrackingResult
Courier::cancelShipment($waybill);   // CancelResult
Courier::getLabel($waybill);         // LabelResult — content is raw PDF bytes
```

## API Notes

**Authentication:** An access token is fetched automatically via `GET /openapi/api/token` before each request and cached for the lifetime of the request. Tokens expire after ~2 hours.

**Encryption:** All dispatch request bodies are AES-256-CBC encrypted using the `encoding_aes_key`. Responses are decrypted automatically.

**Error handling:** Two levels of error checking apply:
1. Outer envelope: `apiResultCode` must equal `0` (integer) — any other value throws `CourierException`
2. Inner business result (after decryption): `success` must be `true` and `code` must be `'0'` — any other value throws `CourierException`

**Labels:** `getLabel` downloads the PDF from the signed URL returned by the API and returns the raw bytes in `LabelResult::$content`. The original URL is available in `$result->meta()['label_url']`.

**Not found:** `track()` throws `ShipmentNotFoundException` when the waybill is not known to the system.

## Service Codes

Pass the service code as `ShipmentPayload::$serviceCode`. Common domestic codes:

| Country | Code | Description |
|---|---|---|
| MY | `M111` | Standard |
| MY | `M101` | Economy |
| MY | `M102` | Express |
| MY | `M105` | Bulky |
| ID | `ID101` | Standard |
| ID | `ID102` | Economy |
| VN | `SFVN100101` | Standard |

Full lists are in the SF Express Open Platform appendix.

## Status Mapping

SF Express `opCode` values mapped to the [normalized status vocabulary](https://github.com/laraditz/courier#normalized-status-vocabulary):

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
