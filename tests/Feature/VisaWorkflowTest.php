<?php

namespace Tests\Feature;

use App\Actions\Bookings\CreateBookingAction;
use App\Actions\Bookings\UpdateBookingAction;
use App\Actions\Visas\UpdateVisaStatusAction;
use App\Enums\StayScope;
use App\Enums\VisaStatus;
use App\Filament\Resources\Bookings\BookingResource;
use App\Filament\Resources\Bookings\Pages\ListBookings;
use App\Filament\Resources\Bookings\RelationManagers\VisasRelationManager;
use App\Filament\Resources\Visas\Pages\ListVisas;
use App\Filament\Widgets\PendingVisas;
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
use App\Models\User;
use App\Models\Visa;
use Filament\Notifications\Notification;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Livewire\Livewire;
use Tests\TestCase;

class VisaWorkflowTest extends TestCase
{
    use RefreshDatabase;

    public function test_booking_creation_makes_a_pending_visa_for_each_resident_and_a_public_token(): void
    {
        $this->actingAs($this->manager());
        $booking = $this->makeBooking(dependentCount: 2);

        $this->assertSame(3, $booking->visas()->count());
        $this->assertSame(3, $booking->visas()->where('status', VisaStatus::Pending->value)->count());
        $this->assertSame(48, strlen($booking->public_token));
        $this->assertNotNull($booking->publicUrl());
        $this->assertStringContainsString('/visa-status/'.$booking->public_token, $booking->publicUrl());
    }

    public function test_public_visa_status_page_shows_each_resident_latest_visa_status(): void
    {
        $this->actingAs($this->manager());
        $booking = $this->makeBooking(dependentCount: 1);
        $residents = $booking->residents()->with('customer')->get();
        $residents[0]->customer->update(['name_ar' => 'ولي الأمر']);
        $residents[1]->customer->update(['name_ar' => 'المرافق']);

        app(UpdateVisaStatusAction::class)->handle($booking->visas()->where('customer_id', $residents[1]->customer_id)->firstOrFail(), VisaStatus::Rejected, rejectedReason: 'بيانات غير مكتملة');

        $response = $this->get(route('visas.public', ['token' => $booking->public_token]));

        $response->assertOk()
            ->assertSee('ولي الأمر')
            ->assertSee('المرافق')
            ->assertSee(VisaStatus::Pending->getLabel())
            ->assertSee(VisaStatus::Rejected->getLabel())
            ->assertSee('بيانات غير مكتملة');
    }

    public function test_issued_transition_requires_a_pdf_and_records_the_user_and_event(): void
    {
        Storage::fake('visas');
        config(['umrah.visa_max_kb' => 20]);
        $manager = $this->manager();
        $this->actingAs($manager);
        $visa = $this->makeBooking()->visas()->firstOrFail();
        $filePath = $this->storePdf($visa, "%PDF-1.7\nvisa contents\n");

        $updated = app(UpdateVisaStatusAction::class)->handle(
            $visa,
            VisaStatus::Issued,
            $filePath,
            visaNumber: 'LY-123456',
            originalName: 'passport-copy.pdf',
        );

        $this->assertSame(VisaStatus::Issued, $updated->status);
        $this->assertSame($manager->getKey(), $updated->issued_by);
        $this->assertSame('LY-123456', $updated->visa_number);
        $this->assertSame('passport-copy.pdf', $updated->original_name);
        $this->assertTrue($updated->isDownloadable());
        $this->assertDatabaseHas('visa_events', [
            'visa_id' => $visa->getKey(),
            'user_id' => $manager->getKey(),
            'from_status' => VisaStatus::Pending->value,
            'to_status' => VisaStatus::Issued->value,
        ]);
        $this->assertFalse(str_starts_with(Storage::disk('visas')->path($filePath), public_path()));
    }

    public function test_issued_status_without_a_stored_pdf_is_rejected(): void
    {
        $this->actingAs($this->manager());
        $visa = $this->makeBooking()->visas()->firstOrFail();

        $this->expectException(ValidationException::class);
        app(UpdateVisaStatusAction::class)->handle($visa, VisaStatus::Issued);
    }

    public function test_rejected_status_without_a_reason_is_rejected(): void
    {
        $this->actingAs($this->manager());
        $visa = $this->makeBooking()->visas()->firstOrFail();

        $this->expectException(ValidationException::class);
        app(UpdateVisaStatusAction::class)->handle($visa, VisaStatus::Rejected);
    }

