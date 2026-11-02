<?php

namespace App\Http\Controllers;

use App\Models\Booking;
use App\Models\Visa;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

class PublicVisaFileController extends Controller
{
    public function __invoke(string $token, Visa $visa): StreamedResponse
    {
        $booking = Booking::query()
            ->where('public_token', $token)
            ->firstOrFail();

        $visa = $booking->visas()->whereKey($visa->getKey())->firstOrFail();
        abort_unless($visa->isDownloadable(), 404);

        $disk = Storage::disk(config('umrah.visa_disk', 'visas'));

        $visa->forceFill([
            'downloads_count' => $visa->downloads_count + 1,
            'first_downloaded_at' => $visa->first_downloaded_at ?? now(),
            'last_downloaded_at' => now(),
        ])->save();

        return $disk->response(
            $visa->file_path,
            filled($visa->original_name) ? basename($visa->original_name) : 'visa.pdf',
            [
                'Content-Type' => 'application/pdf',
                'Cache-Control' => 'private, no-store',
                'X-Content-Type-Options' => 'nosniff',
            ],
            'attachment',
        );
    }
}
