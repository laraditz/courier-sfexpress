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
            'account' => 'test-account',
            'key'     => 'test-key',
            'secret'  => 'test-secret',
            'sandbox' => true,
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
