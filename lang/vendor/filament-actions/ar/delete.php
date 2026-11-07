<?php

return [
    'single' => [
        'label' => 'حذف',
        'modal' => [
            'heading' => 'حذف :label',
            'actions' => [
                'delete' => ['label' => 'حذف'],
            ],
        ],
        'notifications' => [
            'deleted' => ['title' => 'تم الحذف'],
        ],
    ],
    'multiple' => [
        'label' => 'حذف المحدد',
        'modal' => [
            'heading' => 'حذف :label المحدد',
            'actions' => [
                'delete' => ['label' => 'حذف'],
            ],
        ],
        'notifications' => [
            'deleted' => ['title' => 'تم الحذف'],
            'deleted_partial' => [
                'title' => 'تم حذف :count من :total',
                'missing_authorization_failure_message' => 'لا تملك صلاحية حذف :count.',
                'missing_processing_failure_message' => 'تعذر حذف :count.',
            ],
            'deleted_none' => [
                'title' => 'تعذر الحذف',
                'missing_authorization_failure_message' => 'لا تملك صلاحية حذف :count.',
                'missing_processing_failure_message' => 'تعذر حذف :count.',
            ],
        ],
    ],
];
