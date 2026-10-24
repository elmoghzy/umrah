<?php

namespace App\Filament\Widgets;

use App\Filament\Resources\Bookings\BookingResource;
use App\Models\Booking;
use Filament\Widgets\ChartWidget;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class BookingTrendChart extends ChartWidget
{
    protected static ?int $sort = 2;

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

        $start = now()->subMonths(5)->startOfMonth();
        $end = now()->endOfMonth();
        $driver = DB::connection()->getDriverName();

        $yearExpression = $driver === 'sqlite'
            ? "cast(strftime('%Y', created_at) as integer)"
            : 'year(created_at)';
        $monthExpression = $driver === 'sqlite'
            ? "cast(strftime('%m', created_at) as integer)"
            : 'month(created_at)';

        $countsByMonth = Booking::query()
            ->forUser(auth()->user())
            ->whereBetween('created_at', [$start, $end])
            ->selectRaw("{$yearExpression} as y, {$monthExpression} as m, count(*) as total")
            ->groupByRaw("{$yearExpression}, {$monthExpression}")
            ->get()
            ->keyBy(fn ($row): string => sprintf('%04d-%02d', $row->y, $row->m));

        $months = collect(range(5, 0))->map(fn (int $offset): Carbon => now()->subMonths($offset)->startOfMonth());

        return [
            'datasets' => [[
                'label' => 'الحجوزات',
                'data' => $months
                    ->map(fn (Carbon $month): int => (int) ($countsByMonth->get($month->format('Y-m'))->total ?? 0))
                    ->all(),
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
