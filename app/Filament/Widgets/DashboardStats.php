<?php

namespace App\Filament\Widgets;

use App\Actions\InvalidateDashboardCache;
use App\Filament\Resources\Bookings\BookingResource;
use App\Filament\Resources\Customers\CustomerResource;
use App\Models\Booking;
use App\Models\Customer;
use App\Models\Hotel;
use App\Models\Room;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Number;

class DashboardStats extends StatsOverviewWidget
{
    protected static ?int $sort = 1;

    protected ?string $pollingInterval = null;

    protected int|string|array $columnSpan = 'full';

    public static function canView(): bool
    {
        return BookingResource::canViewAny() || CustomerResource::canViewAny();
    }

    protected function getStats(): array
    {
        $stats = [];
        $user = auth()->user();
        $canViewBookings = BookingResource::canViewAny();
        $canViewCustomers = CustomerResource::canViewAny();
        $cacheKey = sprintf(
            'dashboard.stats.v%d.user.%d.day.%s.month.%s',
            InvalidateDashboardCache::version(),
            (int) $user?->getKey(),
            today()->toDateString(),
            now()->format('Y-m'),
        );
        $metrics = Cache::remember($cacheKey, 300, function () use ($user, $canViewBookings, $canViewCustomers): array {
            $metrics = [];

            if ($canViewBookings) {
                $metrics['total_bookings'] = Booking::query()->forUser($user)->count();
                $metrics['todays_bookings'] = Booking::query()
                    ->forUser($user)
                    ->whereBetween('created_at', [today()->startOfDay(), today()->endOfDay()])
                    ->count();
                $metrics['month_net'] = (float) Booking::query()
                    ->forUser($user)
                    ->whereBetween('created_at', [now()->startOfMonth(), now()->endOfMonth()])
                    ->sum('net_amount');
                $metrics['needs_follow_up'] = Booking::query()
                    ->forUser($user)
                    ->where(function ($query): void {
                        $query
                            ->whereDoesntHave('details')
                            ->orWhereDoesntHave('residents');
                    })
                    ->count();
            }

            if ($canViewCustomers) {
                $metrics['residents'] = Customer::query()->whereNotNull('guardian_id')->count();
            }

            if ($canViewBookings && $user?->isMainAdmin()) {
                $metrics['hotel_capacity'] = (int) Hotel::query()->sum('rooms_count');
                $metrics['occupied_rooms'] = Room::query()
                    ->whereHas('bookingDetail', function ($query) use ($user): void {
                        $query
                            ->whereHas('booking', fn ($booking) => $booking->forUser($user))
                            ->where('check_in', '<=', today())
                            ->where('check_out', '>=', today());
                    })
                    ->count();
            }

            return $metrics;
        });

        if ($canViewBookings) {
            $stats[] = Stat::make('إجمالي الحجوزات', number_format($metrics['total_bookings']))
                ->description('كل الحجوزات المسجّلة')
                ->descriptionIcon('heroicon-m-clipboard-document-list')
                ->color('primary')
                ->url(BookingResource::getUrl());

            $stats[] = Stat::make('حجوزات اليوم', number_format($metrics['todays_bookings']))
                ->description('أُنشئت اليوم')
                ->descriptionIcon('heroicon-m-calendar-days')
                ->color('warning');

            $stats[] = Stat::make('تحتاج متابعة', number_format($metrics['needs_follow_up']))
                ->description('بدون إقامة أو بدون معتمرين')
                ->descriptionIcon('heroicon-m-exclamation-triangle')
                ->color($metrics['needs_follow_up'] > 0 ? 'danger' : 'success');

            $stats[] = Stat::make('صافي الشهر', Number::currency(
                $metrics['month_net'],
                config('umrah.currency_code'),
                config('umrah.currency_locale'),
                (int) config('umrah.currency_decimal_places'),
            ))
                ->description('مجموع صافي مبالغ حجوزات الشهر')
                ->descriptionIcon('heroicon-m-banknotes')
                ->color('success');
        }

        if ($canViewCustomers) {
            $stats[] = Stat::make('إجمالي المعتمرين', number_format($metrics['residents']))
                ->description('المسجّلون تحت أولياء الأمور')
                ->descriptionIcon('heroicon-m-users')
                ->color('primary')
                ->url(CustomerResource::getUrl());
        }

        if ($canViewBookings) {
            $hotelCapacity = $metrics['hotel_capacity'] ?? 0;

            if ($hotelCapacity > 0) {
                $occupiedRooms = $metrics['occupied_rooms'];

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
