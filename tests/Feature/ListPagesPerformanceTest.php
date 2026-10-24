<?php

namespace Tests\Feature;

use App\Filament\Pages\BookingsReport;
use App\Filament\Pages\Dashboard;
use App\Filament\Pages\HotelRoomsReport;
use App\Filament\Pages\TripMovementsReport;
use App\Filament\Resources\Bookings\Pages\ListBookings;
use App\Filament\Resources\Customers\Pages\ListCustomers;
use App\Filament\Widgets\BookingTrendChart;
use App\Filament\Widgets\BookingTypeChart;
use App\Filament\Widgets\DashboardQuickActions;
use App\Filament\Widgets\DashboardStats;
use App\Filament\Widgets\RecentBookingsTable;
use App\Models\Airplane;
use App\Models\BookingType;
use App\Models\Broker;
use App\Models\City;
use App\Models\Company;
use App\Models\Hotel;
use App\Models\Meal;
use App\Models\User;
use Filament\Facades\Filament;
use Illuminate\Database\Eloquent\Model as EloquentModel;
use Illuminate\Database\LazyLoadingViolationException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Livewire\Livewire;
use Tests\TestCase;

class ListPagesPerformanceTest extends TestCase
{
    use RefreshDatabase;

    public function test_lists_dashboard_and_reports_with_query_and_lazy_loading_measurements(): void
    {
        $this->actingAs(User::factory()->create());
        $this->createPerformanceData();

        $pages = [
            'قائمة الحجوزات' => ListBookings::class,
            'قائمة أولياء الأمور' => ListCustomers::class,
            'لوحة التحكم' => Dashboard::class,
            'تقرير قائمة الحجوزات' => BookingsReport::class,
            'تقرير حركة الرحلات' => TripMovementsReport::class,
            'تقرير الغرف المحجوزة' => HotelRoomsReport::class,
        ];
        $measurements = [];
        $dashboardWidgetMeasurements = [];

        Filament::setCurrentPanel('admin');
        EloquentModel::preventLazyLoading();
        DB::connection()->enableQueryLog();

        try {
            foreach ($pages as $label => $page) {
                $measurements[] = $this->measureComponent($label, $page);
            }

            $dashboardWidgets = [
                'الإجراءات السريعة' => DashboardQuickActions::class,
                'إحصاءات لوحة التحكم' => DashboardStats::class,
                'مخطط الحجوزات خلال 6 أشهر' => BookingTrendChart::class,
                'مخطط الحجوزات حسب النوع' => BookingTypeChart::class,
                'جدول أحدث الحجوزات' => RecentBookingsTable::class,
            ];

            foreach ($dashboardWidgets as $label => $widget) {
                $dashboardWidgetMeasurements[] = $this->measureComponent($label, $widget);
            }
        } finally {
            DB::connection()->disableQueryLog();
            EloquentModel::preventLazyLoading(false);
            Filament::setCurrentPanel(null);
        }

        $this->writeMeasurements($measurements);
        $this->writeMeasurements($dashboardWidgetMeasurements, 'Widgets لوحة التحكم - طلبات Livewire المؤجلة');

        $this->assertCount(count($pages), $measurements);
        $this->assertCount(5, $dashboardWidgetMeasurements);
    }

    /** @return array{page: string, query_count: int, render_ms: float, top_queries: array<int, array{count: int, sql: string}>, lazy_loading: string} */
    private function measureComponent(string $label, string $componentClass): array
    {
        DB::connection()->flushQueryLog();
        $startedAt = hrtime(true);
        $lazyLoadingException = null;

        try {
            Livewire::test($componentClass)->assertOk();
        } catch (LazyLoadingViolationException $exception) {
            $lazyLoadingException = $exception;
        }

        $elapsedMilliseconds = (hrtime(true) - $startedAt) / 1_000_000;
        $queries = DB::connection()->getQueryLog();

        return [
            'page' => $label,
            'query_count' => count($queries),
            'render_ms' => round($elapsedMilliseconds, 2),
            'top_queries' => $this->topRepeatedQueries($queries),
            'lazy_loading' => $lazyLoadingException
                ? $lazyLoadingException->relation.' ('.$lazyLoadingException->model.')'
                : '—',
        ];
    }

    /** @param array<int, array{query: string}> $queries
     * @return array<int, array{count: int, sql: string}>
     */
    private function topRepeatedQueries(array $queries): array
    {
        $grouped = [];

        foreach ($queries as $query) {
            $sql = preg_replace('/\s+/', ' ', trim($query['query'])) ?? $query['query'];
            $grouped[$sql] = ($grouped[$sql] ?? 0) + 1;
        }

        arsort($grouped);

        $topQueries = [];

        foreach (array_slice($grouped, 0, 5, true) as $sql => $count) {
            $topQueries[] = [
                'count' => $count,
                'sql' => $sql,
            ];
        }

        return $topQueries;
    }

