<?php

namespace Database\Factories;

use App\Models\Booking;
use App\Models\BookingTransfer;
use App\Models\City;
use App\Models\Transport;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<BookingTransfer>
 */
class BookingTransferFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'booking_id' => Booking::factory(),
            'transport_id' => Transport::factory(),
            'from_city_id' => City::factory(),
            'to_city_id' => City::factory(),
            'travel_date' => fake()->date(),
            'travel_time' => fake()->time('H:i'),
            'price' => fake()->randomFloat(2, 0, 1000),
            'note' => fake()->optional()->sentence(6),
        ];
    }
}
