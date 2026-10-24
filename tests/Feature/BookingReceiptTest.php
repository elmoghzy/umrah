<?php

namespace Tests\Feature;

use App\Models\Booking;
use App\Models\BookingDetail;
use App\Models\City;
use App\Models\Customer;
use App\Models\Hotel;
use App\Models\Meal;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Config;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class BookingReceiptTest extends TestCase
{
    use RefreshDatabase;

    public function test_receipt_displays_booking_number_and_residents(): void
    {
        $user = User::factory()->create();
        City::insert([['id' => 1, 'name' => 'مكة'], ['id' => 2, 'name' => 'المدينة المنورة']]);
        $hotel = Hotel::create(['city_id' => 1, 'name_ar' => 'فندق مكة']);
        $meal = Meal::create(['name' => 'BB']);
        $customer = Customer::create(['name_ar' => 'ولي الأمر']);
        $booking = Booking::create([
            'customer_id' => $customer->id,
            'booking_number' => 1,
            'stay_scope' => 2,
        ]);
        $booking->residents()->create(['customer_id' => $customer->id]);
        BookingDetail::create([
            'booking_id' => $booking->id,
            'hotel_id' => $hotel->id,
            'meal_id' => $meal->id,
            'city_id' => 1,
            'check_in' => '2026-11-01',
            'check_out' => '2026-11-03',
        ]);
        BookingDetail::create([
            'booking_id' => $booking->id,
            'hotel_id' => $hotel->id,
            'meal_id' => $meal->id,
            'city_id' => 2,
            'check_in' => '2026-11-03',
            'check_out' => '2026-11-04',
        ]);

        $response = $this->actingAs($user)->get(route('bookings.receipt', $booking));

        $response->assertOk()
            ->assertSee('1')
            ->assertSee('ولي الأمر')
            ->assertSee('مواكب للاستثمار السياحي')
            ->assertSee('سكن مكة')
            ->assertDontSee('سكن المدينة')
            ->assertSee('src="http://localhost:8000/img/UmrahManagment.png"', false)
            ->assertSee('class="watermark receipt-watermark"', false)
            ->assertSee('print-color-adjust: exact', false);
    }

    #[DataProvider('travelerDistributionCases')]
    public function test_receipt_splits_travelers_into_columns_with_continuous_numbers(int $travelerCount, int $expectedTables): void
    {
        Config::set('umrah.receipt_travelers_per_column', 10);
        Config::set('umrah.receipt_traveler_columns', 2);

        $user = User::factory()->create();
        $customers = collect(range(1, $travelerCount))
            ->map(fn (int $number) => Customer::create(['name_ar' => 'معتمر '.$number]));
        $booking = Booking::create([
            'customer_id' => $customers->first()->id,
            'booking_number' => $travelerCount,
            'stay_scope' => 2,
        ]);

        foreach ($customers as $customer) {
            $booking->residents()->create(['customer_id' => $customer->id]);
        }

        $response = $this->actingAs($user)->get(route('bookings.receipt', $booking));
        $content = $response->assertOk()->getContent();

        preg_match_all('/<table class="travelers">/', $content, $tableMatches);
        $this->assertCount($expectedTables, $tableMatches[0]);

        preg_match_all('/<td\b[^>]*class="[^"]*\bnum\b[^"]*"[^>]*>\s*(\d+)\s*<\/td>/u', $content, $numberMatches);
        $this->assertSame(range(1, $travelerCount), array_map('intval', $numberMatches[1]));

        foreach (range(1, $travelerCount) as $number) {
            $response->assertSee('معتمر '.$number);
        }
    }

    public static function travelerDistributionCases(): array
    {
        return [
            'five travelers use one column' => [5, 1],
            'ten travelers use one column' => [10, 1],
            'eleven travelers use two columns' => [11, 2],
            'twenty five travelers use three columns' => [25, 3],
        ];
    }

    public function test_admin_login_page_is_available(): void
    {
        $this->get('/admin/login')->assertOk();
    }

    public function test_guest_is_redirected_to_login(): void
    {
        $this->get(route('bookings.receipt', 1))->assertRedirect(route('login'));
        $this->get(route('reports.booking-list.print'))->assertRedirect(route('login'));
        $this->get(route('reports.trip-movements.print'))->assertRedirect(route('login'));
        $this->get(route('reports.hotel-rooms.print'))->assertRedirect(route('login'));
    }
}
