<?php

namespace Tests\Feature;

use App\Actions\Bookings\CreateBookingAction;
use App\Actions\Bookings\UpdateBookingAction;
use App\Enums\StayScope;
use App\Models\Airplane;
use App\Models\Booking;
use App\Models\BookingType;
use App\Models\Broker;
use App\Models\City;
use App\Models\Company;
use App\Models\Customer;
use App\Models\Hotel;
use App\Models\Meal;
use App\Models\RoomType;
use App\Models\Transport;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class TransportAndBookingTransfersTest extends TestCase
{
    use RefreshDatabase;

    public function test_transport_resource_is_available_to_managers_and_forbidden_to_branch_employees(): void
    {
        $manager = User::factory()->create(['role' => User::ROLE_MAIN_ADMIN]);
        $broker = Broker::query()->create(['name' => 'فرع الاختبار']);
        $transport = Transport::factory()->create();
        $employee = User::factory()->create([
            'role' => User::ROLE_BRANCH_EMPLOYEE,
            'broker_id' => $broker->getKey(),
        ]);

        $this->actingAs($manager)
            ->get('/admin/transports')
            ->assertOk();
        $this->get('/admin/transports/create')->assertOk();
        $this->get('/admin/transports/'.$transport->getKey().'/edit')->assertOk();
        $this->assertTrue($manager->can('viewAny', Transport::class));
        $this->assertTrue($manager->can('create', Transport::class));
        $this->assertTrue($manager->can('update', $transport));
        $this->assertTrue($manager->can('delete', $transport));

        $this->actingAs($employee)
            ->get('/admin/transports')
            ->assertForbidden();
        $this->get('/admin/transports/create')->assertForbidden();
        $this->get('/admin/transports/'.$transport->getKey().'/edit')->assertForbidden();
        $this->assertFalse($employee->can('viewAny', Transport::class));
        $this->assertFalse($employee->can('create', Transport::class));
        $this->assertFalse($employee->can('update', $transport));
    }

    public function test_create_booking_calculates_transport_amount_and_net_amount_on_the_server(): void
    {
        $data = $this->bookingData();
        $data['rooms'][0]['price'] = 100;
        $data['service_amount'] = 25;
        $data['discount'] = 10;
        $data['transport_amount'] = 9999;
        $firstTransport = Transport::factory()->create(['name' => 'حافلة']);
        $secondTransport = Transport::factory()->create(['name' => 'قطار']);
        $data['transfers'] = [
            [
                'transport_id' => $firstTransport->getKey(),
                'from_city_id' => 2,
                'to_city_id' => 1,
                'travel_date' => '2026-11-03',
                'travel_time' => '09:30',
                'price' => 35,
            ],
            [
                'transport_id' => $secondTransport->getKey(),
                'from_city_id' => 1,
                'to_city_id' => 2,
                'travel_date' => '2026-11-06',
                'travel_time' => null,
                'price' => 45,
            ],
        ];

        $booking = app(CreateBookingAction::class)->handle($data);

        $this->assertSame('80.00', (string) $booking->transport_amount);
        $this->assertSame('200.00', (string) $booking->total_amount);
        $this->assertSame('295.00', (string) $booking->net_amount);
        $this->assertCount(2, $booking->transfers);
    }

    public function test_update_booking_replaces_its_transfers(): void
    {
        $data = $this->bookingData();
        $firstTransport = Transport::factory()->create(['name' => 'حافلة']);
        $secondTransport = Transport::factory()->create(['name' => 'قطار']);
        $data['transfers'] = [$this->transferData($firstTransport, 2, 1, 20)];
        $booking = app(CreateBookingAction::class)->handle($data);

        $data['transfers'] = [
            $this->transferData($secondTransport, 1, 2, 30),
            $this->transferData($firstTransport, 2, 1, 40),
        ];

        $updated = app(UpdateBookingAction::class)->handle($booking, $data);

        $this->assertCount(2, $updated->transfers);
        $this->assertSame('70.00', (string) $updated->transport_amount);
        $this->assertSame(
            [$firstTransport->getKey(), $secondTransport->getKey()],
            $updated->transfers->pluck('transport_id')->sort()->values()->all(),
        );
    }

    public function test_transfer_cannot_start_and_end_in_the_same_city(): void
    {
        $data = $this->bookingData();
        $transport = Transport::factory()->create();
        $data['transfers'] = [$this->transferData($transport, 1, 1, 20)];

        try {
            app(CreateBookingAction::class)->handle($data);
            $this->fail('A transfer with identical cities should be rejected.');
        } catch (ValidationException $exception) {
            $this->assertSame(
                'مدينة الوصول يجب أن تختلف عن مدينة المغادرة.',
                $exception->errors()['transfers.0.to_city_id'][0],
            );
        }
    }

    public function test_receipt_shows_transfers_and_their_price_and_hides_empty_transfer_section(): void
    {
        $user = User::factory()->create(['role' => User::ROLE_MAIN_ADMIN]);
        $data = $this->bookingData();
        $transport = Transport::factory()->create(['name' => 'حافلة سياحية']);
        $data['transfers'] = [$this->transferData($transport, 2, 1, 120)];
        $bookingWithTransfer = app(CreateBookingAction::class)->handle($data);
        $bookingWithoutTransfer = app(CreateBookingAction::class)->handle($this->bookingData());

        $response = $this->actingAs($user)->get(route('bookings.receipt', $bookingWithTransfer));
        $response->assertOk()
            ->assertSee('<h2>المواصلات</h2>', false)
            ->assertSee('حافلة سياحية')
            ->assertSee('المدينة المنورة → مكة')
            ->assertSee('120,00');

        $this->get(route('bookings.receipt', $bookingWithoutTransfer))
            ->assertOk()
            ->assertDontSee('<h2>المواصلات</h2>', false);
    }

    public function test_bookings_without_transfers_keep_the_existing_net_amount_calculation(): void
    {
        $data = $this->bookingData();
        $booking = Booking::query()->create([
            'customer_id' => $data['customer_id'],
            'booking_number' => 202610211,
            'stay_scope' => StayScope::Both,
            'total_amount' => 200,
            'service_amount' => 25,
            'discount' => 10,
            'net_amount' => 215,
        ]);
        $booking->refresh();

        $this->assertSame('0.00', (string) $booking->transport_amount);
        $this->assertSame('215.00', (string) $booking->net_amount);
        $this->assertCount(0, $booking->transfers);
    }

    /** @return array<string, mixed> */
    private function bookingData(): array
    {
        City::query()->upsert([
            ['id' => 1, 'name' => 'مكة'],
            ['id' => 2, 'name' => 'المدينة المنورة'],
        ], ['id'], ['name']);
        $customer = Customer::query()->create(['name_ar' => 'ولي الأمر']);
        $roomType = RoomType::query()->create(['name' => 'ثنائية', 'size' => 2, 'price' => 50, 'total_price' => 100]);
        $company = Company::query()->create(['name' => 'شركة']);
        $meal = Meal::query()->create(['name' => 'BB']);
        $bookingType = BookingType::query()->create(['name' => 'عام']);
        $broker = Broker::query()->create(['name' => 'وسيط']);
        $airplane = Airplane::query()->create(['name' => 'طائرة']);
        $madinaHotel = Hotel::query()->create(['city_id' => 2, 'name_ar' => 'فندق المدينة']);
        $makkaHotel = Hotel::query()->create(['city_id' => 1, 'name_ar' => 'فندق مكة']);

        return [
            'customer_id' => $customer->getKey(),
            'booking_type_id' => $bookingType->getKey(),
            'broker_id' => $broker->getKey(),
            'airplane_id' => $airplane->getKey(),
            'stay_scope' => StayScope::Both,
            'service_amount' => 0,
            'discount' => 0,
            'rooms' => [['room_type_id' => $roomType->getKey(), 'price' => 50]],
            'madina_hotel_id' => $madinaHotel->getKey(),
            'madina_company_id' => $company->getKey(),
            'madina_meal_id' => $meal->getKey(),
            'madina_check_in' => '2026-11-01',
            'madina_check_out' => '2026-11-03',
            'makka_hotel_id' => $makkaHotel->getKey(),
            'makka_company_id' => $company->getKey(),
            'makka_meal_id' => $meal->getKey(),
            'makka_check_in' => '2026-11-04',
            'makka_check_out' => '2026-11-06',
        ];
    }

    /** @return array<string, mixed> */
    private function transferData(Transport $transport, int $fromCityId, int $toCityId, int $price): array
    {
        return [
            'transport_id' => $transport->getKey(),
            'from_city_id' => $fromCityId,
            'to_city_id' => $toCityId,
            'travel_date' => '2026-11-03',
            'travel_time' => '09:30',
            'price' => $price,
        ];
    }
}
