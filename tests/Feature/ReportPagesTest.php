<?php

namespace Tests\Feature;

use App\Filament\Pages\BookingsReport;
use App\Filament\Pages\HotelRoomsReport;
use App\Filament\Pages\TripMovementsReport;
use App\Models\BookingDetail;
use App\Models\Hotel;
use App\Models\User;
use Database\Seeders\DemoDataSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class ReportPagesTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->actingAs(User::factory()->create());
        $this->seed(DemoDataSeeder::class);
    }

    public function test_report_pages_render_demo_data(): void
    {
        $this->get('/admin/bookings-report')->assertOk();
        $this->get('/admin/trip-movements-report')->assertOk();
        $this->get('/admin/hotel-rooms-report')->assertOk();

        Livewire::test(BookingsReport::class)->assertSee('فندق أنوار مكة');
        Livewire::test(TripMovementsReport::class)->assertSee('فندق أنوار مكة');
        Livewire::test(HotelRoomsReport::class)->assertSee('فندق أنوار مكة');
    }

    public function test_booking_list_hotel_filter_changes_results_and_print_url(): void
    {
        $hotel = Hotel::query()->where('name_ar', 'فندق أنوار مكة')->firstOrFail();
        Livewire::test(BookingsReport::class)
            ->filterTable('hotel_id', $hotel)
            ->call('applyTableFilters')
            ->assertCountTableRecords(BookingDetail::query()->where('hotel_id', $hotel->id)->whereHas('booking')->count())
            ->assertSee('hotelId='.$hotel->id);
    }

    public function test_trip_and_room_filters_change_results(): void
    {
        $hotel = Hotel::query()->where('name_ar', 'فندق أنوار مكة')->firstOrFail();
        Livewire::test(TripMovementsReport::class)
            ->filterTable('hotel_id', $hotel)
            ->call('applyTableFilters')
            ->assertCountTableRecords(BookingDetail::query()->where('hotel_id', $hotel->id)->whereHas('booking')->count());

        Livewire::test(HotelRoomsReport::class)
            ->filterTable('hotel_id', $hotel)
            ->call('applyTableFilters')
            ->assertCountTableRecords(BookingDetail::query()->where('hotel_id', $hotel->id)->whereHas('booking')->count());
    }
}