    public function test_same_status_without_a_new_file_is_a_no_op(): void
    {
        $this->actingAs($this->manager());
        $visa = $this->makeBooking()->visas()->firstOrFail();

        $unchanged = app(UpdateVisaStatusAction::class)->handle($visa, VisaStatus::Pending, rejectedReason: 'ignored');

        $this->assertSame(VisaStatus::Pending, $unchanged->status);
        $this->assertNull($unchanged->rejected_reason);
        $this->assertSame(0, $unchanged->events()->count());
    }

    public function test_non_pdf_mime_or_signature_and_oversized_files_are_rejected(): void
    {
        Storage::fake('visas');
        config(['umrah.visa_max_kb' => 1]);
        $this->actingAs($this->manager());
        $visa = $this->makeBooking()->visas()->firstOrFail();

        foreach ([
            'plain text named pdf',
            "\x89PNG\r\n\x1a\nnot a pdf",
            "%PDF-1.7\n".str_repeat('x', 1100),
        ] as $contents) {
            $path = $this->storePdf($visa, $contents);

            try {
                app(UpdateVisaStatusAction::class)->handle($visa, VisaStatus::Issued, $path);
                $this->fail('An invalid PDF should not be accepted.');
            } catch (ValidationException) {
                $this->assertSame(VisaStatus::Pending, $visa->fresh()->status);
            }
        }
    }

    public function test_returning_to_pending_keeps_the_private_file_but_disables_downloadability(): void
    {
        Storage::fake('visas');
        $this->actingAs($this->manager());
        $visa = $this->makeBooking()->visas()->firstOrFail();
        $filePath = $this->storePdf($visa, "%PDF-1.7\nissued visa\n");
        $issued = app(UpdateVisaStatusAction::class)->handle($visa, VisaStatus::Issued, $filePath);

        $pending = app(UpdateVisaStatusAction::class)->handle($issued, VisaStatus::Pending);

        $this->assertSame(VisaStatus::Pending, $pending->status);
        $this->assertTrue(Storage::disk('visas')->exists($filePath));
        $this->assertFalse($pending->isDownloadable());
        $this->assertSame(2, $pending->events()->count());
        $this->get(route('visas.file', ['visa' => $pending]))->assertNotFound();
    }

    public function test_replacing_a_file_deletes_the_old_file_and_records_the_replacement(): void
    {
        Storage::fake('visas');
        $this->actingAs($this->manager());
        $visa = $this->makeBooking()->visas()->firstOrFail();
        $oldPath = $this->storePdf($visa, "%PDF-1.7\nfirst\n");
        $issued = app(UpdateVisaStatusAction::class)->handle($visa, VisaStatus::Issued, $oldPath);
        $newPath = $this->storePdf($issued, "%PDF-1.7\nreplacement\n");

        $updated = app(UpdateVisaStatusAction::class)->handle(
            $issued,
            VisaStatus::Issued,
            $newPath,
            originalName: 'replacement.pdf',
        );

        $this->assertFalse(Storage::disk('visas')->exists($oldPath));
        $this->assertTrue(Storage::disk('visas')->exists($newPath));
        $this->assertSame($newPath, $updated->file_path);
        $this->assertSame(2, $updated->events()->count());
    }

    public function test_branch_employee_cannot_update_or_download_a_visa_from_another_branch(): void
    {
        Storage::fake('visas');
        $manager = $this->manager();
        $branchA = Broker::create(['name' => 'فرع أ']);
        $branchB = Broker::create(['name' => 'فرع ب']);
        $this->actingAs($manager);
        $visaA = $this->makeBooking(broker: $branchA)->visas()->firstOrFail();
        $visaB = $this->makeBooking(broker: $branchB)->visas()->firstOrFail();
        $ownFilePath = $this->storePdf($visaA, "%PDF-1.7\nbranch a\n");
        app(UpdateVisaStatusAction::class)->handle($visaA, VisaStatus::Issued, $ownFilePath);
        $filePath = $this->storePdf($visaB, "%PDF-1.7\nbranch b\n");
        app(UpdateVisaStatusAction::class)->handle($visaB, VisaStatus::Issued, $filePath);

        $employee = User::factory()->create([
            'role' => User::ROLE_BRANCH_EMPLOYEE,
            'broker_id' => $branchA->getKey(),
            'is_active' => true,
        ]);
        $this->actingAs($employee);

        try {
            app(UpdateVisaStatusAction::class)->handle($visaB->fresh(), VisaStatus::Pending);
            $this->fail('A branch employee cannot update a visa from another branch.');
        } catch (AuthorizationException) {
            $this->assertSame(VisaStatus::Issued, $visaB->fresh()->status);
        }

        $this->get(route('visas.file', ['visa' => $visaB]))->assertNotFound();
        $this->get(route('visas.file', ['visa' => $visaA]))
            ->assertOk()
            ->assertHeader('Content-Type', 'application/pdf');
        $this->assertFalse($employee->can('delete', $visaA));
        $this->assertFalse($manager->can('delete', $visaA));
    }

