<x-filament-panels::page>
    <style>
        .database-backup-page {
            display: grid;
            gap: 1.25rem;
            width: 100%;
            color: #0f172a;
        }

        .database-backup-page__hero {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 1.5rem;
            padding: 1.5rem;
            overflow: hidden;
            border: 1px solid #d7edf7;
            border-radius: 1rem;
            background: linear-gradient(115deg, #effaff 0%, #ffffff 58%, #f0fdf8 100%);
        }

        .database-backup-page__eyebrow {
            display: inline-flex;
            align-items: center;
            gap: .45rem;
            color: #0879aa;
            font-size: .75rem;
            font-weight: 700;
        }

        .database-backup-page__eyebrow svg {
            width: 1rem;
            height: 1rem;
        }

        .database-backup-page__hero-title {
            margin: .45rem 0 0;
            color: #102a43;
            font-size: 1.25rem;
            font-weight: 750;
            line-height: 1.6;
        }

        .database-backup-page__hero-description {
            max-width: 42rem;
            margin: .35rem 0 0;
            color: #526579;
            font-size: .9rem;
            line-height: 1.8;
        }

        .database-backup-page__hero-badge {
            display: inline-flex;
            flex: 0 0 auto;
            align-items: center;
            gap: .65rem;
            padding: .8rem 1rem;
            border: 1px solid #dbeafe;
            border-radius: .85rem;
            background: rgb(255 255 255 / 85%);
            color: #147c60;
            font-size: .85rem;
            font-weight: 700;
        }

        .database-backup-page__hero-badge-icon {
            display: grid;
            width: 2.35rem;
            height: 2.35rem;
            place-items: center;
            border-radius: .7rem;
            background: #e7f8f1;
        }

        .database-backup-page__hero-badge-icon svg {
            width: 1.25rem;
            height: 1.25rem;
        }

        .database-backup-page__stats {
            display: grid;
            grid-template-columns: repeat(3, minmax(0, 1fr));
            gap: 1rem;
        }

        .database-backup-page__stat {
            display: flex;
            min-width: 0;
            align-items: center;
            gap: .9rem;
            padding: 1.1rem 1.2rem;
            border: 1px solid #e2e8f0;
            border-radius: .9rem;
            background: #fff;
            box-shadow: 0 2px 8px rgb(15 23 42 / 3%);
        }

        .database-backup-page__stat-icon {
            display: grid;
            width: 2.85rem;
            height: 2.85rem;
            flex: 0 0 auto;
            place-items: center;
            border-radius: .8rem;
            background: #eaf7fd;
            color: #1186b7;
        }

        .database-backup-page__stat-icon--amber {
            background: #fff7e6;
            color: #c27a08;
        }

        .database-backup-page__stat-icon--green {
            background: #e9f8f0;
            color: #13815e;
        }

        .database-backup-page__stat-icon svg {
            width: 1.4rem;
            height: 1.4rem;
        }

        .database-backup-page__stat-label {
            color: #64748b;
            font-size: .78rem;
        }

        .database-backup-page__stat-value {
            margin-top: .2rem;
            color: #102a43;
            font-size: .95rem;
            font-weight: 700;
            line-height: 1.6;
        }

        .database-backup-page__section-heading {
            display: flex;
            align-items: center;
            gap: .8rem;
        }

        .database-backup-page__section-icon {
            display: grid;
            width: 2.5rem;
            height: 2.5rem;
            flex: 0 0 auto;
            place-items: center;
            border-radius: .75rem;
            background: #eaf7fd;
            color: #1186b7;
        }

        .database-backup-page__section-icon svg {
            width: 1.2rem;
            height: 1.2rem;
        }

        .database-backup-page__section-title {
            margin: 0;
            color: #102a43;
            font-size: 1rem;
            font-weight: 700;
        }

        .database-backup-page__section-description {
            margin: .2rem 0 0;
            color: #64748b;
            font-size: .82rem;
            font-weight: 400;
            line-height: 1.7;
        }

        .database-backup-page__form {
            display: grid;
            grid-template-columns: minmax(0, 1.35fr) minmax(15rem, .65fr);
            align-items: start;
            gap: 1rem;
            margin-top: 1.25rem;
        }

        .database-backup-page__setting-toggle {
            display: flex;
            min-height: 5.3rem;
            align-items: flex-start;
            gap: .8rem;
            padding: 1rem;
            border: 1px solid #e2e8f0;
            border-radius: .8rem;
            background: #fbfdff;
            cursor: pointer;
            transition: border-color .18s ease, background-color .18s ease;
        }

        .database-backup-page__setting-toggle:hover {
            border-color: #91d2ed;
            background: #f5fbfe;
        }

        .database-backup-page__checkbox {
            margin-top: .15rem;
        }

        .database-backup-page__setting-label {
            display: block;
            color: #102a43;
            font-size: .9rem;
            font-weight: 700;
        }

        .database-backup-page__setting-help {
            display: block;
            margin-top: .35rem;
            color: #64748b;
            font-size: .8rem;
            line-height: 1.7;
        }

        .database-backup-page__retention {
            min-width: 0;
        }

        .database-backup-page__form-footer {
            display: flex;
            justify-content: flex-start;
            margin-top: 1rem;
        }

        .database-backup-page__table-wrap {
            margin-top: 1.25rem;
            overflow: hidden;
            border: 1px solid #e2e8f0;
            border-radius: .85rem;
        }

        .database-backup-page__table-scroll {
            overflow-x: auto;
        }

        .database-backup-page__table {
            width: 100%;
            min-width: 650px;
            border-collapse: collapse;
            text-align: right;
        }

        .database-backup-page__table thead {
            background: #f7fafc;
        }

        .database-backup-page__table th {
            padding: .9rem 1rem;
            border-bottom: 1px solid #e2e8f0;
            color: #64748b;
            font-size: .75rem;
            font-weight: 700;
            white-space: nowrap;
        }

        .database-backup-page__table td {
            padding: .9rem 1rem;
            border-bottom: 1px solid #edf1f5;
            color: #475569;
            font-size: .83rem;
            vertical-align: middle;
        }

        .database-backup-page__table tbody tr:last-child td {
            border-bottom: 0;
        }

        .database-backup-page__table tbody tr {
            transition: background-color .15s ease;
        }

        .database-backup-page__table tbody tr:hover {
            background: #f8fbfd;
        }

        .database-backup-page__filename {
            display: block;
            overflow: hidden;
            color: #102a43;
            font-size: .82rem;
            font-weight: 700;
            text-overflow: ellipsis;
            white-space: nowrap;
        }

        .database-backup-page__file-type {
            display: block;
            margin-top: .25rem;
            color: #94a3b8;
            font-size: .72rem;
        }

        .database-backup-page__download {
            display: inline-flex;
            align-items: center;
            gap: .4rem;
            padding: .5rem .75rem;
            border: 1px solid #cceaf7;
            border-radius: .6rem;
            background: #f0faff;
            color: #0879aa;
            font-size: .8rem;
            font-weight: 700;
            text-decoration: none;
            transition: background-color .15s ease, border-color .15s ease;
        }

        .database-backup-page__download:hover {
            border-color: #9bd8ef;
            background: #e2f5fc;
        }

        .database-backup-page__download svg {
            width: 1rem;
            height: 1rem;
        }

        .database-backup-page__empty {
            padding: 3rem 1rem !important;
            text-align: center;
        }

        .database-backup-page__empty-icon {
            width: 2.5rem;
            height: 2.5rem;
            margin: 0 auto;
            color: #94a3b8;
        }

        .database-backup-page__empty-title {
            margin: .75rem 0 0;
            color: #334155;
            font-size: .9rem;
            font-weight: 700;
        }

        .database-backup-page__empty-description {
            margin: .35rem 0 0;
            color: #64748b;
            font-size: .8rem;
        }

        @media (max-width: 767px) {
            .database-backup-page__hero {
                align-items: flex-start;
                flex-direction: column;
                gap: 1rem;
                padding: 1.2rem;
            }

            .database-backup-page__stats {
                grid-template-columns: minmax(0, 1fr);
                gap: .7rem;
            }

            .database-backup-page__stat {
                padding: .9rem 1rem;
            }

            .database-backup-page__form {
                grid-template-columns: minmax(0, 1fr);
            }
        }
    </style>

    <div class="database-backup-page">
        <section class="database-backup-page__hero" aria-labelledby="database-backup-hero-title">
            <div>
                <span class="database-backup-page__eyebrow">
                    <x-filament::icon icon="heroicon-o-circle-stack" />
                    إدارة النسخ الاحتياطية
                </span>
                <h2 id="database-backup-hero-title" class="database-backup-page__hero-title">حافظ على بياناتك جاهزة عند الحاجة</h2>
                <p class="database-backup-page__hero-description">أنشئ نسخة احتياطية يدويًا أو فعّل النسخ اليومي، وتابع النسخ المحفوظة ونزّلها من مكان واحد.</p>
            </div>

            <div class="database-backup-page__hero-badge">
                <span class="database-backup-page__hero-badge-icon">
                    <x-filament::icon icon="heroicon-o-shield-check" />
                </span>
                <span>إدارة آمنة للنسخ</span>
            </div>
        </section>

        <div class="database-backup-page__stats">
            <article class="database-backup-page__stat">
                <span class="database-backup-page__stat-icon database-backup-page__stat-icon--green">
                    <x-filament::icon icon="heroicon-o-shield-check" />
                </span>
                <div>
                    <p class="database-backup-page__stat-label">حالة الحماية</p>
                    <p class="database-backup-page__stat-value">{{ count($backups) > 0 ? 'توجد نسخة محفوظة' : 'أنشئ أول نسخة احتياطية' }}</p>
                </div>
            </article>

            <article class="database-backup-page__stat">
                <span class="database-backup-page__stat-icon database-backup-page__stat-icon--amber">
                    <x-filament::icon icon="heroicon-o-clock" />
                </span>
                <div>
                    <p class="database-backup-page__stat-label">النسخ التلقائي</p>
                    <p class="database-backup-page__stat-value">{{ $automatic ? 'مفعّل يوميًا — 02:00 صباحًا' : 'غير مفعّل' }}</p>
                </div>
            </article>

            <article class="database-backup-page__stat">
                <span class="database-backup-page__stat-icon">
                    <x-filament::icon icon="heroicon-o-archive-box" />
                </span>
                <div>
                    <p class="database-backup-page__stat-label">النسخ المحفوظة</p>
                    <p class="database-backup-page__stat-value">{{ count($backups) }} من {{ $retention }} نسخة</p>
                </div>
            </article>
        </div>

        <x-filament::section>
            <x-slot name="heading">
                <div class="database-backup-page__section-heading">
                    <span class="database-backup-page__section-icon">
                        <x-filament::icon icon="heroicon-o-cog-6-tooth" />
                    </span>
                    <div>
                        <h2 class="database-backup-page__section-title">إعدادات النسخ الاحتياطي</h2>
                        <p class="database-backup-page__section-description">حدد طريقة إنشاء النسخ وعدد النسخ التي تريد الاحتفاظ بها.</p>
                    </div>
                </div>
            </x-slot>

            <form wire:submit="saveSettings">
                <div class="database-backup-page__form">
                    <label class="database-backup-page__setting-toggle">
                        <x-filament::input.checkbox wire:model="automatic" class="database-backup-page__checkbox" />
                        <span>
                            <span class="database-backup-page__setting-label">تفعيل النسخ الاحتياطي التلقائي</span>
                            <span class="database-backup-page__setting-help">سيتم إنشاء نسخة تلقائيًا كل يوم الساعة 02:00 صباحًا.</span>
                        </span>
                    </label>

                    <div class="database-backup-page__retention">
                        <x-filament::input.wrapper>
                            <x-slot name="label">عدد النسخ المحتفظ بها</x-slot>
                            <x-filament::input type="number" min="1" max="30" wire:model="retention" />
                            <x-slot name="helperText">من 1 إلى 30 نسخة. سيتم حذف النسخ الأقدم تلقائيًا.</x-slot>
                        </x-filament::input.wrapper>
                    </div>
                </div>

                <div class="database-backup-page__form-footer">
                    <x-filament::button type="submit" icon="heroicon-o-check">حفظ الإعدادات</x-filament::button>
                </div>
            </form>
        </x-filament::section>

        <x-filament::section>
            <x-slot name="heading">
                <div class="database-backup-page__section-heading">
                    <span class="database-backup-page__section-icon">
                        <x-filament::icon icon="heroicon-o-archive-box" />
                    </span>
                    <div>
                        <h2 class="database-backup-page__section-title">النسخ المحفوظة</h2>
                        <p class="database-backup-page__section-description">استعرض النسخ المتاحة ونزّل نسخة إلى جهازك عند الحاجة.</p>
                    </div>
                </div>
            </x-slot>

            <div class="database-backup-page__table-wrap">
                <div class="database-backup-page__table-scroll">
                    <table class="database-backup-page__table">
                        <thead>
                            <tr>
                                <th scope="col" style="width: 42%">اسم النسخة</th>
                                <th scope="col" style="width: 16%">الحجم</th>
                                <th scope="col" style="width: 25%">تاريخ الإنشاء</th>
                                <th scope="col" style="width: 17%">الإجراء</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($backups as $backup)
                                <tr>
                                    <td>
                                        <span dir="ltr" class="database-backup-page__filename" title="{{ $backup['name'] }}">{{ $backup['name'] }}</span>
                                        <span class="database-backup-page__file-type">نسخة قاعدة بيانات</span>
                                    </td>
                                    <td>{{ $backup['size'] }}</td>
                                    <td>{{ $backup['date'] }}</td>
                                    <td>
                                        <a
                                            href="{{ route('admin.database-backups.download', ['filename' => $backup['name']]) }}"
                                            class="database-backup-page__download"
                                        >
                                            <x-filament::icon icon="heroicon-o-arrow-down-tray" />
                                            تنزيل
                                        </a>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="4" class="database-backup-page__empty">
                                        <x-filament::icon icon="heroicon-o-archive-box-x-mark" class="database-backup-page__empty-icon" />
                                        <p class="database-backup-page__empty-title">لا توجد نسخ احتياطية حتى الآن</p>
                                        <p class="database-backup-page__empty-description">استخدم زر «إنشاء نسخة الآن» لعمل أول نسخة احتياطية.</p>
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </x-filament::section>
    </div>
</x-filament-panels::page>
