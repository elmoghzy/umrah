<?php

use App\Http\Controllers\PublicVisaStatusController;
use App\Http\Controllers\VisaFileController;
use App\Models\Booking;
use App\Reports\BookingListQuery;
use App\Reports\HotelRoomsQuery;
use App\Reports\TripMovementsQuery;
use BaconQrCode\Renderer\Image\SvgImageBackEnd;
use BaconQrCode\Renderer\ImageRenderer;
use BaconQrCode\Renderer\RendererStyle\RendererStyle;
use BaconQrCode\Writer;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Route;

// Route::get('/', function () {
// return view('welcome');
// });

Route::get('/', fn () => redirect('/admin/login'))->name('login');

Route::get('/visa-status/{token}', PublicVisaStatusController::class)->name('visas.public');

Route::middleware('auth')->get('/admin/bookings/{booking}/receipt', function (Booking $booking) {
    Gate::authorize('view', $booking);
    $renderer = new ImageRenderer(new RendererStyle(180), new SvgImageBackEnd);
    $qr = (new Writer($renderer))->writeString($booking->publicUrl());

    return view('receipts.booking', [
        'booking' => $booking->load([
            'customer',
            'residents.customer',
            'details.hotel',
            'details.meal',
            'details.rooms',
            'transfers.transport',
            'transfers.fromCity',
            'transfers.toCity',
        ]),
        'qr' => $qr,
        'watermark' => true,
    ]);
})->name('bookings.receipt');

Route::middleware('auth')->group(function (): void {
    Route::get('/admin/visas/{visa}/file', VisaFileController::class)->name('visas.file');

    Route::get('/admin/reports/booking-list/print', function (Request $request) {
        Gate::authorize('viewAny', Booking::class);

        $rows = BookingListQuery::apply(BookingListQuery::make(), [
            'hotel_id' => $request->integer('hotelId') ?: null,
            'city_id' => $request->integer('cityId') ?: null,
            'booking_type_id' => $request->integer('bookingTypeId') ?: null,
            'broker_id' => $request->integer('brokerId') ?: null,
            'date_range' => ['from' => $request->string('dateFrom')->toString(), 'to' => $request->string('dateTo')->toString()],
        ])->latest('id')->get();

        return view('reports.booking-list-print', ['rows' => $rows, 'watermark' => true]);
    })->name('reports.booking-list.print');

    Route::get('/admin/reports/trip-movements/print', function (Request $request) {
        Gate::authorize('viewAny', Booking::class);

        $rows = TripMovementsQuery::apply(TripMovementsQuery::make(), [
            'hotel_id' => $request->integer('hotelId') ?: null,
            'city_id' => $request->integer('cityId') ?: null,
            'company_id' => $request->integer('companyId') ?: null,
            'booking_type_id' => $request->integer('bookingTypeId') ?: null,
            'broker_id' => $request->integer('brokerId') ?: null,
            'airplane_id' => $request->integer('airplaneId') ?: null,
            'date_range' => ['from' => $request->string('dateFrom')->toString(), 'to' => $request->string('dateTo')->toString()],
        ])->latest('id')->get();

        return view('reports.trip-movements-print', ['rows' => $rows, 'watermark' => false]);
    })->name('reports.trip-movements.print');

    Route::get('/admin/reports/hotel-rooms/print', function (Request $request) {
        Gate::authorize('viewAny', Booking::class);

        $rows = HotelRoomsQuery::apply(HotelRoomsQuery::make(), [
            'hotel_id' => $request->integer('hotelId') ?: null,
            'city_id' => $request->integer('cityId') ?: null,
            'booking_type_id' => $request->integer('bookingTypeId') ?: null,
            'broker_id' => $request->integer('brokerId') ?: null,
            'date_range' => ['from' => $request->string('dateFrom')->toString(), 'to' => $request->string('dateTo')->toString()],
        ])->latest('id')->get();

        return view('reports.hotel-rooms-print', ['rows' => $rows, 'watermark' => true]);
    })->name('reports.hotel-rooms.print');
});
