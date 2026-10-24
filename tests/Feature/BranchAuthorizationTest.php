<?php

namespace Tests\Feature;

use App\Actions\Bookings\CreateBookingAction;
use App\Actions\Bookings\UpdateBookingAction;
use App\Enums\StayScope;
use App\Filament\Pages\BookingsReport;
use App\Filament\Resources\Bookings\BookingResource;
use App\Filament\Resources\Bookings\Pages\ListBookings;
use App\Filament\Resources\Users\Pages\CreateUser;
use App\Filament\Resources\Users\Pages\EditUser;
use App\Filament\Widgets\DashboardStats;
use App\Filament\Widgets\RecentBookingsTable;
use App\Models\Airplane;
use App\Models\Booking;
use App\Models\BookingDetail;
use App\Models\BookingType;
use App\Models\Broker;
use App\Models\City;
use App\Models\Company;
use App\Models\Customer;
use App\Models\Hotel;
use App\Models\Meal;
use App\Models\RoomType;
use App\Models\User;
use App\Reports\BookingListQuery;
use Filament\Facades\Filament;
use Filament\Panel;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Livewire\Livewire;
use Tests\TestCase;

class BranchAuthorizationTest extends TestCase
{
    use RefreshDatabase;

    public function test_panel_access_requires_an_active_manager_or_a_branch_assignment(): void
    {
        $branchA = $this->createBroker('فرع أ');
        $branchB = $this->createBroker('فرع ب');
        $manager = User::factory()->create(['role' => User::ROLE_MAIN_ADMIN, 'is_active' => true]);
        $employeeA = $this->createBranchEmployee($branchA);
        $employeeB = $this->createBranchEmployee($branchB);
        $withoutRole = User::factory()->create(['role' => '', 'is_active' => true]);
        $withoutBranch = User::factory()->create(['role' => User::ROLE_BRANCH_EMPLOYEE, 'broker_id' => null, 'is_active' => true]);
        $inactive = User::factory()->create(['role' => User::ROLE_MAIN_ADMIN, 'is_active' => false]);
        $panel = Filament::getPanel('admin');

        $this->assertInstanceOf(Panel::class, $panel);
        $this->assertTrue($manager->canAccessPanel($panel));
        $this->assertTrue($employeeA->canAccessPanel($panel));
        $this->assertTrue($employeeB->canAccessPanel($panel));
        $this->assertFalse($withoutRole->canAccessPanel($panel));
        $this->assertFalse($withoutBranch->canAccessPanel($panel));
        $this->assertFalse($inactive->canAccessPanel($panel));
    }

    public function test_branch_employee_sees_only_its_bookings_and_reports(): void
    {
        $branchA = $this->createBroker('فرع أ');
        $branchB = $this->createBroker('فرع ب');
        $employeeA = $this->createBranchEmployee($branchA);
        $bookingA = $this->createBookingForBroker($branchA, 202610101, 'عميل الفرع أ', 111);
        $bookingB = $this->createBookingForBroker($branchB, 202610102, 'عميل الفرع ب', 999);
        $detailA = $bookingA->details()->firstOrFail();
        $detailB = $bookingB->details()->firstOrFail();

        $this->actingAs($employeeA);

        Livewire::test(ListBookings::class)
            ->assertCanSeeTableRecords([$bookingA])
            ->assertCanNotSeeTableRecords([$bookingB]);

        Livewire::test(BookingsReport::class)
            ->assertCanSeeTableRecords([$detailA])
            ->assertCanNotSeeTableRecords([$detailB]);

        Livewire::test(RecentBookingsTable::class)
            ->assertCanSeeTableRecords([$bookingA])
            ->assertCanNotSeeTableRecords([$bookingB]);

        Livewire::test(DashboardStats::class)
            ->assertSee('صافي الشهر')
            ->assertSee('111,00')
            ->assertDontSee('999,00');

        foreach (['reports.booking-list.print', 'reports.trip-movements.print', 'reports.hotel-rooms.print'] as $routeName) {
            $this->get(route($routeName))
                ->assertOk()
                ->assertSee('عميل الفرع أ')
                ->assertDontSee('عميل الفرع ب');
        }
    }

