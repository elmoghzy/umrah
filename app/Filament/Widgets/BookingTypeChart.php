<?php

namespace App\Filament\Widgets;

use App\Filament\Resources\Bookings\BookingResource;
use App\Models\Booking;
use App\Models\BookingType;
use Filament\Widgets\ChartWidget;

class BookingTypeChart extends ChartWidget
{
    protected static ?int $sort = 3;

    protected ?string $heading = 'الحجوزات حسب النوع';

    protected int|string|array $columnSpan = 1;

    public static function canView(): bool
    {
        return BookingResource::canViewAny();
    }

    protected function getData(): array
    {
        $rows = Booking::query()
            ->forUser(auth()->user())
            ->selectRaw('booking_type_id, count(*) as total')
            ->groupBy('booking_type_id')
            ->get();

        $typeIds = $rows->pluck('booking_type_id')->filter()->all();
        $types = BookingType::query()->whereIn('id', $typeIds)->pluck('name', 'id');

        $labels = [];
        $data = [];

        foreach ($rows as $row) {
            if ($row->booking_type_id === null) {
                continue;
            }

            $labels[] = $types[$row->booking_type_id] ?? 'نوع محذوف';
            $data[] = (int) $row->total;
        }

        $withoutType = (int) ($rows->firstWhere('booking_type_id', null)?->total ?? 0);

        if ($withoutType > 0) {
            $labels[] = 'بدون نوع';
            $data[] = $withoutType;
        }

        return [
            'datasets' => [[
                'label' => 'عدد الحجوزات',
                'data' => $data,
                'backgroundColor' => [
                    '#0F5C4C',
                    '#C9A227',
                    '#1A7A64',
                    '#E2B75A',
                    '#3D9B82',
                    '#8A6918',
                ],
            ]],
            'labels' => $labels,
        ];
    }

    protected function getType(): string
    {
        return 'doughnut';
    }
}
