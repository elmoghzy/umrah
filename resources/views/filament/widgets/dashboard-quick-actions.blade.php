<x-filament-widgets::widget>
    <section class="mwk-dash" aria-label="اختصارات لوحة التحكم">
        <div class="mwk-dash__hero">
            <div class="mwk-dash__intro">
                <p class="mwk-dash__brand">مواكب لإدارة العمرة</p>
                <h1 class="mwk-dash__title">أهلاً بك، {{ $user_name }}</h1>
                <p class="mwk-dash__subtitle">ابدأ يومك بإضافة ولي أمر أو حجز جديد، ثم تابع الأعمال من الاختصارات السريعة.</p>
            </div>

            @if (count($primary_actions))
                <div class="mwk-dash__primary">
                    @foreach ($primary_actions as $action)
                        <a
                            href="{{ $action['url'] }}"
                            class="mwk-dash__cta mwk-dash__cta--{{ $action['tone'] }}"
                            wire:navigate
                        >
                            <span class="mwk-dash__cta-icon" aria-hidden="true">
                                <x-filament::icon :icon="$action['icon']" />
                            </span>
                            <span class="mwk-dash__cta-copy">
                                <strong>{{ $action['label'] }}</strong>
                                <span>{{ $action['description'] }}</span>
                            </span>
                            <span class="mwk-dash__cta-arrow" aria-hidden="true">
                                <x-filament::icon icon="heroicon-o-arrow-left" />
                            </span>
                        </a>
                    @endforeach
                </div>
            @else
                <p class="mwk-dash__empty">لا تتوفر صلاحيات لإنشاء ولي أمر أو حجز من هذه الشاشة.</p>
            @endif
        </div>

        @if (count($shortcuts))
            <div class="mwk-dash__shortcuts">
                <h2>اختصارات سريعة</h2>
                <div class="mwk-dash__shortcut-grid">
                    @foreach ($shortcuts as $shortcut)
                        <a href="{{ $shortcut['url'] }}" class="mwk-dash__shortcut" wire:navigate>
                            <span class="mwk-dash__shortcut-icon" aria-hidden="true">
                                <x-filament::icon :icon="$shortcut['icon']" />
                            </span>
                            <span>{{ $shortcut['label'] }}</span>
                        </a>
                    @endforeach
                </div>
            </div>
        @endif
    </section>

    <style>
        .mwk-dash {
            overflow: hidden;
            border: 1px solid color-mix(in oklab, var(--primary-500) 18%, transparent);
            border-radius: 1.1rem;
            background:
                radial-gradient(120% 90% at 100% 0%, color-mix(in oklab, var(--warning-400) 16%, transparent), transparent 55%),
                linear-gradient(145deg, color-mix(in oklab, var(--primary-700) 92%, #063b58) 0%, var(--primary-600) 48%, color-mix(in oklab, var(--primary-500) 80%, #22b3e2) 100%);
            color: #fff;
            box-shadow: 0 12px 28px rgb(17 113 158 / 18%);
        }

        .mwk-dash__hero {
            display: flex;
            flex-direction: column;
            gap: 1.35rem;
            padding: 1.5rem 1.5rem 1.25rem;
        }

        .mwk-dash__intro {
            display: flex;
            flex-direction: column;
            gap: .4rem;
            max-width: 40rem;
        }

        .mwk-dash__brand {
            margin: 0;
            color: color-mix(in oklab, #ffcf09 78%, white);
            font-size: .86rem;
            font-weight: 700;
            letter-spacing: .02em;
        }

        .mwk-dash__title {
            margin: 0;
            color: #fff;
            font-size: clamp(1.35rem, 2vw, 1.7rem);
            font-weight: 800;
            line-height: 1.35;
        }

        .mwk-dash__subtitle,
        .mwk-dash__empty {
            margin: 0;
            color: rgb(255 255 255 / 86%);
            font-size: .92rem;
            line-height: 1.7;
        }

        .mwk-dash__primary {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: .9rem;
        }

        .mwk-dash__cta {
            display: flex;
            min-height: 7.25rem;
            align-items: center;
            gap: .95rem;
            border: 1px solid transparent;
            border-radius: 1rem;
            padding: 1rem 1.1rem;
            text-decoration: none;
            transition: transform .18s ease, box-shadow .18s ease, border-color .18s ease;
        }

        .mwk-dash__cta:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 22px rgb(0 0 0 / 14%);
        }

        .mwk-dash__cta--green {
            border-color: color-mix(in oklab, #fff 35%, transparent);
            background: color-mix(in oklab, #fff 14%, transparent);
            color: #fff;
        }

        .mwk-dash__cta--gold {
            border-color: color-mix(in oklab, #ffcf09 65%, white);
            background: linear-gradient(135deg, #fff4b3 0%, #ffcf09 100%);
            color: #17445a;
        }

        .mwk-dash__cta-icon {
            display: grid;
            width: 3rem;
            height: 3rem;
            flex: 0 0 3rem;
            place-items: center;
            border-radius: .85rem;
            background: rgb(255 255 255 / 92%);
            color: var(--primary-700);
            box-shadow: 0 1px 4px rgb(0 0 0 / 10%);
        }

        .mwk-dash__cta--gold .mwk-dash__cta-icon {
            color: #8a6900;
        }

        .mwk-dash__cta-icon svg,
        .mwk-dash__shortcut-icon svg,
        .mwk-dash__cta-arrow svg {
            width: 1.4rem;
            height: 1.4rem;
        }

        .mwk-dash__cta-copy {
            display: flex;
            min-width: 0;
            flex: 1;
            flex-direction: column;
            gap: .28rem;
        }

        .mwk-dash__cta-copy strong {
            font-size: 1.08rem;
            font-weight: 800;
        }

        .mwk-dash__cta-copy span {
            font-size: .8rem;
            line-height: 1.55;
            opacity: .9;
        }

        .mwk-dash__cta-arrow {
            display: grid;
            place-items: center;
            opacity: .7;
        }

        .mwk-dash__shortcuts {
            border-top: 1px solid rgb(255 255 255 / 14%);
            background: rgb(255 255 255 / 96%);
            padding: 1.15rem 1.35rem 1.35rem;
        }

        .mwk-dash__shortcuts h2 {
            margin: 0 0 .75rem;
            color: #145572;
            font-size: .92rem;
            font-weight: 800;
        }

        .mwk-dash__shortcut-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(8.5rem, 1fr));
            gap: .65rem;
        }

        .mwk-dash__shortcut {
            display: flex;
            min-height: 5.4rem;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            gap: .45rem;
            border: 1px solid #d4e9f2;
            border-radius: .85rem;
            background: #f6fbfd;
            padding: .7rem .5rem;
            color: #24566d;
            font-size: .78rem;
            font-weight: 700;
            text-align: center;
            text-decoration: none;
            transition: border-color .15s ease, background .15s ease, color .15s ease;
        }

        .mwk-dash__shortcut:hover {
            border-color: color-mix(in oklab, var(--primary-500) 45%, #d4e9f2);
            background: color-mix(in oklab, var(--primary-50) 80%, white);
            color: var(--primary-700);
        }

        .mwk-dash__shortcut-icon {
            display: grid;
            width: 2.35rem;
            height: 2.35rem;
            place-items: center;
            border-radius: .7rem;
            background: #fff;
            color: var(--primary-600);
            box-shadow: 0 1px 3px rgb(17 113 158 / 10%);
        }

        @media (max-width: 768px) {
            .mwk-dash__primary {
                grid-template-columns: 1fr;
            }

            .mwk-dash__cta {
                min-height: 6.4rem;
            }
        }
    </style>
</x-filament-widgets::widget>
