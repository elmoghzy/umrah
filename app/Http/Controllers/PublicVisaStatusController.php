<?php

namespace App\Http\Controllers;

use App\Models\Booking;

class PublicVisaStatusController extends Controller
{
    public function __invoke(string $token)
    {
        $booking = Booking::query()
            ->where('public_token', $token)
            ->with([
                'customer',
                'residents.customer',
                'visas',
            ])
            ->firstOrFail();

        return view('visas.public-status', [
            'booking' => $booking,
            'visasByCustomer' => $booking->visas->keyBy('customer_id'),
        ]);
    }
}
