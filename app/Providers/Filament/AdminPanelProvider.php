<?php

namespace App\Providers\Filament;

use App\Filament\Pages\Auth\BranchLogin;
use App\Filament\Pages\Dashboard;
use App\Filament\Widgets\BookingTrendChart;
use App\Filament\Widgets\BookingTypeChart;
use App\Filament\Widgets\DashboardQuickActions;
use App\Filament\Widgets\RecentBookingsTable;
use Filament\FontProviders\LocalFontProvider;
use Filament\Http\Middleware\Authenticate;
use Filament\Http\Middleware\AuthenticateSession;
use Filament\Http\Middleware\DisableBladeIconComponents;
use Filament\Http\Middleware\DispatchServingFilamentEvent;
use Filament\Navigation\NavigationItem;
use Filament\Panel;
use Filament\PanelProvider;
use Filament\Support\Colors\Color;
use Filament\Support\Icons\Heroicon;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Foundation\Http\Middleware\PreventRequestForgery;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\View\Middleware\ShareErrorsFromSession;

class AdminPanelProvider extends PanelProvider
{
    public function panel(Panel $panel): Panel
    {
        return $panel
            ->default()
            ->id('admin')
            ->path('admin')
            ->login(BranchLogin::class)
            ->spa()
            ->sidebarCollapsibleOnDesktop()
            ->unsavedChangesAlerts()
            ->globalSearch(true)
            ->brandName('مواكب لإدارة العمرة')
            ->brandLogo(asset(config('umrah.logo_path')))
            ->brandLogoHeight('3rem')
            ->favicon(asset(config('umrah.logo_path')))
            ->darkMode(false)
            ->font('Cairo', asset('fonts/cairo.css'), LocalFontProvider::class)
            ->navigationGroups([
                'الحجوزات',
                'العملاء',
                'البيانات الأساسية',
                'التقارير',
                'الإعدادات',
            ])
            ->navigationItems([
                NavigationItem::make('مقفولة لموظف الفرع')
                    ->group('البيانات الأساسية')
                    ->icon(Heroicon::OutlinedLockClosed)
                    ->url('#')
                    ->visible(fn (): bool => auth()->user()?->isBranchEmployee() ?? false)
                    ->extraAttributes([
                        'aria-disabled' => 'true',
                        'style' => 'opacity: .55; pointer-events: none;',
                        'x-on:click.capture.prevent' => 'true',
                    ]),
                NavigationItem::make('إدارة المستخدمين (مقفولة)')
                    ->group('الإعدادات')
                    ->icon(Heroicon::OutlinedLockClosed)
                    ->url('#')
                    ->visible(fn (): bool => auth()->user()?->isBranchEmployee() ?? false)
                    ->extraAttributes([
                        'aria-disabled' => 'true',
                        'style' => 'opacity: .55; pointer-events: none;',
                        'x-on:click.capture.prevent' => 'true',
                    ]),
            ])
            ->colors([
                'primary' => Color::hex('#1BA6E1'),
                'warning' => Color::hex('#FFCF09'),
                'success' => Color::Emerald,
            ])
            ->discoverResources(in: app_path('Filament/Resources'), for: 'App\Filament\Resources')
            ->discoverPages(in: app_path('Filament/Pages'), for: 'App\Filament\Pages')
            ->pages([
                Dashboard::class,
            ])
            ->discoverWidgets(in: app_path('Filament/Widgets'), for: 'App\Filament\Widgets')
            ->widgets([
                DashboardQuickActions::class,
                BookingTrendChart::class,
                BookingTypeChart::class,
                RecentBookingsTable::class,
            ])
            ->middleware([
                EncryptCookies::class,
                AddQueuedCookiesToResponse::class,
                StartSession::class,
                AuthenticateSession::class,
                ShareErrorsFromSession::class,
                PreventRequestForgery::class,
                SubstituteBindings::class,
                DisableBladeIconComponents::class,
                DispatchServingFilamentEvent::class,
            ])
            ->authMiddleware([
                Authenticate::class,
            ]);
    }
}
