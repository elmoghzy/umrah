<?php

namespace App\Filament\Widgets;

use App\Filament\Pages\BookingsReport;
use App\Filament\Pages\HotelRoomsReport;
use App\Filament\Pages\TripMovementsReport;
use App\Filament\Resources\Airplanes\AirplaneResource;
use App\Filament\Resources\Bookings\BookingResource;
use App\Filament\Resources\Customers\CustomerResource;
use App\Filament\Resources\Hotels\HotelResource;
use App\Filament\Resources\RoomTypes\RoomTypeResource;
use Filament\Widgets\Widget;

class DashboardQuickActions extends Widget
{
    protected static ?int $sort = 0;

    protected int|string|array $columnSpan = 'full';

    protected string $view = 'filament.widgets.dashboard-quick-actions';

    /**
     * @return array{
     *     user_name: string,
     *     primary_actions: array<int, array<string, string>>,
     *     shortcuts: array<int, array<string, string>>
     * }
     */
    protected function getViewData(): array
    {
        $primaryActions = [];

        if (CustomerResource::canCreate()) {
            $primaryActions[] = [
                'label' => 'إضافة ولي أمر',
                'description' => 'أنشئ ملف ولي الأمر وأضف المعتمرين التابعين له ضمن نفس التدفق الحالي.',
                'url' => CustomerResource::getUrl('create'),
                'icon' => 'heroicon-o-user-plus',
                'tone' => 'green',
            ];
        }

        if (BookingResource::canCreate()) {
            $primaryActions[] = [
                'label' => 'إضافة حجز جديد',
                'description' => 'افتح نموذج الحجز، اختر ولي أمر مسجّلًا بالبحث، ثم راجع البيانات قبل الحفظ.',
                'url' => BookingResource::getUrl('create'),
                'icon' => 'heroicon-o-calendar-days',
                'tone' => 'gold',
            ];
        }

        $shortcuts = [];

        if (BookingResource::canViewAny()) {
            $shortcuts[] = [
                'label' => 'البحث عن حجز',
                'url' => BookingResource::getUrl(),
                'icon' => 'heroicon-o-magnifying-glass',
            ];
        }

        if (CustomerResource::canViewAny()) {
            $shortcuts[] = [
                'label' => 'أولياء الأمور والمعتمرون',
                'url' => CustomerResource::getUrl(),
                'icon' => 'heroicon-o-users',
            ];
        }

        if (HotelResource::canViewAny()) {
            $shortcuts[] = [
                'label' => 'الفنادق',
                'url' => HotelResource::getUrl(),
                'icon' => 'heroicon-o-building-office-2',
            ];
        }

        if (RoomTypeResource::canViewAny()) {
            $shortcuts[] = [
                'label' => 'أنواع الغرف',
                'url' => RoomTypeResource::getUrl(),
                'icon' => 'heroicon-o-home-modern',
            ];
        }

        if (AirplaneResource::canViewAny()) {
            $shortcuts[] = [
                'label' => 'الرحلات والطائرات',
                'url' => AirplaneResource::getUrl(),
                'icon' => 'heroicon-o-paper-airplane',
            ];
        }

        foreach ([
            ['label' => 'تقرير الحجوزات', 'page' => BookingsReport::class, 'icon' => 'heroicon-o-chart-bar'],
            ['label' => 'تقرير الغرف', 'page' => HotelRoomsReport::class, 'icon' => 'heroicon-o-building-office'],
            ['label' => 'حركة الرحلات', 'page' => TripMovementsReport::class, 'icon' => 'heroicon-o-map'],
        ] as $report) {
            if ($report['page']::canAccess()) {
                $shortcuts[] = [
                    'label' => $report['label'],
                    'url' => $report['page']::getUrl(),
                    'icon' => $report['icon'],
                ];
            }
        }

        return [
            'user_name' => auth()->user()?->name ?? 'فريق العمل',
            'primary_actions' => $primaryActions,
            'shortcuts' => $shortcuts,
        ];
    }
}
