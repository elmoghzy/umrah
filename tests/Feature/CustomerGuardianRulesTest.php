<?php

namespace Tests\Feature;

use App\Actions\Customers\CreateGuardianAction;
use App\Models\Booking;
use App\Models\Customer;
use App\Models\RoomResident;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class CustomerGuardianRulesTest extends TestCase
{
    use RefreshDatabase;

    public function test_customer_cannot_be_its_own_guardian(): void
    {
        $customer = Customer::create(['name_ar' => 'عميل']);

        $this->expectException(ValidationException::class);

        $customer->guardian_id = $customer->id;
        $customer->save();
    }

    public function test_customer_view_includes_bookings_where_customer_is_a_resident(): void
    {
        $customer = Customer::create(['name_ar' => 'المعتمر']);
        $booking = Booking::create(['customer_id' => Customer::create(['name_ar' => 'ولي الأمر'])->id, 'booking_number' => 99]);
        RoomResident::create(['booking_id' => $booking->id, 'customer_id' => $customer->id]);

        $this->assertSame(1, $customer->residentBookings()->whereKey($booking->id)->count());
    }

    public function test_dependent_cannot_be_selected_as_guardian(): void
    {
        $guardian = Customer::create(['name_ar' => 'ولي']);
        $dependent = Customer::create([
            'name_ar' => 'مرافق',
            'guardian_id' => $guardian->id,
        ]);
        $other = Customer::create(['name_ar' => 'عميل آخر']);

        $this->expectException(ValidationException::class);

        $other->guardian_id = $dependent->id;
        $other->save();
    }

    public function test_guardian_can_be_created_with_dependents(): void
    {
        $guardian = app(CreateGuardianAction::class)->handle([
            'name_ar' => 'ولي الأمر',
            'phone' => '0500000000',
            'dependents' => [
                ['name_ar' => 'المعتمر الأول', 'phone' => '0511111111'],
                ['name_ar' => 'المعتمر الثاني'],
            ],
        ]);

        $this->assertNull($guardian->guardian_id);
        $this->assertEqualsCanonicalizing(
            ['المعتمر الأول', 'المعتمر الثاني'],
            $guardian->dependents()->pluck('name_ar')->all(),
        );
    }

    public function test_duplicate_dependents_in_one_creation_are_saved_once(): void
    {
        $guardian = app(CreateGuardianAction::class)->handle([
            'name_ar' => 'ولي الأمر',
            'dependents' => [
                ['name_ar' => 'المعتمر', 'phone' => '0500000000'],
                ['name_ar' => 'المعتمر', 'phone' => '0500000000'],
            ],
        ]);

        $this->assertSame(1, $guardian->dependents()->count());
    }
}