    public function test_branch_employee_cannot_open_another_branches_booking(): void
    {
        $branchA = $this->createBroker('فرع أ');
        $branchB = $this->createBroker('فرع ب');
        $employeeA = $this->createBranchEmployee($branchA);
        $bookingA = $this->createBookingForBroker($branchA, 202610111, 'عميل أ');
        $bookingB = $this->createBookingForBroker($branchB, 202610112, 'عميل ب');

        $this->actingAs($employeeA);

        $this->get(BookingResource::getUrl())->assertOk();
        $this->get(BookingResource::getUrl('view', ['record' => $bookingB]))->assertNotFound();
        $this->get(BookingResource::getUrl('edit', ['record' => $bookingB]))->assertNotFound();
        $this->get(route('bookings.receipt', ['booking' => $bookingB]))->assertForbidden();

        $this->assertTrue(auth()->user()->can('view', $bookingA));
        $this->assertFalse(auth()->user()->can('view', $bookingB));
        $this->assertFalse(auth()->user()->can('delete', $bookingA));
        $this->assertFalse(auth()->user()->can('restore', $bookingA));
        $this->assertFalse(auth()->user()->can('forceDelete', $bookingA));
    }

    public function test_branch_employee_is_forbidden_from_master_data_branches_and_users(): void
    {
        $employee = $this->createBranchEmployee($this->createBroker('فرع أ'));
        $customer = Customer::query()->create(['name_ar' => 'عميل الفرع']);
        $this->actingAs($employee);

        $this->get('/admin/customers')->assertOk();
        $this->assertTrue($employee->can('viewAny', Customer::class));
        $this->assertTrue($employee->can('view', $customer));
        $this->assertTrue($employee->can('update', $customer));
        $this->assertFalse($employee->can('delete', $customer));

        foreach ([
            '/admin/hotels',
            '/admin/users',
            '/admin/brokers',
            '/admin/cities',
            '/admin/meals',
            '/admin/booking-types',
            '/admin/room-types',
            '/admin/airplanes',
            '/admin/companies',
        ] as $path) {
            $this->get($path)->assertForbidden();
        }

        $this->get('/admin')
            ->assertOk()
            ->assertSee('البيانات الأساسية')
            ->assertSee('مقفولة لموظف الفرع')
            ->assertSee('إدارة المستخدمين (مقفولة)')
            ->assertSee('aria-disabled="true"', false)
            ->assertSee('pointer-events: none', false)
            ->assertSee('الحجوزات')
            ->assertSee('التقارير')
            ->assertDontSee('الفروع');
    }

    public function test_branch_employee_booking_actions_force_their_branch_and_reject_other_branch_updates(): void
    {
        $branchA = $this->createBroker('فرع أ');
        $branchB = $this->createBroker('فرع ب');
        $employeeA = $this->createBranchEmployee($branchA);
        $this->actingAs($employeeA);

        $created = app(CreateBookingAction::class)->handle($this->bookingPayload($branchB->getKey()));

        $this->assertSame($branchA->getKey(), $created->broker_id);
        $this->assertDatabaseHas('bookings', ['id' => $created->getKey(), 'broker_id' => $branchA->getKey()]);

        $existing = $this->createBookingForBroker($branchA, 202610121, 'حجز للتحديث');
        $updated = app(UpdateBookingAction::class)->handle($existing, $this->bookingPayload($branchB->getKey()));

        $this->assertSame($branchA->getKey(), $updated->broker_id);
        $this->assertDatabaseHas('bookings', ['id' => $existing->getKey(), 'broker_id' => $branchA->getKey()]);

        $otherBranchBooking = $this->createBookingForBroker($branchB, 202610122, 'حجز فرع آخر');
        $this->expectException(AuthorizationException::class);

        app(UpdateBookingAction::class)->handle($otherBranchBooking, $this->bookingPayload($branchA->getKey()));
    }

    public function test_only_the_last_active_manager_is_protected_from_deletion_demotion_and_deactivation(): void
    {
        $lastManager = User::factory()->create(['role' => User::ROLE_MAIN_ADMIN, 'is_active' => true]);
        $employee = $this->createBranchEmployee($this->createBroker('فرع أ'));
        $this->actingAs($employee);

        $lastManager->assignAccessFromUserResource(User::ROLE_BRANCH_EMPLOYEE, null);
        $this->assertValidationMessage(
            fn (): bool => $lastManager->save(),
            'role',
            'لا يمكن تخفيض أو تعطيل آخر مدير نشط.',
        );

        $lastManager->refresh();
        $lastManager->is_active = false;
        $this->assertValidationMessage(
            fn (): bool => $lastManager->save(),
            'is_active',
            'لا يمكن تخفيض أو تعطيل آخر مدير نشط.',
        );

        $lastManager->refresh();
        $this->assertValidationMessage(
            fn (): bool => $lastManager->delete(),
            'user',
            'لا يمكن حذف آخر مدير نشط.',
        );
    }

