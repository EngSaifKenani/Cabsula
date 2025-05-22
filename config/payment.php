<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Default Payment Method
    |--------------------------------------------------------------------------
    |
    | يمكنك تحديد مزود الدفع الافتراضي هنا. مثلاً stripe أو paypal.
    | ويمكنك تغييره من خلال ملف البيئة .env بسهولة.
    |
    */

    'default' => env('PAYMENT_METHOD', 'stripe'),

    // مستقبلًا ممكن تضيف إعدادات خاصة بكل مزود هنا.
    'stripe' => [
        'secret' => env('STRIPE_SECRET_KEY'),
        'public' => env('STRIPE_PUBLIC_KEY'),
    ],

    'paypal' => [
        'client_id' => env('PAYPAL_CLIENT_ID'),
        'secret' => env('PAYPAL_SECRET'),
        'mode' => env('PAYPAL_MODE', 'sandbox'), // or 'live'
    ],
];
