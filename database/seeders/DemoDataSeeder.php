<?php

namespace Database\Seeders;

use App\Actions\Bookings\CreateBookingAction;
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
use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Collection;

class DemoDataSeeder extends Seeder
{
    public function run(): void
    {
        if (app()->environment('production')) {
            throw new \RuntimeException('لا يسمح بتشغيل البيانات التجريبية على الإنتاج.');
        }

        $this->seedReferenceDataIfEmpty();

        $airplanes = $this->createAirplanes();
        $transports = $this->createTransports();
        $brokers = $this->createBrokers();
        $companies = $this->createCompanies();
        $hotels = $this->createHotels();
        [$guardians, $customers] = $this->createCustomers();
        $bookingTypes = BookingType::query()->orderBy('id')->get();
        $meals = Meal::query()->orderBy('id')->get();
        $roomTypes = RoomType::query()->orderBy('id')->get();

        $bookingNumbers = $this->createBookings(
            $guardians,
            $bookingTypes,
            $brokers,
            $airplanes,
            $companies,
            $hotels,
            $meals,
            $roomTypes,
            $transports,
        );

        $bookings = Booking::query()->whereIn('booking_number', $bookingNumbers)->orderBy('id')->get();
        $bookings->take(2)->each->delete();

        $this->command?->info('تم إنشاء البيانات التجريبية بنجاح.');
        $createdBookings = Booking::withTrashed()->whereIn('booking_number', $bookingNumbers)->with('details.rooms')->get();
        $this->command?->info('عدد العملاء: '.Customer::query()->count());
        $this->command?->info('عدد الحجوزات المنشأة: '.$createdBookings->count());
        $this->command?->info('عدد الغرف: '.$createdBookings->flatMap(fn (Booking $booking) => $booking->details)->flatMap(fn ($detail) => $detail->rooms)->count());
        $this->command?->info('أرقام الحجوزات: '.implode('، ', $bookingNumbers));
    }

    private function seedReferenceDataIfEmpty(): void
    {
        if (City::query()->count() === 0) {
            $this->call(CitySeeder::class);
        }

        if (Meal::query()->count() === 0) {
            $this->call(MealSeeder::class);
        }

        if (BookingType::query()->count() === 0) {
            $this->call(BookingTypeSeeder::class);
        }

        if (RoomType::query()->count() === 0) {
            $this->call(RoomTypeSeeder::class);
        }
    }

    /** @return Collection<int, Airplane> */
    private function createAirplanes()
    {
        return collect(['الخطوط الأفريقية', 'البراق', 'ليبيا الجوية', 'الخطوط التركية'])
            ->map(fn (string $name): Airplane => Airplane::query()->firstOrCreate(['name' => $name]));
    }

    /** @return Collection<int, Transport> */
    private function createTransports(): Collection
    {
        return collect(['حافلة سياحية', 'قطار الحرمين', 'سيارة خاصة'])
            ->map(fn (string $name): Transport => Transport::query()->firstOrCreate(['name' => $name]));
    }

    /** @return Collection<int, Broker> */
    private function createBrokers()
    {
        $items = [
            ['name' => 'مكتب النور للسفر', 'contact_name' => 'عبدالسلام الورفلي', 'contact_phone' => '091-3214567'],
            ['name' => 'مكتب الرحاب', 'contact_name' => 'سالم القذافي', 'contact_phone' => '092-6543210'],
            ['name' => 'مكتب البشائر', 'contact_name' => 'مصطفى الزنتاني', 'contact_phone' => '094-7788990'],
        ];

        return collect($items)->map(fn (array $item): Broker => Broker::query()->firstOrCreate(
            ['name' => $item['name']],
            $item,
        ));
    }

    /** @return Collection<int, Company> */
    private function createCompanies()
    {
        $items = ['شركة الأفق للسياحة', 'شركة دروب الحرمين', 'شركة قافلة الخير'];

        return collect($items)->map(fn (string $name): Company => Company::query()->firstOrCreate(['name' => $name]));
    }