    public function test_a_manager_cannot_demote_themselves_and_access_fields_cannot_be_changed_outside_the_resource(): void
    {
        $manager = User::factory()->create(['role' => User::ROLE_MAIN_ADMIN, 'is_active' => true]);
        User::factory()->create(['role' => User::ROLE_MAIN_ADMIN, 'is_active' => true]);
        $this->actingAs($manager);

        Livewire::test(EditUser::class, ['record' => $manager->getKey()])
            ->assertFormFieldDisabled('role');

        $manager->assignAccessFromUserResource(User::ROLE_BRANCH_EMPLOYEE, null);
        $this->assertValidationMessage(fn (): bool => $manager->save(), 'role', 'لا يمكنك تخفيض صلاحية حسابك الشخصي.');
        $this->assertFalse(auth()->user()->can('delete', $manager));
        $this->assertValidationMessage(
            fn (): bool => $manager->delete(),
            'user',
            'لا يمكنك حذف الحساب الذي سجلت الدخول به.',
        );

        $employee = $this->createBranchEmployee($this->createBroker('فرع ب'));
        $employee->broker_id = $this->createBroker('فرع ج')->getKey();
        $this->assertValidationMessage(
            fn (): bool => $employee->save(),
            'role',
            'تعديل نوع المستخدم والفرع مسموح من شاشة إدارة المستخدمين فقط.',
        );
    }

    public function test_manager_can_view_all_bookings_and_master_data_and_can_create_branch_users(): void
    {
        $branchA = $this->createBroker('فرع أ');
        $branchB = $this->createBroker('فرع ب');
        $manager = User::factory()->create(['role' => User::ROLE_MAIN_ADMIN, 'is_active' => true]);
        $bookingA = $this->createBookingForBroker($branchA, 202610131, 'عميل المدير أ');
        $bookingB = $this->createBookingForBroker($branchB, 202610132, 'عميل المدير ب');

        $this->actingAs($manager);

        foreach ([
            '/admin/hotels',
            '/admin/users',
            '/admin/brokers',
            '/admin/cities',
            '/admin/meals',
            '/admin/booking-types',
            '/admin/room-types',
            '/admin/airplanes',
            '/admin/companies',
        ] as $path) {
            $this->get($path)->assertOk();
        }

        $this->get(BookingResource::getUrl())->assertOk()->assertSee('عميل المدير أ')->assertSee('عميل المدير ب');
        $this->get(route('bookings.receipt', ['booking' => $bookingB]))->assertOk();
        $this->assertTrue(auth()->user()->can('delete', $bookingA));
        $this->assertTrue(auth()->user()->can('view', $bookingB));
        $this->assertSame(2, BookingListQuery::make()->count());

        Livewire::test(CreateUser::class)
            ->fillForm([
                'name' => 'موظف جديد',
                'email' => 'new.branch.employee@example.test',
                'role' => User::ROLE_BRANCH_EMPLOYEE,
                'broker_id' => $branchB->getKey(),
                'is_active' => true,
                'password' => 'password123',
            ])
            ->call('create')
            ->assertHasNoFormErrors();

        $this->assertDatabaseHas('users', [
            'email' => 'new.branch.employee@example.test',
            'role' => User::ROLE_BRANCH_EMPLOYEE,
            'broker_id' => $branchB->getKey(),
            'is_active' => true,
        ]);
    }

    public function test_booking_receipt_and_report_print_routes_require_authentication(): void
    {
        $booking = $this->createBookingForBroker($this->createBroker('فرع أ'), 202610141, 'عميل محمي');

        $this->get(route('bookings.receipt', ['booking' => $booking]))->assertRedirect(route('login'));

        foreach (['reports.booking-list.print', 'reports.trip-movements.print', 'reports.hotel-rooms.print'] as $routeName) {
            $this->get(route($routeName))->assertRedirect(route('login'));
        }
    }

