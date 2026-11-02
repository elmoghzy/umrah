<?php

namespace App\Filament\Widgets;

use App\Actions\InvalidateDashboardCache;
use App\Enums\VisaStatus;
use App\Filament\Resources\Visas\VisaResource;
use App\Models\Visa;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Facades\Cache;

class PendingVisas extends StatsOverviewWidget
{
    protected static ?int $sort = 3;

    protected ?string $pollingInterval = null;

    protected int|string|array $columnSpan = 'full';

    public static function canView(): bool
    {
        return VisaResource::canViewAny();
    }

    protected function getStats(): array
    {
        $user = auth()->user();
        $cacheKey = sprintf(
            'dashboard.visas.v%d.user.%d',
            InvalidateDashboardCache::version(),
            (int) $user?->getKey(),
        );
        $count = Cache::remember($cacheKey, 300, fn (): int => Visa::query()
            ->forUser($user)
            ->where('status', VisaStatus::Pending->value)
            ->count());

        return [
            Stat::make('تأشيرات قيد الاستخراج', number_format($count))
                ->description('حسب الحجوزات المتاحة لحسابك')
                ->descriptionIcon('heroicon-m-identification')
                ->color($count > 0 ? 'warning' : 'success')
                ->url(VisaResource::getUrl('index')),
        ];
    }
}
