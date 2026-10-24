<?php

namespace App\Filament\Widgets;

use App\Filament\Resources\Bookings\BookingResource;
use App\Filament\Resources\Customers\CustomerResource;
use App\Models\Booking;
use App\Models\Customer;
use App\Models\Hotel;
use App\Models\Room;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Number;

class DashboardStats extends StatsOverviewWidget
{
    protected static ?int $sort = 1;

    protected int|string|array $columnSpan = 'full';

    public static function canView(): bool
    {
        return BookingResource::canViewAny() || CustomerResource::canViewAny();
    }

    protected function getStats(): array
    {
        $stats = [];

        if (BookingResource::canViewAny()) {
            $totalBookings = Booking::query()->forUser(auth()->user())->count();
            $todaysBookings = Booking::query()->forUser(auth()->user())->whereDate('created_at', today())->count();
            $monthNet = (float) Booking::query()
                ->forUser(auth()->user())
                ->whereBetween('created_at', [now()->startOfMonth(), now()->endOfMonth()])
                ->sum('net_amount');
            $needsFollowUp = Booking::query()
                ->forUser(auth()->user())
                ->where(function ($query): void {
                    $query
                        ->whereDoesntHave('details')
                        ->orWhereDoesntHave('residents');
                })
                ->count();

            $stats[] = Stat::make('إجمالي الحجوزات', number_format($totalBookings))
                ->description('كل الحجوزات المسجّلة')
                ->descriptionIcon('heroicon-m-clipboard-document-list')
                ->color('primary')
                ->url(BookingResource::getUrl());

            $stats[] = Stat::make('حجوزات اليوم', number_format($todaysBookings))
                ->description('أُنشئت اليوم')
                ->descriptionIcon('heroicon-m-calendar-days')
                ->color('warning');

            $stats[] = Stat::make('تحتاج متابعة', number_format($needsFollowUp))
                ->description('بدون إقامة أو بدون معتمرين')
                ->descriptionIcon('heroicon-m-exclamation-triangle')
                ->color($needsFollowUp > 0 ? 'danger' : 'success');

            $stats[] = Stat::make('صافي الشهر', Number::currency(
                $monthNet,
                config('umrah.currency_code'),
                config('umrah.currency_locale'),
                (int) config('umrah.currency_decimal_places'),
            ))
                ->description('مجموع صافي مبالغ حجوزات الشهر')
                ->descriptionIcon('heroicon-m-banknotes')
                ->color('success');
        }

        if (CustomerResource::canViewAny()) {
            $residents = Customer::query()->whereNotNull('guardian_id')->count();

            $stats[] = Stat::make('إجمالي المعتمرين', number_format($residents))
                ->description('المسجّلون تحت أولياء الأمور')
                ->descriptionIcon('heroicon-m-users')
                ->color('primary')
                ->url(CustomerResource::getUrl());
        }

        if (BookingResource::canViewAny()) {
            $hotelCapacity = auth()->user()?->isMainAdmin()
                ? (int) Hotel::query()->sum('rooms_count')
                : 0;

            if ($hotelCapacity > 0) {
                $occupiedRooms = Room::query()
                    ->whereHas('bookingDetail', function ($query): void {
                        $query
                            ->whereHas('booking', fn ($booking) => $booking->forUser(auth()->user()))
                            ->whereDate('check_in', '<=', today())
                            ->whereDate('check_out', '>=', today());
                    })
                    ->count();

                $availableRooms = max(0, $hotelCapacity - $occupiedRooms);

                $stats[] = Stat::make('الغرف المشغولة', number_format($occupiedRooms))
                    ->description('من أصل '.number_format($hotelCapacity).' غرفة مسجّلة')
                    ->descriptionIcon('heroicon-m-home-modern')
                    ->color('warning');

                $stats[] = Stat::make('الغرف المتاحة', number_format($availableRooms))
                    ->description('تقريبي وفق سعة الفنادق والإقامات الحالية')
                    ->descriptionIcon('heroicon-m-check-circle')
                    ->color('success');
            }
        }

        return $stats;
    }
}