    /** @return array{0: Collection<int, Hotel>, 1: Collection<int, Hotel>} */
    private function createHotels(): array
    {
        $makka = [
            ['name_ar' => 'فندق أنوار مكة', 'name_en' => 'Anwar Makkah Hotel', 'rooms_count' => 180],
            ['name_ar' => 'فندق ضيافة الحرم', 'name_en' => 'Diyafa Al Haram Hotel', 'rooms_count' => 240],
            ['name_ar' => 'فندق الصفوة', 'name_en' => 'Al Safwa Hotel', 'rooms_count' => 320],
        ];
        $madina = [
            ['name_ar' => 'فندق طيبة المدينة', 'name_en' => 'Taybah Madinah Hotel', 'rooms_count' => 160],
            ['name_ar' => 'فندق دار الهجرة', 'name_en' => 'Dar Al Hijra Hotel', 'rooms_count' => 220],
            ['name_ar' => 'فندق روضة المسجد', 'name_en' => 'Rawdah Mosque Hotel', 'rooms_count' => 280],
        ];

        return [
            collect($makka)->map(fn (array $item): Hotel => Hotel::query()->firstOrCreate(
                ['name_ar' => $item['name_ar'], 'city_id' => 1],
                $item + ['city_id' => 1],
            )),
            collect($madina)->map(fn (array $item): Hotel => Hotel::query()->firstOrCreate(
                ['name_ar' => $item['name_ar'], 'city_id' => 2],
                $item + ['city_id' => 2],
            )),
        ];
    }

    /** @return array{0: \Illuminate\Database\Eloquent\Collection<int, Customer>, 1: \Illuminate\Database\Eloquent\Collection<int, Customer>} */
    private function createCustomers(): array
    {
        $guardianNames = ['محمد سالم الترهوني', 'عبدالرحمن مفتاح الورفلي', 'خالد علي الزليطني', 'عمر فرج المقريف', 'سعيد نوري بن غشير'];
        $guardians = collect($guardianNames)->map(fn (string $name, int $index): Customer => Customer::query()->firstOrCreate(
            ['name_ar' => $name],
            ['name_en' => null, 'phone' => '091-'.(2200000 + ($index * 111111))],
        ));

        $dependents = [
            ['أحمد محمد الترهوني', 0], ['ليان محمد الترهوني', 0],
            ['مفتاح عبدالرحمن الورفلي', 1], ['سارة عبدالرحمن الورفلي', 1], ['ياسين عبدالرحمن الورفلي', 1],
            ['علي خالد الزليطني', 2], ['مريم خالد الزليطني', 2],
            ['فرج عمر المقريف', 3],
            ['نور سعيد بن غشير', 4], ['ريم سعيد بن غشير', 4],
        ];

        foreach ($dependents as $index => [$name, $guardianIndex]) {
            Customer::query()->firstOrCreate(
                ['name_ar' => $name, 'guardian_id' => $guardians[$guardianIndex]->id],
                ['phone' => '092-'.(3300000 + ($index * 77777))],
            );
        }

        $independent = collect(['ياسر عبدالله طرابلسي', 'آمنة سالم مصراتية', 'بشير فوزي الزاوي'])->map(
            fn (string $name, int $index): Customer => Customer::query()->firstOrCreate(
                ['name_ar' => $name, 'guardian_id' => null],
                ['phone' => '094-'.(4400000 + ($index * 88888))],
            ),
        );

        return [$guardians, $guardians->concat($independent)];
    }

