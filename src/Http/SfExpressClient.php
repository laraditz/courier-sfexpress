<?php

namespace Laraditz\Courier\SfExpress\Http;

use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;
use Laraditz\Courier\Exceptions\AuthenticationException;
use Laraditz\Courier\Exceptions\CourierException;

class SfExpressClient
{
    private ?string $accessToken = null;

    public function __construct(private readonly array $config) {}

    public function post(string $endpoint, array $payload): array
    {
        $response = Http::timeout($this->config['timeout'] ?? 30)
            ->withToken($this->getAccessToken())
            ->post($this->baseUrl().$endpoint, $payload);

        return $this->handleResponse($response);
    }

    public function account(): string
    {
        return $this->config['account'] ?? '';
    }

    private function getAccessToken(): string
    {
        if ($this->accessToken !== null) {
            return $this->accessToken;
        }

        $response = Http::timeout($this->config['timeout'] ?? 30)
            ->asForm()
            ->post($this->baseUrl().($this->config['token_url'] ?? '/oauth2/accessToken'), [
                'grant_type'    => 'client_credentials',
                'client_id'     => $this->config['key'],
                'client_secret' => $this->config['secret'],
            ]);

        if ($response->failed()) {
            throw new AuthenticationException(
                'SF Express authentication failed: '.$response->body()
            );
        }

        $data = $response->json();

        if (empty($data['access_token'])) {
            throw new AuthenticationException('SF Express returned no access token.');
        }

        $this->accessToken = $data['access_token'];

        return $this->accessToken;
    }

    private function baseUrl(): string
    {
        return ($this->config['sandbox'] ?? false)
            ? ($this->config['sandbox_url'] ?? '')
            : ($this->config['base_url'] ?? '');
    }

    private function handleResponse(Response $response): array
    {
        if ($response->failed()) {
            throw new CourierException(
                'SF Express API error ('.$response->status().'): '.$response->body()
            );
        }

        $data = $response->json();

        if (($data['apiResultCode'] ?? '') !== 'A1000') {
            throw new CourierException(
                'SF Express API returned error code ['.($data['apiResultCode'] ?? 'unknown').']: '.
                ($data['apiResultData']['errorMsg'] ?? $response->body())
            );
        }

        return $data['apiResultData'] ?? [];
    }
}
