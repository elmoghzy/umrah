<?php

namespace Tests\Feature;

use App\Filament\Resources\Bookings\BookingResource;
use App\Filament\Resources\Customers\CustomerResource;
use App\Filament\Widgets\DashboardQuickActions;
use App\Filament\Widgets\DashboardStats;
use App\Filament\Widgets\RecentBookingsTable;
use App\Models\Booking;
use App\Models\City;
use App\Models\Customer;
use App\Models\Hotel;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class DashboardTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->actingAs(User::factory()->create(['name' => 'موظف الاختبار']));
    }

    public function test_dashboard_page_loads_successfully(): void
    {
        $this->get('/admin')
            ->assertSuccessful()
            ->assertSee('مواكب لإدارة العمرة');
    }

    public function test_quick_actions_link_to_existing_create_pages(): void
    {
        Livewire::test(DashboardQuickActions::class)
            ->assertSee('مواكب لإدارة العمرة')
            ->assertSee('أهلاً بك، موظف الاختبار')
            ->assertSee('إضافة ولي أمر')
            ->assertSee('إضافة حجز جديد')
            ->assertSeeHtml(CustomerResource::getUrl('create'))
            ->assertSeeHtml(BookingResource::getUrl('create'))
            ->assertSeeHtml(BookingResource::getUrl())
            ->assertSee('البحث عن حجز');
    }

    public function test_create_guardian_and_booking_routes_remain_available(): void
    {
        $this->get(CustomerResource::getUrl('create'))->assertSuccessful();
        $this->get(BookingResource::getUrl('create'))->assertSuccessful();
    }

    public function test_stats_widget_reflects_database_counts(): void
    {
        $guardian = Customer::create(['name_ar' => 'ولي أمر الإحصائيات']);
        Customer::create(['name_ar' => 'معتمر', 'guardian_id' => $guardian->id]);
        Booking::create([
            'customer_id' => $guardian->id,
            'booking_number' => 20261001,
            'net_amount' => 1500,
        ]);

        Livewire::test(DashboardStats::class)
            ->assertSee('إجمالي الحجوزات')
            ->assertSee('1')
            ->assertSee('إجمالي المعتمرين')
            ->assertSee('تحتاج متابعة')
            ->assertSee('صافي الشهر');
    }

    public function test_room_availability_stats_appear_when_hotel_capacity_exists(): void
    {
        $city = City::create(['name' => 'مكة']);

        Hotel::create([
            'city_id' => $city->id,
            'name_ar' => 'فندق الاختبار',
            'rooms_count' => 10,
        ]);

        Livewire::test(DashboardStats::class)
            ->assertSee('الغرف المشغولة')
            ->assertSee('الغرف المتاحة');
    }

    public function test_recent_bookings_table_shows_booking_and_view_link(): void
    {
        $guardian = Customer::create(['name_ar' => 'ولي أمر الجدول']);
        $booking = Booking::create([
            'customer_id' => $guardian->id,
            'booking_number' => 20261099,
            'net_amount' => 900,
        ]);

        Livewire::test(RecentBookingsTable::class)
            ->assertSee('20261099')
            ->assertSee('ولي أمر الجدول')
            ->assertSee('عرض');

        $this->get(BookingResource::getUrl('view', ['record' => $booking]))
            ->assertSuccessful();
    }
}
