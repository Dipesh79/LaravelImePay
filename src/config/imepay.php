<?php

return array(
    'apiUser' => env('IMEPAY_API_USER'),
    'apiPassword' => env('IMEPAY_API_PASSWORD'),
    'module' => env('IMEPAY_MODULE'),
    'merchantCode' => env('IMEPAY_MERCHANT_CODE'),
    'env' => env('IMEPAY_ENV'),
    'callbackUrl' => env('IMEPAY_CALLBACK_URL'),
    'cancelUrl' => env('IMEPAY_CANCEL_URL'),
    'callbackMethod' => env('IMEPAY_CALLBACK_METHOD'),
);
