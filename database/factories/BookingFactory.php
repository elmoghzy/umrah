<?php

namespace Database\Factories;

use App\Models\Booking;
use App\Models\Customer;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Booking>
 */
class BookingFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'customer_id' => Customer::factory(),
            'booking_number' => fake()->unique()->numberBetween(1, 4_000_000_000),
            'service_amount' => 0,
            'total_amount' => 0,
            'transport_amount' => 0,
            'discount' => 0,
            'net_amount' => 0,
            'stay_scope' => 0,
        ];
    }
}
