<?php

namespace Tests\Feature\Filament\Resources\Bookings\Pages;

use App\Filament\Resources\Bookings\Pages\CreateBooking;
use App\Models\Customer;
use App\Models\User;
use Filament\Forms\Components\Select;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class CreateBookingTest extends TestCase
{
    use RefreshDatabase;

    public function test_booking_form_preloads_all_guardians_above_the_previous_limit(): void
    {
        Customer::factory()->count(60)->create();
        $this->actingAs(User::factory()->create());

        Livewire::test(CreateBooking::class)
            ->assertOk()
            ->assertFormFieldExists(
                'customer_id',
                fn (Select $field): bool => $field->isSearchable()
                    && $field->isPreloaded()
                    && $field->getOptionsLimit() === 2000
                    && count($field->getOptions()) === 60,
            )
            ->assertFormFieldExists(
                'booking_type_id',
                fn (Select $field): bool => $field->isSearchable() && $field->isPreloaded(),
            );
    }
}
