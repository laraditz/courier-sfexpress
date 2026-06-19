<?php

namespace Laraditz\Courier\SfExpress\Tests\Http;

use Illuminate\Support\Facades\Http;
use Laraditz\Courier\Exceptions\AuthenticationException;
use Laraditz\Courier\Exceptions\CourierException;
use Laraditz\Courier\SfExpress\Http\SfExpressClient;
use Laraditz\Courier\SfExpress\Http\SfExpressEncryptor;
use Laraditz\Courier\SfExpress\Tests\TestCase;

class SfExpressClientTest extends TestCase
{
    private array $config;
    private SfExpressEncryptor $encryptor;

    protected function setUp(): void
    {
        parent::setUp();
        $this->config    = config('courier.drivers.sfexpress');
        $this->encryptor = new SfExpressEncryptor(
            $this->config['encoding_aes_key'],
            $this->config['key'],
        );
    }

    private function tokenResponse(): array
    {
        return [
            'apiResultCode' => 0,
            'apiResultData' => ['accessToken' => 'test-token', 'expireIn' => 7200],
            'apiTimestamp'  => time(),
        ];
    }

    private function dispatchResponse(array $inner): array
    {
        $result = $this->encryptor->encrypt(
            json_encode($inner),
            'test-token',
            (string) time(),
            'nonce'
        );

        return [
            'apiResultCode' => 0,
            'apiResultData' => $result['encrypt'],
            'apiTimestamp'  => time(),
        ];
    }

    public function test_dispatch_returns_decrypted_inner_object(): void
    {
        $inner = ['success' => true, 'code' => '0', 'msg' => 'ok', 'data' => ['sfWaybillNo' => 'MYIU123']];

        Http::fake([
            '*/openapi/api/token'    => Http::response($this->tokenResponse(), 200),
            '*/openapi/api/dispatch' => Http::response($this->dispatchResponse($inner), 200),
        ]);

        $client = new SfExpressClient($this->config);
        $result = $client->dispatch('IUOP_OS_QUERY_TRACK', ['customerCode' => 'TEST']);

        $this->assertSame('MYIU123', $result['data']['sfWaybillNo']);
        $this->assertTrue($result['success']);
    }

    public function test_dispatch_sends_correct_headers_and_body(): void
    {
        $inner = ['success' => true, 'code' => '0', 'msg' => 'ok', 'data' => []];

        Http::fake([
            '*/openapi/api/token'    => Http::response($this->tokenResponse(), 200),
            '*/openapi/api/dispatch' => Http::response($this->dispatchResponse($inner), 200),
        ]);

        $client = new SfExpressClient($this->config);
        $client->dispatch('IUOP_OS_CREATE_ORDER', ['customerCode' => 'TEST']);

        Http::assertSent(function ($request) {
            return str_contains($request->url(), '/openapi/api/dispatch')
                && $request->header('msgType')[0] === 'IUOP_OS_CREATE_ORDER'
                && $request->header('appKey')[0] === 'test-key'
                && $request->header('lang')[0] === 'en'
                && $request->header('country')[0] === 'MY'
                && $request->header('scopeName')[0] === 'OSMY'
                && isset($request->data()['encrypt']);
        });
    }

    public function test_token_is_cached_across_multiple_dispatches(): void
    {
        $inner = ['success' => true, 'code' => '0', 'msg' => 'ok', 'data' => []];

        Http::fake([
            '*/openapi/api/token'    => Http::response($this->tokenResponse(), 200),
            '*/openapi/api/dispatch' => Http::response($this->dispatchResponse($inner), 200),
        ]);

        $client = new SfExpressClient($this->config);
        $client->dispatch('IUOP_OS_QUERY_TRACK', []);
        $client->dispatch('IUOP_OS_QUERY_TRACK', []);

        Http::assertSentCount(3); // 1 token fetch + 2 dispatches
    }

    public function test_throws_authentication_exception_when_token_fetch_returns_http_error(): void
    {
        Http::fake([
            '*/openapi/api/token' => Http::response('Server Error', 500),
        ]);

        $this->expectException(AuthenticationException::class);

        (new SfExpressClient($this->config))->dispatch('IUOP_OS_QUERY_TRACK', []);
    }

    public function test_throws_authentication_exception_when_token_is_missing(): void
    {
        Http::fake([
            '*/openapi/api/token' => Http::response([
                'apiResultCode' => 0,
                'apiResultData' => ['accessToken' => '', 'expireIn' => 7200],
            ], 200),
        ]);

        $this->expectException(AuthenticationException::class);

        (new SfExpressClient($this->config))->dispatch('IUOP_OS_QUERY_TRACK', []);
    }

    public function test_throws_courier_exception_when_outer_api_result_code_is_not_zero(): void
    {
        Http::fake([
            '*/openapi/api/token'    => Http::response($this->tokenResponse(), 200),
            '*/openapi/api/dispatch' => Http::response([
                'apiResultCode' => 502,
                'apiErrorMsg'   => 'Request parameter error',
                'apiTimestamp'  => time(),
            ], 200),
        ]);

        $this->expectException(CourierException::class);
        $this->expectExceptionMessageMatches('/502/');

        (new SfExpressClient($this->config))->dispatch('IUOP_OS_QUERY_TRACK', []);
    }

    public function test_throws_courier_exception_when_inner_success_is_false(): void
    {
        $inner = ['success' => false, 'code' => '9001', 'msg' => 'Customer order not found'];

        Http::fake([
            '*/openapi/api/token'    => Http::response($this->tokenResponse(), 200),
            '*/openapi/api/dispatch' => Http::response($this->dispatchResponse($inner), 200),
        ]);

        $this->expectException(CourierException::class);
        $this->expectExceptionMessageMatches('/Customer order not found/');

        (new SfExpressClient($this->config))->dispatch('IUOP_OS_QUERY_TRACK', []);
    }

    public function test_customer_code_returns_config_value(): void
    {
        $client = new SfExpressClient($this->config);
        $this->assertSame('TEST-CUSTOMER-CODE', $client->customerCode());
    }

    public function test_pay_month_card_returns_config_value(): void
    {
        $client = new SfExpressClient($this->config);
        $this->assertSame('TESTJACK0004', $client->payMonthCard());
    }
}
