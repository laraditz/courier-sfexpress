<?php

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
