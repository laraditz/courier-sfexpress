# Changelog

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/), and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [1.0.1] - 2026-06-19

Full rewrite targeting the **SF Express International Open Platform** (`api-ifsp.sf.global`) for domestic shipping in Malaysia, Indonesia, and Vietnam.

### Added

- `SfExpressEncryptor` — AES-256-CBC encryption and SHA256 request signing per the SF Express Open Platform spec
- Config keys: `customer_code`, `encoding_aes_key`, `pay_month_card`, `country`, `scope_name`

### Changed

- **HTTP layer rewritten** — single dispatch endpoint (`POST /openapi/api/dispatch`) with `msgType` header routing; token fetched via `GET /openapi/api/token` and cached per request
- **Request bodies encrypted** — all dispatch payloads are AES-256-CBC encrypted using `encoding_aes_key`; responses decrypted automatically
- **Config keys** — removed `account` and `token_url`; `base_url` updated to `https://api-ifsp.sf.global`, `sandbox_url` to `https://api-ifsp-sit.sf.global`
- `ShipmentMapper` — maps `sfWaybillNo`, `labelUrl`, `customerOrderNo` from the domestic create-order response
- `TrackingMapper` — maps `trackList[]` events with `opCode` → normalized status; detects not-found waybills via non-empty `trackSummary` with empty `trackList`
- `LabelMapper` — downloads the PDF from the signed URL returned by the API; `LabelResult::$content` contains raw bytes, original URL stored in `meta()['label_url']`
- `CancelMapper` — maps flat `{success, code, msg}` response (cancel has no nested `data` key)
- `SfExpressDriver` — injectable `SfExpressClient` constructor parameter for testability; `cancelShipment` passes the full inner response to `CancelMapper`

### Removed

- Support for `getRates` and `getAvailability` — the SF Express domestic API does not provide these operations; both now throw `UnsupportedOperationException`

### Fixed

- Token credentials sent as query parameters (`?appKey=&appSecret=`) as required by the API spec
- AES encryption uses `OPENSSL_RAW_DATA` flag to produce correct single-base64 wire format
- Sandbox URL changed from `http://` to `https://`
- `track()` re-throws `AuthenticationException` instead of masking it as `ShipmentNotFoundException`

## [1.0.0] - 2026-06-18

Initial release.

### Added

- `SfExpressDriver` implementing all six `CourierDriver` operations: `createShipment`, `track`, `getRates`, `cancelShipment`, `getLabel`, `getAvailability`
- `SfExpressServiceProvider` with automatic driver registration and config merging
- `SfExpressClient` with token-based authentication
- Mappers: `ShipmentMapper`, `TrackingMapper`, `LabelMapper`, `CancelMapper`, `RateMapper`, `AvailabilityMapper`
- `config/sfexpress.php` published via `--tag=courier-sfexpress-config`
- Laravel 10, 11, 12, and 13 support