    public function test_emergency_command_promotes_and_activates_a_user(): void
    {
        $branch = $this->createBroker('فرع أ');
        $employee = $this->createBranchEmployee($branch, ['is_active' => false]);

        $this->artisan('umrah:make-admin', ['email' => $employee->email])->assertExitCode(0);

        $this->assertDatabaseHas('users', [
            'id' => $employee->getKey(),
            'role' => User::ROLE_MAIN_ADMIN,
            'broker_id' => null,
            'is_active' => true,
        ]);
    }

    public function test_user_form_rejects_duplicate_email_and_short_passwords(): void
    {
        $branch = $this->createBroker('فرع أ');
        $manager = User::factory()->create(['role' => User::ROLE_MAIN_ADMIN, 'is_active' => true]);
        $existing = User::factory()->create(['email' => 'duplicate@example.test']);
        $this->actingAs($manager);

        Livewire::test(CreateUser::class)
            ->fillForm([
                'name' => 'مستخدم مكرر',
                'email' => $existing->email,
                'role' => User::ROLE_BRANCH_EMPLOYEE,
                'broker_id' => $branch->getKey(),
                'is_active' => true,
                'password' => 'short',
            ])
            ->call('create')
            ->assertHasFormErrors(['email' => 'unique', 'password' => 'min']);
    }

    private function createBroker(string $name): Broker
    {
        return Broker::query()->create(['name' => $name]);
    }

    /** @param array<string, mixed> $attributes */
    private function createBranchEmployee(Broker $broker, array $attributes = []): User
    {
        return User::factory()->create([
            'role' => User::ROLE_BRANCH_EMPLOYEE,
            'broker_id' => $broker->getKey(),
            'is_active' => true,
            ...$attributes,
        ]);
    }

    private function createBookingForBroker(Broker $broker, int $bookingNumber, string $customerName, float $netAmount = 100): Booking
    {
        $city = City::query()->firstOrCreate(['id' => 1], ['name' => 'مكة']);
        $customer = Customer::query()->create(['name_ar' => $customerName]);
        $hotel = Hotel::query()->create(['city_id' => $city->getKey(), 'name_ar' => 'فندق '.$customerName]);
        $booking = Booking::query()->create([
            'customer_id' => $customer->getKey(),
            'broker_id' => $broker->getKey(),
            'booking_number' => $bookingNumber,
            'net_amount' => $netAmount,
        ]);
        BookingDetail::query()->create([
            'booking_id' => $booking->getKey(),
            'hotel_id' => $hotel->getKey(),
            'city_id' => $city->getKey(),
            'check_in' => '2026-11-01',
            'check_out' => '2026-11-03',
        ]);

        return $booking->load('details');
    }

    /** @return array<string, mixed> */
    private function bookingPayload(int $requestedBrokerId): array
    {
        City::query()->upsert([
            ['id' => 1, 'name' => 'مكة'],
            ['id' => 2, 'name' => 'المدينة المنورة'],
        ], ['id'], ['name']);
        $customer = Customer::query()->create(['name_ar' => 'ولي أمر الإجراء']);
        $roomType = RoomType::query()->create(['name' => 'ثنائية', 'size' => 2, 'price' => 50, 'total_price' => 100]);
        $company = Company::query()->create(['name' => 'شركة الإجراء']);
        $meal = Meal::query()->create(['name' => 'وجبة الإجراء']);
        $bookingType = BookingType::query()->create(['name' => 'حجز الإجراء']);
        $airplane = Airplane::query()->create(['name' => 'طائرة الإجراء']);
        $madinaHotel = Hotel::query()->create(['city_id' => 2, 'name_ar' => 'فندق المدينة للإجراء']);
        $makkaHotel = Hotel::query()->create(['city_id' => 1, 'name_ar' => 'فندق مكة للإجراء']);

        return [
            'customer_id' => $customer->getKey(),
            'booking_type_id' => $bookingType->getKey(),
            'broker_id' => $requestedBrokerId,
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

    private function assertValidationMessage(callable $action, string $field, string $message): void
    {
        try {
            $action();
        } catch (ValidationException $exception) {
            $this->assertSame($message, $exception->errors()[$field][0] ?? null);

            return;
        }

        $this->fail('Expected a validation exception with the Arabic access-protection message.');
    }
}
