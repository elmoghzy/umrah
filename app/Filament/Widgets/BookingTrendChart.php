<?php

namespace App\Filament\Widgets;

use App\Actions\InvalidateDashboardCache;
use App\Filament\Resources\Bookings\BookingResource;
use App\Models\Booking;
use Filament\Widgets\ChartWidget;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;

class BookingTrendChart extends ChartWidget
{
    protected static ?int $sort = 2;

    protected ?string $pollingInterval = null;

    protected ?string $heading = 'الحجوزات خلال آخر 6 أشهر';

    protected int|string|array $columnSpan = 1;

    public static function canView(): bool
    {
        return BookingResource::canViewAny();
    }

    protected function getData(): array
    {
        $monthNames = [
            1 => 'يناير', 2 => 'فبراير', 3 => 'مارس', 4 => 'أبريل',
            5 => 'مايو', 6 => 'يونيو', 7 => 'يوليو', 8 => 'أغسطس',
            9 => 'سبتمبر', 10 => 'أكتوبر', 11 => 'نوفمبر', 12 => 'ديسمبر',
        ];

        $months = collect(range(5, 0))->map(fn (int $offset): Carbon => now()->subMonths($offset)->startOfMonth());
        $user = auth()->user();
        $cacheKey = sprintf(
            'dashboard.trend.v%d.user.%d.start.%s',
            InvalidateDashboardCache::version(),
            (int) $user?->getKey(),
            $months->first()->toDateString(),
        );
        $counts = Cache::remember($cacheKey, 300, fn (): array => $months
            ->map(fn (Carbon $month): int => Booking::query()
                ->forUser($user)
                ->whereBetween('created_at', [
                    $month->copy()->startOfMonth(),
                    $month->copy()->endOfMonth(),
                ])
                ->count())
            ->all());

        return [
            'datasets' => [[
                'label' => 'الحجوزات',
                'data' => $counts,
                'borderColor' => '#0F5C4C',
                'backgroundColor' => 'rgba(15, 92, 76, 0.15)',
                'fill' => true,
                'tension' => 0.35,
            ]],
            'labels' => $months->map(fn (Carbon $month): string => $monthNames[$month->month])->all(),
        ];
    }

    protected function getType(): string
    {
        return 'line';
    }
}
