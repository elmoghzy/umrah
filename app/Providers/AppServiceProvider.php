<?php

namespace App\Providers;

use App\Models\Booking;
use App\Models\BookingDetail;
use App\Models\BookingType;
use App\Models\Customer;
use App\Models\Hotel;
use App\Models\Room;
use App\Models\RoomResident;
use App\Models\Visa;
use App\Observers\DashboardCacheObserver;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        foreach ([
            Booking::class,
            BookingDetail::class,
            BookingType::class,
            Customer::class,
            Hotel::class,
            Room::class,
            RoomResident::class,
            Visa::class,
        ] as $model) {
            $model::observe(DashboardCacheObserver::class);
        }
    }
}
