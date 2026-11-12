<?php

namespace Tests\Feature\Filament\Resources\Bookings\Tables;

use App\Enums\VisaStatus;
use App\Filament\Resources\Bookings\Pages\ListBookings;
use App\Models\Booking;
use App\Models\BookingType;
use App\Models\Broker;
use App\Models\Customer;
use App\Models\RoomResident;
use App\Models\User;
use App\Models\Visa;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Livewire\Livewire;
use Tests\TestCase;

class BookingsTableTest extends TestCase
{
    use RefreshDatabase;

    private const TOTAL_BOOKINGS = 1017;

    public function test_page_sizes_render_requested_bookings_without_gaps_or_duplicates(): void
    {
        Filament::setCurrentPanel('admin');
        $this->actingAs(User::factory()->create());

        $this->createBookings(self::TOTAL_BOOKINGS);
        $expectedIds = Booking::query()->orderByDesc('id')->pluck('id')->map(fn ($id): int => (int) $id)->all();
        $component = Livewire::test(ListBookings::class)->assertOk();
        $component->assertCountTableRecords(self::TOTAL_BOOKINGS);
        $this->assertSame([10, 25, 50, 100, 250], $component->instance()->getTable()->getPaginationPageOptions());
        $this->assertSame(250, $component->instance()->getTable()->getDefaultPaginationPageOption());

        $connection = DB::connection();
        $connection->enableQueryLog();
        $measurements = [];

        foreach ([10, 25, 50, 100, 250, 500, 1000] as $pageSize) {
            $connection->flushQueryLog();
            memory_reset_peak_usage();
            $cpuStartedAt = getrusage();
            $startedAt = hrtime(true);

            $component->set('tableRecordsPerPage', $pageSize);

            $durationMilliseconds = (hrtime(true) - $startedAt) / 1_000_000;
            $queries = $connection->getQueryLog();
            $records = $component->instance()->getTableRecords()->items();
            $recordIds = array_map(fn (Booking $record): int => (int) $record->getKey(), $records);
            $html = $component->html();
            $htmlBytes = strlen($html);
            $compressedHtmlBytes = strlen(gzencode($html));
            $cpuMilliseconds = $this->getCpuMilliseconds($cpuStartedAt, getrusage());

            $this->assertSame(
                array_slice($expectedIds, 0, min($pageSize, self::TOTAL_BOOKINGS)),
                $recordIds,
                "The {$pageSize}-record page should return the correct booking IDs in order.",
            );
            $this->assertGreaterThan(0, $htmlBytes);

            if ($pageSize === 10) {
                $this->assertArrayNotHasKey('notes', $records[0]->getAttributes());
                $this->assertArrayNotHasKey('services_note', $records[0]->getAttributes());
            }

            $measurements[] = [
                'page_size' => $pageSize,
                'record_count' => count($recordIds),
                'query_count' => count($queries),
                'query_time_ms' => round(array_sum(array_column($queries, 'time')), 2),
                'render_time_ms' => round($durationMilliseconds, 2),
                'cpu_time_ms' => round($cpuMilliseconds, 2),
                'html_bytes' => $htmlBytes,
                'gzip_html_bytes' => $compressedHtmlBytes,
                'peak_memory_kb' => (int) round(memory_get_peak_usage(true) / 1024),
            ];
        }

        $queryCounts = array_column($measurements, 'query_count');
        $this->assertCount(1, array_unique($queryCounts), 'The query count should not grow with the page size.');
        $connection->disableQueryLog();

        foreach ([10, 25, 50, 100, 250, 500, 1000] as $pageSize) {
            $component->set('tableRecordsPerPage', $pageSize);
            $pageName = $component->instance()->getTablePaginationPageName();
            $pageCount = (int) ceil(self::TOTAL_BOOKINGS / $pageSize);
            $seenIds = [];

            for ($page = 1; $page <= $pageCount; $page++) {
                if ($page > 1) {
                    $component->call('gotoPage', $page, $pageName);
                }

                $pageRecords = $component->instance()->getTableRecords()->items();
                $seenIds = array_merge($seenIds, array_map(fn (Booking $record): int => (int) $record->getKey(), $pageRecords));
            }

            $this->assertSame(
                $expectedIds,
                $seenIds,
                "Paginating {$pageSize} records should return every booking exactly once.",
            );
        }

        Filament::setCurrentPanel(null);

        fwrite(STDOUT, "\n| Per page | Rows | Queries | Query time (ms) | Render time (ms) | CPU time (ms) | HTML bytes | Gzip estimate (bytes) | Peak memory (KB) |\n");
        fwrite(STDOUT, "|---:|---:|---:|---:|---:|---:|---:|---:|---:|\n");

        foreach ($measurements as $measurement) {
            fwrite(STDOUT, '| '.$measurement['page_size'].' | '.$measurement['record_count'].' | '.$measurement['query_count'].' | '.$measurement['query_time_ms'].' | '.$measurement['render_time_ms'].' | '.$measurement['cpu_time_ms'].' | '.$measurement['html_bytes'].' | '.$measurement['gzip_html_bytes'].' | '.$measurement['peak_memory_kb']." |\n");
        }
    }

