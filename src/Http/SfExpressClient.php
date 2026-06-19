<?php

namespace Laraditz\Courier\SfExpress\Http;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use Laraditz\Courier\Exceptions\AuthenticationException;
use Laraditz\Courier\Exceptions\CourierException;

class SfExpressClient
{
    private ?string $accessToken = null;
    private readonly SfExpressEncryptor $encryptor;

    public function __construct(private readonly array $config)
    {
        $this->encryptor = new SfExpressEncryptor(
            $this->config['encoding_aes_key'],
            $this->config['key'],
        );
    }

    public function dispatch(string $msgType, array $body): array
    {
        $token     = $this->getAccessToken();
        $timestamp = (string) time();
        $nonce     = Str::uuid()->toString();

        $result = $this->encryptor->encrypt(json_encode($body), $token, $timestamp, $nonce);

        $response = Http::timeout($this->config['timeout'] ?? 30)
            ->withHeaders([
                'msgType'   => $msgType,
                'appKey'    => $this->config['key'],
                'token'     => $token,
                'timestamp' => $timestamp,
                'nonce'     => $nonce,
                'signature' => $result['signature'],
                'lang'      => 'en',
                'country'   => $this->config['country'] ?? 'MY',
                'scopeName' => $this->config['scope_name'] ?? 'OSMY',
            ])
            ->post($this->baseUrl() . '/openapi/api/dispatch', [
                'encrypt' => $result['encrypt'],
            ]);

        if ($response->failed()) {
            throw new CourierException(
                'SF Express API error (' . $response->status() . '): ' . $response->body()
            );
        }

        $data = $response->json();

        if (($data['apiResultCode'] ?? -1) !== 0) {
            throw new CourierException(
                'SF Express API error [' . ($data['apiResultCode'] ?? 'unknown') . ']: ' .
                ($data['apiErrorMsg'] ?? $response->body())
            );
        }

        $inner = json_decode($this->encryptor->decrypt($data['apiResultData']), true);

        if (($inner['success'] ?? false) !== true || ($inner['code'] ?? '') !== '0') {
            throw new CourierException(
                'SF Express business error: ' . ($inner['msg'] ?? 'Unknown error')
            );
        }

        return $inner;
    }

    public function customerCode(): string
    {
        return $this->config['customer_code'] ?? '';
    }

    public function payMonthCard(): string
    {
        return $this->config['pay_month_card'] ?? '';
    }

    private function getAccessToken(): string
    {
        if ($this->accessToken !== null) {
            return $this->accessToken;
        }

        $response = Http::timeout($this->config['timeout'] ?? 30)
            ->get($this->baseUrl() . '/openapi/api/token', [
                'appKey'    => $this->config['key'],
                'appSecret' => $this->config['secret'],
            ]);

        if ($response->failed()) {
            throw new AuthenticationException(
                'SF Express authentication failed: ' . $response->body()
            );
        }

        $data = $response->json();

        if (($data['apiResultCode'] ?? -1) !== 0) {
            throw new AuthenticationException(
                'SF Express authentication error [' . ($data['apiResultCode'] ?? 'unknown') . ']: ' .
                ($data['apiErrorMsg'] ?? 'Unknown error')
            );
        }

        $token = $data['apiResultData']['accessToken'] ?? null;

        if (empty($token)) {
            throw new AuthenticationException('SF Express returned no access token.');
        }

        $this->accessToken = $token;

        return $this->accessToken;
    }

    private function baseUrl(): string
    {
        return ($this->config['sandbox'] ?? false)
            ? ($this->config['sandbox_url'] ?? '')
            : ($this->config['base_url'] ?? '');
    }
}
