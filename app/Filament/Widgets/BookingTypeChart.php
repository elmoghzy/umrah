<?php

namespace App\Filament\Widgets;

use App\Actions\InvalidateDashboardCache;
use App\Filament\Resources\Bookings\BookingResource;
use App\Models\Booking;
use App\Models\BookingType;
use Filament\Widgets\ChartWidget;
use Illuminate\Support\Facades\Cache;

class BookingTypeChart extends ChartWidget
{
    protected static ?int $sort = 3;

    protected ?string $pollingInterval = null;

    protected ?string $heading = 'الحجوزات حسب النوع';

    protected int|string|array $columnSpan = 1;

    public static function canView(): bool
    {
        return BookingResource::canViewAny();
    }

    protected function getData(): array
    {
        $user = auth()->user();
        $cacheKey = sprintf(
            'dashboard.types.v%d.user.%d',
            InvalidateDashboardCache::version(),
            (int) $user?->getKey(),
        );
        $data = Cache::remember($cacheKey, 300, function () use ($user): array {
            $rows = Booking::query()
                ->forUser($user)
                ->pluck('booking_type_id')
                ->countBy();
            $types = BookingType::query()
                ->whereIn('id', $rows->keys()->filter()->all())
                ->pluck('name', 'id');
            $labels = [];
            $values = [];

            foreach ($rows as $bookingTypeId => $total) {
                if ($bookingTypeId === '') {
                    continue;
                }

                $labels[] = $types[$bookingTypeId] ?? 'نوع محذوف';
                $values[] = (int) $total;
            }

            $withoutType = (int) ($rows->get('') ?? 0);

            if ($withoutType > 0) {
                $labels[] = 'بدون نوع';
                $values[] = $withoutType;
            }

            return ['labels' => $labels, 'values' => $values];
        });

        return [
            'datasets' => [[
                'label' => 'عدد الحجوزات',
                'data' => $data['values'],
                'backgroundColor' => [
                    '#0F5C4C',
                    '#C9A227',
                    '#1A7A64',
                    '#E2B75A',
                    '#3D9B82',
                    '#8A6918',
                ],
            ]],
            'labels' => $data['labels'],
        ];
    }

    protected function getType(): string
    {
        return 'doughnut';
    }
}
