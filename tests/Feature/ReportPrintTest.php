<?php

namespace Tests\Feature;

use App\Actions\Bookings\CreateBookingAction;
use App\Enums\StayScope;
use App\Models\BookingType;
use App\Models\City;
use App\Models\Company;
use App\Models\Customer;
use App\Models\Hotel;
use App\Models\Meal;
use App\Models\RoomType;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ReportPrintTest extends TestCase
{
    use RefreshDatabase;

    public function test_authenticated_user_can_print_reports_and_filters_change_results(): void
    {
        City::insert([['id' => 1, 'name' => 'مكة'], ['id' => 2, 'name' => 'المدينة المنورة']]);
        $customer = Customer::create(['name_ar' => 'عميل التقرير']);
        $type = BookingType::create(['name' => 'خاص']);
        $company = Company::create(['name' => 'شركة التقرير']);
        $meal = Meal::create(['name' => 'BB']);
        $roomType = RoomType::create(['name' => 'ثنائية', 'size' => 2, 'price' => 0, 'total_price' => 0]);
        $hotel = Hotel::create(['city_id' => 1, 'name_ar' => 'فندق التقرير']);
        app(CreateBookingAction::class)->handle([
            'customer_id' => $customer->id,
            'booking_type_id' => $type->id,
            'stay_scope' => StayScope::MakkaOnly,
            'residents' => [['customer_id' => $customer->id]],
            'rooms' => [['room_type_id' => $roomType->id, 'price' => 0]],
            'makka_hotel_id' => $hotel->id,
            'makka_company_id' => $company->id,
            'makka_meal_id' => $meal->id,
            'makka_check_in' => '2026-11-01',
            'makka_check_out' => '2026-11-03',
            'service_amount' => 0,
            'discount' => 0,
        ]);

        $this->actingAs(User::factory()->create());

        $response = $this->get(route('reports.booking-list.print'));

        $response->assertOk()
            ->assertSee('عميل التقرير')
            ->assertSee('مواكب للاستثمار السياحي');
        $this->get(route('reports.trip-movements.print'))->assertOk();
        $this->get(route('reports.hotel-rooms.print'))->assertOk();
        $this->get(route('reports.booking-list.print', ['bookingTypeId' => 999]))
            ->assertOk()
            ->assertDontSee('عميل التقرير');
    }
}
