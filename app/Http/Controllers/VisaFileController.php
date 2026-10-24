<?php

namespace App\Http\Controllers;

use App\Models\Visa;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

class VisaFileController extends Controller
{
    /**
     * Handle the incoming request.
     */
    public function __invoke(Request $request, Visa $visa): StreamedResponse
    {
        $visa = Visa::query()
            ->forUser($request->user())
            ->with('booking')
            ->findOrFail($visa->getKey());

        Gate::authorize('view', $visa);

        $disk = Storage::disk(config('umrah.visa_disk', 'visas'));
        abort_unless($visa->isDownloadable(), 404);

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
            $request->boolean('download') ? 'attachment' : 'inline',
        );
    }
}