    public function test_search_filter_sort_and_relation_columns_return_the_expected_bookings(): void
    {
        Filament::setCurrentPanel('admin');
        $this->actingAs(User::factory()->create());

        $broker = Broker::query()->create(['name' => 'فرع جدول الاختبار']);
        $firstType = BookingType::query()->create(['name' => 'نوع أول']);
        $secondType = BookingType::query()->create(['name' => 'نوع ثان']);
        $guardian = Customer::factory()->create(['name_ar' => 'ولي أمر الاختبار']);
        $companion = Customer::factory()->create(['name_ar' => 'مرافق الاختبار']);

        $firstBooking = Booking::factory()->create([
            'customer_id' => $guardian->getKey(),
            'broker_id' => $broker->getKey(),
            'booking_type_id' => $firstType->getKey(),
            'booking_number' => 202600101,
            'net_amount' => 300,
        ]);
        $secondBooking = Booking::factory()->create([
            'customer_id' => $companion->getKey(),
            'broker_id' => $broker->getKey(),
            'booking_type_id' => $secondType->getKey(),
            'booking_number' => 202600102,
            'net_amount' => 100,
        ]);
        $thirdBooking = Booking::factory()->create([
            'customer_id' => $guardian->getKey(),
            'broker_id' => $broker->getKey(),
            'booking_type_id' => $firstType->getKey(),
            'booking_number' => 202600103,
            'net_amount' => 200,
        ]);

        RoomResident::query()->create(['booking_id' => $firstBooking->getKey(), 'customer_id' => $guardian->getKey()]);
        RoomResident::query()->create(['booking_id' => $firstBooking->getKey(), 'customer_id' => $companion->getKey()]);
        Visa::query()->create([
            'booking_id' => $firstBooking->getKey(),
            'customer_id' => $guardian->getKey(),
            'status' => VisaStatus::Issued->value,
        ]);
        Visa::query()->create([
            'booking_id' => $firstBooking->getKey(),
            'customer_id' => $companion->getKey(),
            'status' => VisaStatus::Pending->value,
        ]);

        $component = Livewire::test(ListBookings::class)
            ->assertOk()
            ->assertCanSeeTableRecords([$firstBooking, $secondBooking, $thirdBooking])
            ->assertSee('فرع جدول الاختبار')
            ->assertSee('ولي أمر الاختبار')
            ->assertSee('2')
            ->assertSee('1/2');

        $firstRecord = collect($component->instance()->getTableRecords()->items())->firstWhere('id', $firstBooking->getKey());
        $this->assertSame(2, (int) $firstRecord->residents_count);
        $this->assertSame(2, (int) $firstRecord->visas_total_count);
        $this->assertSame(1, (int) $firstRecord->visas_issued_count);

        $component->searchTable('202600102')
            ->assertCanSeeTableRecords([$secondBooking])
            ->assertCanNotSeeTableRecords([$firstBooking, $thirdBooking]);

        $component->searchTable('ولي أمر الاختبار')
            ->assertCanSeeTableRecords([$firstBooking, $thirdBooking])
            ->assertCanNotSeeTableRecords([$secondBooking]);

        $component->searchTable(null)
            ->filterTable('booking_type_id', $firstType->getKey())
            ->assertCanSeeTableRecords([$firstBooking, $thirdBooking])
            ->assertCanNotSeeTableRecords([$secondBooking]);

        $component->removeTableFilters()
            ->sortTable('net_amount', 'asc')
            ->assertCanSeeTableRecords([$secondBooking, $thirdBooking, $firstBooking], inOrder: true);
    }

