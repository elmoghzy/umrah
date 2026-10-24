<?php

namespace App\Filament\Pages;

use App\Filament\Widgets\DashboardQuickActions;
use App\Filament\Widgets\DashboardStats;
use App\Filament\Widgets\PendingVisas;
use BackedEnum;
use Filament\Pages\Dashboard as BaseDashboard;
use Filament\Support\Icons\Heroicon;

class Dashboard extends BaseDashboard
{
    protected static bool $isDiscovered = false;

    protected static ?string $navigationLabel = 'لوحة التحكم';

    protected static ?string $title = 'لوحة التحكم';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedHome;

    public function getColumns(): int|array
    {
        return [
            'default' => 1,
            'md' => 2,
            'xl' => 2,
        ];
    }

    public function getWidgets(): array
    {
        return [
            DashboardQuickActions::class,
            DashboardStats::class,
            PendingVisas::class,
        ];
    }
}