    public function test_removed_pending_visa_is_soft_deleted_but_issued_visa_is_preserved_and_staff_is_notified(): void
    {
        Storage::fake('visas');
        $manager = $this->manager();
        $this->actingAs($manager);
        $booking = $this->makeBooking(dependentCount: 2);
        $residents = $booking->residents()->with('customer')->get();
        $pendingVisa = $booking->visas()->where('customer_id', $residents[1]->customer_id)->firstOrFail();
        $issuedVisa = $booking->visas()->where('customer_id', $residents[2]->customer_id)->firstOrFail();
        $filePath = $this->storePdf($issuedVisa, "%PDF-1.7\nissued\n");
        app(UpdateVisaStatusAction::class)->handle($issuedVisa, VisaStatus::Issued, $filePath);

        $residents[1]->customer->update(['guardian_id' => null]);
        $residents[2]->customer->update(['guardian_id' => null]);
        app(UpdateBookingAction::class)->handle($booking, $this->updatePayload($booking));

        $this->assertNotNull(Visa::withTrashed()->findOrFail($pendingVisa->getKey())->deleted_at);
        $this->assertNull(Visa::withTrashed()->findOrFail($issuedVisa->getKey())->deleted_at);
        Notification::assertNotified('تأشيرات محفوظة');
    }

    public function test_visa_worklist_and_dashboard_pending_count_are_scoped_to_the_branch(): void
    {
        $branchA = Broker::create(['name' => 'فرع أ']);
        $branchB = Broker::create(['name' => 'فرع ب']);
        $this->actingAs($this->manager());
        $bookingA = $this->makeBooking(broker: $branchA);
        $bookingB = $this->makeBooking(dependentCount: 2, broker: $branchB);
        Livewire::test(ListVisas::class)
            ->assertCanSeeTableRecords($bookingA->visas->merge($bookingB->visas));
        $employee = User::factory()->create([
            'role' => User::ROLE_BRANCH_EMPLOYEE,
            'broker_id' => $branchA->getKey(),
            'is_active' => true,
        ]);
        $this->actingAs($employee);

        Livewire::test(ListVisas::class)
            ->assertCanSeeTableRecords($bookingA->visas)
            ->assertCanNotSeeTableRecords($bookingB->visas);

        Livewire::test(PendingVisas::class)
            ->assertSee('تأشيرات قيد الاستخراج')
            ->assertSee('1');
    }

    public function test_visa_worklist_groups_bookings_and_keeps_full_counts_when_pending_filter_is_active(): void
    {
        Storage::fake('visas');
        $manager = $this->manager();
        $this->actingAs($manager);

        $bookingWithFour = $this->makeBooking(dependentCount: 3);
        $bookingWithTwo = $this->makeBooking(dependentCount: 1);
        $bookingWithOne = $this->makeBooking();

        $bookings = collect([
            [$bookingWithFour, 41001, 'ولي الأمر أربعة'],
            [$bookingWithTwo, 41002, 'ولي الأمر اثنان'],
            [$bookingWithOne, 41003, 'ولي الأمر واحد'],
        ]);

        foreach ($bookings as [$booking, $number, $guardianName]) {
            $booking->update(['booking_number' => $number]);
            $booking->customer->update(['name_ar' => $guardianName]);
        }

        $issuedVisa = $bookingWithFour->visas()->firstOrFail();
        $filePath = $this->storePdf($issuedVisa, "%PDF-1.7\ngrouped visa\n");
        app(UpdateVisaStatusAction::class)->handle($issuedVisa, VisaStatus::Issued, $filePath);

        $queryCount = 0;
        DB::listen(static function () use (&$queryCount): void {
            $queryCount++;
        });

        $component = Livewire::test(ListVisas::class);

        $component
            ->assertSee('حجز 41001 — ولي الأمر: ولي الأمر أربعة')
            ->assertSee('حجز 41002 — ولي الأمر: ولي الأمر اثنان')
            ->assertSee('حجز 41003 — ولي الأمر: ولي الأمر واحد')
            ->assertSee('1 من 4 مستخرجة')
            ->assertSee('ولي الأمر')
            ->assertSee('مرافق');

        $this->assertLessThan(20, $queryCount);
    }

