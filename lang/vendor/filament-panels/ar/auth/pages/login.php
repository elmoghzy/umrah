<?php

return [
    'title' => 'تسجيل الدخول',
    'heading' => 'تسجيل الدخول',
    'actions' => [
        'register' => [
            'before' => 'أو',
            'label' => 'إنشاء حساب',
        ],
        'request_password_reset' => [
            'label' => 'هل نسيت كلمة المرور؟',
        ],
    ],
    'form' => [
        'email' => [
            'label' => 'البريد الإلكتروني',
        ],
        'password' => [
            'label' => 'كلمة المرور',
        ],
        'remember' => [
            'label' => 'تذكرني',
        ],
        'actions' => [
            'authenticate' => [
                'label' => 'تسجيل الدخول',
            ],
        ],
    ],
    'messages' => [
        'failed' => 'بيانات الدخول غير صحيحة.',
    ],
    'notifications' => [
        'throttled' => [
            'title' => 'محاولات دخول كثيرة',
            'body' => 'يرجى المحاولة مرة أخرى بعد :seconds ثانية.',
        ],
    ],
];
