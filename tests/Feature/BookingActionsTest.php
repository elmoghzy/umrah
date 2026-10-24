<?php

namespace Tests\Feature;

use App\Actions\Bookings\CreateBookingAction;
use App\Enums\StayScope;
use App\Models\Airplane;
use App\Models\BookingType;
use App\Models\Broker;
use App\Models\City;
use App\Models\Company;
use App\Models\Customer;
use App\Models\Hotel;
use App\Models\Meal;
use App\Models\RoomType;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class BookingActionsTest extends TestCase
{
    use RefreshDatabase;

    public function test_both_scope_creates_two_details(): void
    {
        $booking = app(CreateBookingAction::class)->handle($this->bookingData(StayScope::Both));

        $this->assertSame(2, $booking->details()->count());
        $this->assertSame(1, $booking->residents()->count());
    }

    public function test_madina_scope_creates_only_madina_detail(): void
    {
        $booking = app(CreateBookingAction::class)->handle($this->bookingData(StayScope::MadinaOnly));

        $this->assertSame(1, $booking->details()->where('city_id', 2)->count());
        $this->assertSame(0, $booking->details()->where('city_id', 1)->count());
    }

    public function test_makka_scope_creates_only_makka_detail(): void
    {
        $booking = app(CreateBookingAction::class)->handle($this->bookingData(StayScope::MakkaOnly));

        $this->assertSame(1, $booking->details()->where('city_id', 1)->count());
        $this->assertSame(0, $booking->details()->where('city_id', 2)->count());
    }

    public function test_server_recalculates_net_amount(): void
    {
        $data = $this->bookingData(StayScope::MakkaOnly);
        $data['rooms'][0]['price'] = 100;
        $data['service_amount'] = 25;
        $data['discount'] = 10;

        $booking = app(CreateBookingAction::class)->handle($data);

        $this->assertSame('215.00', (string) $booking->net_amount);
        $this->assertSame('200.00', (string) $booking->total_amount);
    }

    public function test_booking_numbers_are_sequential(): void
    {
        $first = app(CreateBookingAction::class)->handle($this->bookingData(StayScope::MakkaOnly));
        $second = app(CreateBookingAction::class)->handle($this->bookingData(StayScope::MakkaOnly));

        $this->assertSame($first->booking_number + 1, $second->booking_number);
    }

    public function test_guardian_dependents_are_added_to_residents(): void
    {
        $data = $this->bookingData(StayScope::MakkaOnly);
        $dependent = Customer::create(['name_ar' => 'المرافق', 'guardian_id' => $data['customer_id']]);

        $booking = app(CreateBookingAction::class)->handle($data);

        $this->assertEqualsCanonicalizing(
            [$data['customer_id'], $dependent->id],
            $booking->residents()->pluck('customer_id')->all(),
        );
    }

    public function test_booking_without_rooms_is_rejected(): void
    {
        $this->expectException(ValidationException::class);

        $data = $this->bookingData(StayScope::MakkaOnly);
        $data['rooms'] = [];

        app(CreateBookingAction::class)->handle($data);
    }

    /** @return array<string, mixed> */
    private function bookingData(StayScope $scope): array
    {
        City::query()->upsert([
            ['id' => 1, 'name' => 'مكة'],
            ['id' => 2, 'name' => 'المدينة المنورة'],
        ], ['id'], ['name']);
        $customer = Customer::create(['name_ar' => 'ولي الأمر']);
        $roomType = RoomType::create(['name' => 'ثنائية', 'size' => 2, 'price' => 50, 'total_price' => 100]);
        $company = Company::create(['name' => 'شركة']);
        $meal = Meal::create(['name' => 'BB']);
        $bookingType = BookingType::create(['name' => 'عام']);
        $broker = Broker::create(['name' => 'وسيط']);
        $airplane = Airplane::create(['name' => 'طائرة']);
        $madinaHotel = Hotel::create(['city_id' => 2, 'name_ar' => 'فندق المدينة']);
        $makkaHotel = Hotel::create(['city_id' => 1, 'name_ar' => 'فندق مكة']);

        return [
            'customer_id' => $customer->id,
            'booking_type_id' => $bookingType->id,
            'broker_id' => $broker->id,
            'airplane_id' => $airplane->id,
            'stay_scope' => $scope,
            'service_amount' => 0,
            'discount' => 0,
            'residents' => [['customer_id' => $customer->id]],
            'rooms' => [['room_type_id' => $roomType->id, 'price' => 50]],
            'madina_hotel_id' => $madinaHotel->id,
            'madina_company_id' => $company->id,
            'madina_meal_id' => $meal->id,
            'madina_check_in' => '2026-11-01',
            'madina_check_out' => '2026-11-03',
            'makka_hotel_id' => $makkaHotel->id,
            'makka_company_id' => $company->id,
            'makka_meal_id' => $meal->id,
            'makka_check_in' => '2026-11-04',
            'makka_check_out' => '2026-11-06',
        ];
    }
}