    public function test_booking_table_shows_visa_progress_and_relation_manager_is_registered(): void
    {
        $manager = $this->manager();
        $this->actingAs($manager);
        $booking = $this->makeBooking(dependentCount: 1);

        Livewire::test(ListBookings::class)
            ->assertCanSeeTableRecords([$booking])
            ->assertSee('0/2');

        $this->assertContains(VisasRelationManager::class, BookingResource::getRelations());
        $this->get(BookingResource::getUrl('view', ['record' => $booking]))
            ->assertOk()
            ->assertSee('التأشيرات');
        $this->get(BookingResource::getUrl('edit', ['record' => $booking]))
            ->assertOk()
            ->assertSee('التأشيرات');
    }

    private function manager(): User
    {
        return User::factory()->create([
            'role' => User::ROLE_MAIN_ADMIN,
            'broker_id' => null,
            'is_active' => true,
        ]);
    }

    private function makeBooking(int $dependentCount = 0, ?Broker $broker = null): Booking
    {
        City::query()->upsert([
            ['id' => 1, 'name' => 'مكة'],
            ['id' => 2, 'name' => 'المدينة المنورة'],
        ], ['id'], ['name']);

        $customer = Customer::create(['name_ar' => fake()->name()]);
        for ($index = 1; $index <= $dependentCount; $index++) {
            Customer::create([
                'name_ar' => 'معتمر '.$index,
                'guardian_id' => $customer->getKey(),
            ]);
        }

        $roomType = RoomType::create(['name' => 'ثنائية', 'size' => 2, 'price' => 50, 'total_price' => 100]);
        $company = Company::create(['name' => 'شركة']);
        $meal = Meal::create(['name' => 'BB']);
        $bookingType = BookingType::create(['name' => 'عام']);
        $broker ??= Broker::create(['name' => 'الفرع الرئيسي']);
        $airplane = Airplane::create(['name' => 'طائرة']);
        $hotel = Hotel::create(['city_id' => 1, 'name_ar' => 'فندق مكة']);

        return app(CreateBookingAction::class)->handle([
            'customer_id' => $customer->getKey(),
            'broker_id' => $broker->getKey(),
            'booking_type_id' => $bookingType->getKey(),
            'airplane_id' => $airplane->getKey(),
            'stay_scope' => StayScope::MakkaOnly,
            'service_amount' => 0,
            'discount' => 0,
            'rooms' => [['room_type_id' => $roomType->getKey(), 'price' => 50]],
            'makka_hotel_id' => $hotel->getKey(),
            'makka_company_id' => $company->getKey(),
            'makka_meal_id' => $meal->getKey(),
            'makka_check_in' => '2026-11-05',
            'makka_check_out' => '2026-11-08',
        ]);
    }

    private function updatePayload(Booking $booking): array
    {
        $booking->load(['details.rooms']);
        $detail = $booking->details->firstOrFail();

        return [
            'customer_id' => $booking->customer_id,
            'broker_id' => $booking->broker_id,
            'booking_type_id' => $booking->booking_type_id,
            'airplane_id' => $booking->airplane_id,
            'stay_scope' => $booking->stay_scope,
            'rooms' => $detail->rooms->map(fn ($room): array => [
                'room_type_id' => $room->room_type_id,
                'price' => $room->price,
            ])->all(),
            'service_amount' => $booking->service_amount,
            'discount' => $booking->discount,
            'makka_hotel_id' => $detail->hotel_id,
            'makka_company_id' => $detail->company_id,
            'makka_meal_id' => $detail->meal_id,
            'makka_check_in' => $detail->check_in->toDateString(),
            'makka_check_out' => $detail->check_out->toDateString(),
        ];
    }

    private function storePdf(Visa $visa, string $contents): string
    {
        $path = 'visas/'.$visa->booking_id.'/'.Str::uuid().'.pdf';
        Storage::disk('visas')->put($path, $contents);

        return $path;
    }
}
