<?php

return [
    'single' => [
        'label' => 'حذف نهائي',
        'modal' => [
            'heading' => 'حذف :label نهائياً',
            'actions' => ['delete' => ['label' => 'حذف نهائي']],
        ],
        'notifications' => ['deleted' => ['title' => 'تم الحذف نهائياً']],
    ],
    'multiple' => [
        'label' => 'حذف المحدد نهائياً',
        'modal' => [
            'heading' => 'حذف :label المحدد نهائياً',
            'actions' => ['delete' => ['label' => 'حذف نهائي']],
        ],
        'notifications' => ['deleted' => ['title' => 'تم الحذف نهائياً']],
    ],
];
