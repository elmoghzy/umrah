<?php

return [
    'single' => [
        'label' => 'إلغاء الربط',
        'modal' => [
            'heading' => 'إلغاء ربط :label',
            'actions' => ['dissociate' => ['label' => 'إلغاء الربط']],
        ],
        'notifications' => ['dissociated' => ['title' => 'تم إلغاء الربط']],
    ],
    'multiple' => [
        'label' => 'إلغاء ربط المحدد',
        'modal' => [
            'heading' => 'إلغاء ربط :label المحدد',
            'actions' => ['dissociate' => ['label' => 'إلغاء الربط']],
        ],
        'notifications' => ['dissociated' => ['title' => 'تم إلغاء الربط']],
    ],
];
