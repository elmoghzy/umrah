<?php

namespace Tests\Feature;

use App\Filament\Widgets\RecentBookingsTable;
use App\Models\Booking;
use App\Models\Customer;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class FilamentSimpleResourcesTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->actingAs(User::factory()->create());
    }

    public function test_city_list_and_create_pages_work(): void
    {
        $this->assertResourcePagesWork('cities');
    }

    public function test_meal_list_and_create_pages_work(): void
    {
        $this->assertResourcePagesWork('meals');
    }

    public function test_booking_type_list_and_create_pages_work(): void
    {
        $this->assertResourcePagesWork('booking-types');
    }

    public function test_room_type_list_and_create_pages_work(): void
    {
        $this->assertResourcePagesWork('room-types');
    }

    public function test_airplane_list_and_create_pages_work(): void
    {
        $this->assertResourcePagesWork('airplanes');
    }

    public function test_broker_list_and_create_pages_work(): void
    {
        $this->assertResourcePagesWork('brokers');
    }

    public function test_company_list_and_create_pages_work(): void
    {
        $this->assertResourcePagesWork('companies');
    }

    public function test_hotel_list_and_create_pages_work(): void
    {
        $this->assertResourcePagesWork('hotels');
    }

    public function test_customer_list_create_and_view_pages_work(): void
    {
        $this->assertResourcePagesWork('customers');

        $customer = Customer::create(['name_ar' => 'ولي أمر']);

        $this->get("/admin/customers/{$customer->id}")
            ->assertSuccessful();
    }

    public function test_user_list_and_create_pages_work(): void
    {
        $this->assertResourcePagesWork('users');
    }

    public function test_booking_list_and_create_pages_work(): void
    {
        $this->assertResourcePagesWork('bookings');
    }

    public function test_dashboard_widgets_render_with_empty_database(): void
    {
        $this->get('/admin')->assertSuccessful();
    }

    public function test_dashboard_shows_recent_booking_data(): void
    {
        $guardian = Customer::create(['name_ar' => 'ولي أمر لوحة التحكم']);
        Booking::create([
            'customer_id' => $guardian->id,
            'booking_number' => 2026001,
        ]);

        Livewire::test(RecentBookingsTable::class)
            ->assertSee('2026001')
            ->assertSee('ولي أمر لوحة التحكم');
    }

    private function assertResourcePagesWork(string $resource): void
    {
        $this->get("/admin/{$resource}")
            ->assertSuccessful();

        $this->get("/admin/{$resource}/create")
            ->assertSuccessful();
    }
}