    /** @param array<int, array{page: string, query_count: int, render_ms: float, top_queries: array<int, array{count: int, sql: string}>, lazy_loading: string}> $measurements */
    private function writeMeasurements(array $measurements, string $heading = 'الصفحات'): void
    {
        fwrite(STDOUT, "\n**{$heading}**\n\n");
        fwrite(STDOUT, "| الصفحة / المكوّن | عدد الاستعلامات | زمن التصيير (ms) | Lazy loading |\n");
        fwrite(STDOUT, "|---|---:|---:|---|\n");

        foreach ($measurements as $measurement) {
            fwrite(STDOUT, '| '.$measurement['page'].' | '.$measurement['query_count'].' | '.$measurement['render_ms'].' | '.$measurement['lazy_loading']." |\n");
        }

        fwrite(STDOUT, "\n| الصفحة | أعلى 5 استعلامات تكرارًا (العدد × SQL) |\n");
        fwrite(STDOUT, "|---|---|\n");

        foreach ($measurements as $measurement) {
            $queries = array_map(
                fn (array $query): string => $query['count'].'× `'.str_replace(['`', '|'], ["'", '\\|'], $query['sql']).'`',
                $measurement['top_queries'],
            );

            fwrite(STDOUT, '| '.$measurement['page'].' | '.implode('<br>', $queries)." |\n");
        }
    }

    private function createPerformanceData(): void
    {
        $now = now();
        $makkah = City::query()->create(['name' => 'مكة']);
        $madinah = City::query()->create(['name' => 'المدينة المنورة']);
        $broker = Broker::query()->create(['name' => 'فرع القياس']);
        $airplane = Airplane::query()->create(['name' => 'طائرة القياس']);
        $bookingType = BookingType::query()->create(['name' => 'حجز القياس']);
        $company = Company::query()->create(['name' => 'شركة القياس']);
        $meal = Meal::query()->create(['name' => 'وجبة القياس']);

        $hotelIds = [];

        for ($index = 1; $index <= 20; $index++) {
            $hotel = Hotel::query()->create([
                'city_id' => $index % 2 === 0 ? $madinah->id : $makkah->id,
                'name_ar' => 'فندق قياس '.$index,
                'rooms_count' => 20,
            ]);
            $hotelIds[] = $hotel->id;
        }

        $customerRows = [];

        for ($index = 1; $index <= 200; $index++) {
            $customerRows[] = [
                'guardian_id' => null,
                'name_ar' => 'ولي أمر قياس '.$index,
                'name_en' => 'Performance Guardian '.$index,
                'phone' => '050'.str_pad((string) $index, 7, '0', STR_PAD_LEFT),
                'created_at' => $now,
                'updated_at' => $now,
            ];
        }

        foreach (array_chunk($customerRows, 50) as $chunk) {
            DB::table('customers')->insert($chunk);
        }

        $customerIds = DB::table('customers')->orderBy('id')->pluck('id')->all();
        $bookingRows = [];

        for ($index = 1; $index <= 120; $index++) {
            $bookingRows[] = [
                'customer_id' => $customerIds[$index - 1],
                'broker_id' => $broker->id,
                'airplane_id' => $airplane->id,
                'booking_type_id' => $bookingType->id,
                'booking_number' => 202600000 + $index,
                'stay_scope' => 0,
                'created_by' => null,
                'updated_by' => null,
                'created_at' => $now->copy()->subDays($index % 180),
                'updated_at' => $now,
            ];
        }

        foreach (array_chunk($bookingRows, 50) as $chunk) {
            DB::table('bookings')->insert($chunk);
        }

        $bookingIds = DB::table('bookings')->orderBy('id')->pluck('id')->all();
        $residentRows = [];
        $bookingDetailRows = [];

        for ($index = 1; $index <= 120; $index++) {
            $bookingId = $bookingIds[$index - 1];
            $residentRows[] = [
                'booking_id' => $bookingId,
                'customer_id' => $customerIds[$index - 1],
                'created_at' => $now,
                'updated_at' => $now,
            ];
            $bookingDetailRows[] = [
                'booking_id' => $bookingId,
                'hotel_id' => $hotelIds[($index - 1) % count($hotelIds)],
                'company_id' => $company->id,
                'meal_id' => $meal->id,
                'city_id' => $index % 2 === 0 ? $madinah->id : $makkah->id,
                'check_in' => $now->copy()->addDays($index % 30)->toDateString(),
                'check_out' => $now->copy()->addDays(($index % 30) + 3)->toDateString(),
                'created_at' => $now,
                'updated_at' => $now,
            ];
        }

        foreach (array_chunk($residentRows, 50) as $chunk) {
            DB::table('room_residents')->insert($chunk);
        }

        foreach (array_chunk($bookingDetailRows, 50) as $chunk) {
            DB::table('booking_details')->insert($chunk);
        }
    }
}
