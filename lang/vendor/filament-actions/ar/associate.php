<?php

return [
    'single' => [
        'label' => 'ربط',
        'modal' => [
            'heading' => 'ربط :label',
            'fields' => ['record_id' => ['label' => 'السجل']],
            'actions' => ['associate' => ['label' => 'ربط']],
        ],
        'notifications' => ['associated' => ['title' => 'تم الربط']],
    ],
];
