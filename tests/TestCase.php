<?php

namespace Laraditz\Courier\SfExpress\Tests;

use Laraditz\Courier\CourierServiceProvider;
use Laraditz\Courier\SfExpress\SfExpressServiceProvider;
use Orchestra\Testbench\TestCase as Orchestra;

class TestCase extends Orchestra
{
    protected function getPackageProviders($app): array
    {
        return [
            CourierServiceProvider::class,
            SfExpressServiceProvider::class,
        ];
    }

    protected function getEnvironmentSetUp($app): void
    {
        $app['config']->set('courier.drivers.sfexpress', [
            'key'              => 'test-key',
            'secret'           => 'test-secret',
            'customer_code'    => 'TEST-CUSTOMER-CODE',
            'encoding_aes_key' => 'oI1YdU1cb1YC70HVjZRa3wXBLUsrIUYr5lDh0gFrMbe',
            'pay_month_card'   => 'TESTJACK0004',
            'country'          => 'MY',
            'scope_name'       => 'OSMY',
            'sandbox'          => true,
            'sandbox_url'      => 'https://api-ifsp-sit.sf.global',
            'base_url'         => 'https://api-ifsp.sf.global',
            'timeout'          => 30,
        ]);
    }

    protected function fixture(string $name): array
    {
        return json_decode(
            file_get_contents(__DIR__."/fixtures/{$name}.json"),
            true
        );
    }
}
