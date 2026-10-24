<?php

namespace Tests\Feature;

use App\Filament\Resources\Bookings\Pages\CreateBooking;
use App\Models\City;
use App\Models\Company;
use App\Models\Customer;
use App\Models\Hotel;
use App\Models\Meal;
use App\Models\RoomType;
use App\Models\User;
use Closure;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Livewire\Livewire;
use Tests\TestCase;

class BookingFormPerformanceTest extends TestCase
{
    use RefreshDatabase;

    public function test_booking_form_reports_query_counts_and_request_times(): void
    {
        $fixtures = $this->createPerformanceDataset();
        $this->actingAs(User::factory()->create());

        $connection = DB::connection();
        $connection->enableQueryLog();

        $measurements = [];
        $measurements[] = $this->measureInteraction('فتح الصفحة', fn () => $this->get(route('filament.admin.resources.bookings.create'))->assertOk());

        $component = Livewire::test(CreateBooking::class)->assertOk();
        $component->fillForm([
            'rooms' => array_fill(0, 6, [
                'room_type_id' => $fixtures['room_type_ids'][0],
                'price' => 50,
            ]),
        ]);
        $rooms = $component->get('data.rooms');
        $this->assertCount(6, $rooms);
        $roomKey = array_key_first($rooms);

        $measurements[] = $this->measureInteraction('اختيار ولي الأمر', fn () => $component->set('data.customer_id', $fixtures['guardian_id']));
        $measurements[] = $this->measureInteraction('تغيير نوع غرفة', fn () => $component->set("data.rooms.{$roomKey}.room_type_id", $fixtures['room_type_ids'][1]));
        $measurements[] = $this->measureInteraction('تغيير سعر غرفة', fn () => $component->set("data.rooms.{$roomKey}.price", 75));
        $measurements[] = $this->measureInteraction('تغيير تاريخ الدخول', fn () => $component->set('data.makka_check_in', '2026-11-01'));
        $measurements[] = $this->measureInteraction('تغيير التخفيض', fn () => $component->set('data.discount', 25));

        $connection->disableQueryLog();

        $this->printMeasurements($measurements);
    }

    /**
     * @return array{guardian_id: int, room_type_ids: list<int>}
     */
    private function createPerformanceDataset(): array
    {
        City::query()->insert([
            ['id' => 1, 'name' => 'مكة'],
            ['id' => 2, 'name' => 'المدينة المنورة'],
        ]);

        $now = now()->toDateTimeString();
        $guardians = [];

        for ($number = 1; $number <= 200; $number++) {
            $guardians[] = [
                'name_ar' => sprintf('ولي أمر %03d', $number),
                'created_at' => $now,
                'updated_at' => $now,
            ];
        }

        Customer::query()->insert($guardians);

        $guardian = Customer::query()->where('name_ar', 'ولي أمر 001')->firstOrFail();
        Customer::create([
            'guardian_id' => $guardian->getKey(),
            'name_ar' => 'تابع ولي الأمر 001',
        ]);

        foreach (range(1, 20) as $number) {
            Hotel::create([
                'city_id' => $number <= 10 ? 1 : 2,
                'name_ar' => sprintf('فندق %02d', $number),
            ]);
        }

        Company::create(['name' => 'شركة القياس']);
        Meal::create(['name' => 'وجبة القياس']);

        foreach (range(1, 10) as $number) {
            RoomType::create([
                'name' => sprintf('غرفة %02d', $number),
                'size' => 2 + ($number % 4),
                'price' => 50 + $number,
            ]);
        }

        return [
            'guardian_id' => (int) $guardian->getKey(),
            'room_type_ids' => RoomType::query()->orderBy('id')->pluck('id')->map(fn ($id): int => (int) $id)->all(),
        ];
    }

    /**
     * @return array{label: string, query_count: int, duration_ms: float, top_queries: list<array{count: int, sql: string}>}
     */
    private function measureInteraction(string $label, Closure $interaction): array
    {
        $connection = DB::connection();
        $connection->flushQueryLog();
        $startedAt = hrtime(true);

        $interaction();

        $duration = (hrtime(true) - $startedAt) / 1_000_000;
        $queries = $connection->getQueryLog();
        $topQueries = collect($queries)
            ->groupBy(fn (array $query): string => $this->normalizeSql($query['query']))
            ->map(fn ($group, string $sql): array => ['count' => $group->count(), 'sql' => $sql])
            ->sortByDesc('count')
            ->take(5)
            ->values()
            ->all();

        return [
            'label' => $label,
            'query_count' => count($queries),
            'duration_ms' => round($duration, 2),
            'top_queries' => $topQueries,
        ];
    }

    private function normalizeSql(string $sql): string
    {
        return mb_strtolower((string) preg_replace('/\s+/', ' ', trim($sql)));
    }

    /**
     * @param  list<array{label: string, query_count: int, duration_ms: float, top_queries: list<array{count: int, sql: string}>}>  $measurements
     */
    private function printMeasurements(array $measurements): void
    {
        $lines = [
            '| التفاعل | عدد الاستعلامات | الزمن (ms) | أعلى 5 استعلامات تكرارًا |',
            '|---|---:|---:|---|',
        ];

        foreach ($measurements as $measurement) {
            $topQueries = collect($measurement['top_queries'])
                ->map(fn (array $query): string => $query['count'].'× '.str_replace('|', '\\|', $query['sql']))
                ->implode('<br>');

            $lines[] = '| '.$measurement['label'].' | '.$measurement['query_count'].' | '.$measurement['duration_ms'].' | '.$topQueries.' |';
        }

        fwrite(STDOUT, implode(PHP_EOL, $lines).PHP_EOL);
    }
}
