<?php

return [
    'logo_path' => 'img/UmrahManagment.png',
    'company_name' => env('UMRAH_COMPANY_NAME', 'مواكب للاستثمار السياحي'),
    'currency_code' => 'LYD',
    'currency_locale' => 'ar-LY',
    'currency_symbol' => 'د.ل',
    'currency_decimal_places' => 2,
    'public_url' => env('UMRAH_PUBLIC_URL'),
    'visa_disk' => 'visas',
    'visa_max_kb' => (int) env('UMRAH_VISA_MAX_KB', 5120),
    'watermark_opacity' => env('UMRAH_WATERMARK_OPACITY', 0.10),
    'receipt_qr_url' => env('UMRAH_RECEIPT_QR_URL', 'https://www.facebook.com/mawakeblibya/'),
    'receipt_travelers_per_column' => 10,
    'receipt_traveler_columns' => 2,
];