    public function test_print_action_uses_only_the_current_table_page(): void
    {
        Filament::setCurrentPanel('admin');
        $this->actingAs(User::factory()->create());

        Booking::factory()->count(12)->create();

        $component = Livewire::test(ListBookings::class)->assertOk();
        $component->set('tableRecordsPerPage', 10);
        $component->call('gotoPage', 2, $component->instance()->getTablePaginationPageName());
        $bookingIds = collect($component->instance()->getTableRecords()->items())
            ->map(fn (Booking $booking): int => (int) $booking->getKey())
            ->all();

        $component->assertActionHasUrl('print', route('reports.bookings-page.print', ['bookingIds' => $bookingIds]));
        $this->assertCount(2, $bookingIds);
    }

    private function createBookings(int $count): void
    {
        $customer = Customer::factory()->create(['name_ar' => 'ولي أمر قياس الأداء']);
        $broker = Broker::query()->create(['name' => 'فرع قياس الأداء']);
        $bookingType = BookingType::query()->create(['name' => 'حجز قياس الأداء']);
        $now = now()->toDateTimeString();
        $bookings = Booking::factory()
            ->count($count)
            ->recycle($customer)
            ->make();
        $rows = [];

        foreach ($bookings as $index => $booking) {
            $booking->forceFill([
                'broker_id' => $broker->getKey(),
                'booking_type_id' => $bookingType->getKey(),
                'booking_number' => 202600000 + $index + 1,
                'notes' => str_repeat('م', 500),
                'services_note' => str_repeat('خ', 120),
                'net_amount' => number_format($index, 2, '.', ''),
                'created_at' => $now,
                'updated_at' => $now,
            ]);

            $rows[] = $booking->getAttributes();
        }

        foreach (array_chunk($rows, 500) as $chunk) {
            Booking::query()->insert($chunk);
        }

    }

    /**
     * @param array{ru_utime.tv_sec: int, ru_utime.tv_usec: int, ru_stime.tv_sec: int, ru_stime.tv_usec: int} $startedAt
     * @param array{ru_utime.tv_sec: int, ru_utime.tv_usec: int, ru_stime.tv_sec: int, ru_stime.tv_usec: int} $finishedAt
     */
    private function getCpuMilliseconds(array $startedAt, array $finishedAt): float
    {
        $startedMicroseconds = (($startedAt['ru_utime.tv_sec'] + $startedAt['ru_stime.tv_sec']) * 1_000_000)
            + $startedAt['ru_utime.tv_usec']
            + $startedAt['ru_stime.tv_usec'];
        $finishedMicroseconds = (($finishedAt['ru_utime.tv_sec'] + $finishedAt['ru_stime.tv_sec']) * 1_000_000)
            + $finishedAt['ru_utime.tv_usec']
            + $finishedAt['ru_stime.tv_usec'];

        return ($finishedMicroseconds - $startedMicroseconds) / 1000;
    }
}
