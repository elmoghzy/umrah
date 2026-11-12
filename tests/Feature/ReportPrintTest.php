<?php

namespace Tests\Feature;

use App\Actions\Bookings\CreateBookingAction;
use App\Enums\StayScope;
use App\Models\Booking;
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

    public function test_booking_page_print_contains_only_the_requested_bookings_in_order(): void
    {
        $bookings = Booking::factory()->count(3)->create();
        $firstPrintedBooking = $bookings[2];
        $secondPrintedBooking = $bookings[0];
        $excludedBooking = $bookings[1];

        $response = $this->actingAs(User::factory()->create())
            ->get(route('reports.bookings-page.print', [
                'bookingIds' => [$firstPrintedBooking->getKey(), $secondPrintedBooking->getKey()],
            ]));

        $response->assertOk()
            ->assertSeeInOrder([
                (string) $firstPrintedBooking->booking_number,
                (string) $secondPrintedBooking->booking_number,
            ])
            ->assertDontSee((string) $excludedBooking->booking_number)
            ->assertSee('الإجمالي: 2');
    }

    public function test_booking_page_print_rejects_more_than_250_booking_ids(): void
    {
        $response = $this->actingAs(User::factory()->create())
            ->from('/admin/bookings')
            ->get(route('reports.bookings-page.print', ['bookingIds' => range(1, 251)]));

        $response->assertRedirect('/admin/bookings')
            ->assertSessionHasErrors('bookingIds');
    }
}
