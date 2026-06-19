<?php

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
