<?php

return [
    'columns' => [
        'actions' => ['label' => 'إجراء|إجراءات'],
        'select' => [
            'loading_message' => 'جارٍ التحميل...',
            'no_options_message' => 'لا توجد خيارات متاحة.',
            'no_search_results_message' => 'لا توجد خيارات مطابقة للبحث.',
            'placeholder' => 'اختر خياراً',
            'searching_message' => 'جارٍ البحث...',
            'search_prompt' => 'ابدأ بالكتابة للبحث',
        ],
        'icon' => [
            'boolean' => [
                'true' => 'نعم',
                'false' => 'لا',
            ],
        ],
    ],
    'fields' => [
        'bulk_select_page' => ['label' => 'تحديد أو إلغاء تحديد كل العناصر للإجراءات الجماعية.'],
        'bulk_select_record' => ['label' => 'تحديد أو إلغاء تحديد العنصر :key للإجراءات الجماعية.'],
        'search' => [
            'label' => 'بحث',
            'placeholder' => 'بحث',
            'indicator' => 'بحث',
        ],
    ],
    'summary' => [
        'heading' => 'الملخص',
        'subheadings' => [
            'all' => 'كل :label',
            'group' => 'ملخص :group',
            'page' => 'هذه الصفحة',
        ],
        'summarizers' => [
            'average' => ['label' => 'المتوسط'],
            'count' => ['label' => 'العدد'],
            'sum' => ['label' => 'المجموع'],
        ],
    ],
    'actions' => [
        'filter' => ['label' => 'تصفية'],
        'group' => ['label' => 'تجميع'],
        'open_bulk_actions' => ['label' => 'إجراءات جماعية'],
        'column_manager' => ['label' => 'إدارة الأعمدة'],
        'enable_reordering' => ['label' => 'إعادة ترتيب السجلات'],
        'disable_reordering' => ['label' => 'إنهاء إعادة ترتيب السجلات'],
    ],
    'empty' => [
        'heading' => 'لا توجد سجلات من نوع :model',
        'description' => 'أنشئ :model للبدء.',
    ],
    'filters' => [
        'actions' => [
            'apply' => ['label' => 'تطبيق عوامل التصفية'],
            'remove' => ['label' => 'إزالة عامل التصفية'],
            'remove_all' => [
                'label' => 'إزالة جميع عوامل التصفية',
                'tooltip' => 'إزالة جميع عوامل التصفية',
            ],
            'reset' => ['label' => 'إعادة ضبط'],
        ],
        'heading' => 'عوامل التصفية',
        'indicator' => 'عوامل التصفية النشطة',
        'multi_select' => ['placeholder' => 'الكل'],
        'select' => [
            'placeholder' => 'الكل',
            'relationship' => ['empty_option_label' => 'لا يوجد'],
        ],
        'trashed' => [
            'label' => 'السجلات المحذوفة',
            'only_trashed' => 'المحذوفة فقط',
            'with_trashed' => 'مع المحذوفة',
            'without_trashed' => 'بدون المحذوفة',
        ],
    ],
];