    private function createBookings($guardians, $bookingTypes, $brokers, $airplanes, $companies, $hotels, $meals, $roomTypes, $transports): array
    {
        $scopes = [
            StayScope::Both, StayScope::Both, StayScope::Both, StayScope::Both, StayScope::Both, StayScope::Both,
            StayScope::MadinaOnly, StayScope::MadinaOnly, StayScope::MadinaOnly,
            StayScope::MakkaOnly, StayScope::MakkaOnly, StayScope::MakkaOnly,
        ];
        $offsets = [-45, -30, -12, 5, 12, 19, 26, 33, 40, 47, 54, 58];
        $durations = [5, 6, 7, 8, 9, 10, 11, 12, 4, 6, 8, 10];
        $numbers = [];

        foreach ($scopes as $index => $scope) {
            $start = now()->addDays($offsets[$index])->startOfDay();
            $duration = $durations[$index];
            $data = [
                'customer_id' => $guardians[$index % $guardians->count()]->id,
                'booking_type_id' => $bookingTypes[$index % $bookingTypes->count()]->id,
                'broker_id' => $brokers[$index % $brokers->count()]->id,
                'airplane_id' => $airplanes[$index % $airplanes->count()]->id,
                'stay_scope' => $scope,
                'service_amount' => $index % 3 === 0 ? 75 : 0,
                'services_note' => $index % 3 === 0 ? 'خدمة نقل واستقبال' : null,
                'discount' => $index % 4 === 0 ? 50 : 0,
                'notes' => $index % 2 === 0 ? 'يرجى مراجعة المستندات قبل السفر.' : null,
                'residents' => [],
                'rooms' => $this->roomsForBooking($index, $roomTypes),
            ];

            if ($scope === StayScope::Both && in_array($index, [0, 3, 5], true)) {
                $data['transfers'] = [[
                    'transport_id' => $transports[$index % $transports->count()]->id,
                    'from_city_id' => 2,
                    'to_city_id' => 1,
                    'travel_date' => $start->copy()->addDays(intdiv($duration, 2))->format('Y-m-d'),
                    'price' => 120 + ($index * 10),
                ]];
            }

            if ($scope === StayScope::Both && $index === 3) {
                $data['transfers'][] = [
                    'transport_id' => $transports[($index + 1) % $transports->count()]->id,
                    'from_city_id' => 1,
                    'to_city_id' => 2,
                    'travel_date' => $start->copy()->addDays($duration)->format('Y-m-d'),
                    'price' => 140,
                ];
            }

            $details = $this->detailsForBooking($scope, $start, $duration, $hotels, $companies, $meals, $index);
            $booking = app(CreateBookingAction::class)->handle([...$data, ...$details]);
            $numbers[] = $booking->booking_number;
        }

        return $numbers;
    }

    private function roomsForBooking(int $index, $roomTypes): array
    {
        $count = ($index % 4) + 1;

        return collect(range(0, $count - 1))->map(fn (int $roomIndex): array => [
            'room_type_id' => $roomTypes[($index + $roomIndex) % $roomTypes->count()]->id,
            'price' => 180 + (($index + $roomIndex) * 25),
        ])->all();
    }

    private function detailsForBooking(StayScope $scope, Carbon $start, int $duration, array $hotels, $companies, $meals, int $index): array
    {
        $details = [];
        if ($scope === StayScope::Both || $scope === StayScope::MadinaOnly) {
            $madinaNights = $scope === StayScope::Both ? intdiv($duration, 2) : $duration;
            $details['madina_hotel_id'] = $hotels[1][$index % $hotels[1]->count()]->id;
            $details['madina_company_id'] = $companies[$index % $companies->count()]->id;
            $details['madina_meal_id'] = $meals[$index % $meals->count()]->id;
            $details['madina_check_in'] = $start->format('Y-m-d');
            $details['madina_check_out'] = $start->copy()->addDays($madinaNights)->format('Y-m-d');
        }

        if ($scope === StayScope::Both || $scope === StayScope::MakkaOnly) {
            $madinaNights = $scope === StayScope::Both ? intdiv($duration, 2) : 0;
            $makkaNights = $scope === StayScope::Both ? $duration - $madinaNights : $duration;
            $makkaStart = $start->copy()->addDays($madinaNights);
            $details['makka_hotel_id'] = $hotels[0][$index % $hotels[0]->count()]->id;
            $details['makka_company_id'] = $companies[($index + 1) % $companies->count()]->id;
            $details['makka_meal_id'] = $meals[($index + 1) % $meals->count()]->id;
            $details['makka_check_in'] = $makkaStart->format('Y-m-d');
            $details['makka_check_out'] = $makkaStart->copy()->addDays($makkaNights)->format('Y-m-d');
        }

        return $details;
    }
}
