<?php

namespace Database\Seeders;

use App\Actions\Bookings\CreateBookingAction;
use App\Enums\StayScope;
use App\Models\Airplane;
use App\Models\Booking;
use App\Models\BookingType;
use App\Models\Broker;
use App\Models\Company;
use App\Models\Customer;
use App\Models\Hotel;
use App\Models\Meal;
use App\Models\RoomType;
use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Collection;

class BulkBookingSeeder extends Seeder
{
    public function run(): void
    {
        $this->call(DatabaseSeeder::class);

        $bookingTypes = BookingType::query()->orderBy('id')->get();
        $meals = Meal::query()->orderBy('id')->get();
        $roomTypes = RoomType::query()->orderBy('id')->get();
        $airplanes = Airplane::query()->get()->take(4);
        $brokers = Broker::query()->get()->take(3);
        $companies = Company::query()->get()->take(3);
        $customers = $this->customers();
        $hotels = $this->hotels();

        if ($bookingTypes->isEmpty() || $meals->isEmpty() || $roomTypes->isEmpty()
            || $airplanes->isEmpty() || $brokers->isEmpty() || $companies->isEmpty()) {
            throw new \RuntimeException('البيانات المرجعية مطلوبة قبل إنشاء الحجوزات.');
        }

        for ($index = 0; $index < 1000; $index++) {
            $scope = StayScope::cases()[$index % count(StayScope::cases())];
            $start = now()->addDays(($index % 120) - 60)->startOfDay();
            $duration = 4 + ($index % 9);

            app(CreateBookingAction::class)->handle([
                'customer_id' => $customers[$index % $customers->count()]->id,
                'booking_type_id' => $bookingTypes[$index % $bookingTypes->count()]->id,
                'broker_id' => $brokers[$index % $brokers->count()]->id,
                'airplane_id' => $airplanes[$index % $airplanes->count()]->id,
                'stay_scope' => $scope,
                'service_amount' => $index % 3 === 0 ? 75 : 0,
                'services_note' => $index % 3 === 0 ? 'خدمة نقل واستقبال' : null,
                'discount' => $index % 10 === 0 ? 50 : 0,
                'rooms' => [[
                    'room_type_id' => $roomTypes[$index % $roomTypes->count()]->id,
                    'price' => 180 + (($index % 8) * 25),
                ]],
                ...$this->details($scope, $start, $duration, $hotels, $companies, $meals, $index),
            ]);
        }

        $this->command?->info('تم إنشاء 1000 حجز جديد بنجاح.');
        $this->command?->info('إجمالي الحجوزات: '.Booking::query()->count());
    }

    /** @return Collection<int, Customer> */
    private function customers(): Collection
    {
        return collect(range(1, 50))->map(fn (int $index): Customer => Customer::query()->firstOrCreate(
            ['name_ar' => 'حاج تجريبي '.$index],
            ['phone' => '091-'.str_pad((string) $index, 7, '0', STR_PAD_LEFT)],
        ));
    }

    /** @return array{0: Collection, 1: Collection} */
    private function hotels(): array
    {
        $makka = Hotel::query()->where('city_id', 1)->get();
        $madina = Hotel::query()->where('city_id', 2)->get();

        if ($makka->isEmpty() || $madina->isEmpty()) {
            throw new \RuntimeException('بيانات فنادق مكة والمدينة مطلوبة قبل إنشاء الحجوزات.');
        }

        return [$makka, $madina];
    }

    /** @param array{0: Collection, 1: Collection} $hotels */
    private function details(StayScope $scope, Carbon $start, int $duration, array $hotels, Collection $companies, Collection $meals, int $index): array
    {
        $details = [];

        if ($scope === StayScope::Both || $scope === StayScope::MadinaOnly) {
            $nights = $scope === StayScope::Both ? intdiv($duration, 2) : $duration;
            $details += [
                'madina_hotel_id' => $hotels[1][$index % $hotels[1]->count()]->id,
                'madina_company_id' => $companies[$index % $companies->count()]->id,
                'madina_meal_id' => $meals[$index % $meals->count()]->id,
                'madina_check_in' => $start->format('Y-m-d'),
                'madina_check_out' => $start->copy()->addDays($nights)->format('Y-m-d'),
            ];
        }

        if ($scope === StayScope::Both || $scope === StayScope::MakkaOnly) {
            $madinaNights = $scope === StayScope::Both ? intdiv($duration, 2) : 0;
            $details += [
                'makka_hotel_id' => $hotels[0][$index % $hotels[0]->count()]->id,
                'makka_company_id' => $companies[($index + 1) % $companies->count()]->id,
                'makka_meal_id' => $meals[($index + 1) % $meals->count()]->id,
                'makka_check_in' => $start->copy()->addDays($madinaNights)->format('Y-m-d'),
                'makka_check_out' => $start->copy()->addDays($duration)->format('Y-m-d'),
            ];
        }

        return $details;
    }
}
