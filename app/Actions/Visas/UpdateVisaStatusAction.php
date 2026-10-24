<?php

namespace App\Actions\Visas;

use App\Enums\VisaStatus;
use App\Models\Visa;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Throwable;

class UpdateVisaStatusAction
{
    public function handle(
        Visa $visa,
        VisaStatus $newStatus,
        ?string $filePath = null,
        ?string $rejectedReason = null,
        ?string $visaNumber = null,
        ?string $originalName = null,
    ): Visa {
        $visa->loadMissing('booking');
        Gate::authorize('update', $visa);

        $oldFilePath = $visa->file_path;
        $isReplacingFile = filled($filePath) && $filePath !== $oldFilePath;
        $disk = Storage::disk(config('umrah.visa_disk', 'visas'));

        if ($visa->status === $newStatus && ! $isReplacingFile) {
            return $visa;
        }

        try {
            if ($filePath !== null) {
                $this->validatePdf($visa, $filePath);
            }

            $candidateFilePath = $filePath ?? $oldFilePath;
            if ($newStatus === VisaStatus::Issued) {
                if (blank($candidateFilePath)) {
                    throw ValidationException::withMessages([
                        'file_path' => 'يجب إرفاق ملف PDF صالح قبل اعتماد التأشيرة.',
                    ]);
                }

                $this->validatePdf($visa, $candidateFilePath);
            }

            if ($newStatus === VisaStatus::Rejected) {
                $rejectedReason = filled($rejectedReason) ? trim($rejectedReason) : null;
                validator(
                    ['rejected_reason' => $rejectedReason],
                    ['rejected_reason' => ['required', 'string', 'min:3', 'max:255']],
                    ['rejected_reason.required' => 'سبب الرفض مطلوب.', 'rejected_reason.min' => 'سبب الرفض يجب ألا يقل عن 3 أحرف.'],
                )->validate();
            }

            if (filled($visaNumber)) {
                validator(
                    ['visa_number' => $visaNumber],
                    ['visa_number' => ['string', 'max:100']],
                )->validate();
            }

            $previousStatus = $visa->status;

            $updatedVisa = DB::transaction(function () use (
                $visa,
                $newStatus,
                $filePath,
                $isReplacingFile,
                $originalName,
                $rejectedReason,
                $visaNumber,
                $previousStatus,
                $disk,
            ): Visa {
                $visa->status = $newStatus;

                if ($newStatus === VisaStatus::Issued) {
                    if ($previousStatus !== VisaStatus::Issued) {
                        $visa->issued_at = now();
                        $visa->issued_by = auth()->id();
                    }

                    $visa->rejected_reason = null;
                } elseif ($newStatus === VisaStatus::Rejected) {
                    $visa->rejected_reason = trim((string) $rejectedReason);
                } else {
                    $visa->rejected_reason = null;
                }

                if ($isReplacingFile && $filePath !== null) {
                    $visa->file_path = $filePath;
                    $visa->original_name = filled($originalName)
                        ? mb_substr(basename($originalName), 0, 255)
                        : basename($filePath);
                    $visa->file_size = $disk->size($filePath);
                }

                if (filled($visaNumber)) {
                    $visa->visa_number = trim($visaNumber);
                }

                $visa->save();

                $note = $newStatus === VisaStatus::Rejected
                    ? $visa->rejected_reason
                    : ($isReplacingFile ? 'تم استبدال ملف التأشيرة.' : $newStatus->getLabel());

                $visa->events()->create([
                    'user_id' => auth()->id(),
                    'from_status' => $previousStatus?->value,
                    'to_status' => $newStatus->value,
                    'note' => $note,
                    'created_at' => now(),
                ]);

                return $visa->fresh(['booking', 'customer', 'issuer', 'latestEvent.user']);
            });
        } catch (Throwable $exception) {
            if ($isReplacingFile && $this->isOwnedFilePath($visa, (string) $filePath)) {
                $disk->delete($filePath);
            }

            throw $exception;
        }

        if ($isReplacingFile && filled($oldFilePath)) {
            $disk->delete($oldFilePath);
        }

        return $updatedVisa;
    }

    private function validatePdf(Visa $visa, string $filePath): void
    {
        $disk = Storage::disk(config('umrah.visa_disk', 'visas'));

        if (! $this->isOwnedFilePath($visa, $filePath) || ! $disk->exists($filePath)) {
            throw ValidationException::withMessages([
                'file_path' => 'ملف التأشيرة غير موجود في مجلد الحجز أو مساره غير صالح.',
            ]);
        }

        $maximumBytes = max(1, (int) config('umrah.visa_max_kb', 5120)) * 1024;
        $absolutePath = $disk->path($filePath);
        $mimeType = (new \finfo(FILEINFO_MIME_TYPE))->file($absolutePath);
        $file = fopen($absolutePath, 'rb');
        $signature = $file === false ? false : fread($file, 4);

        if (is_resource($file)) {
            fclose($file);
        }

        if ($mimeType !== 'application/pdf' || $signature !== '%PDF' || $disk->size($filePath) > $maximumBytes) {
            throw ValidationException::withMessages([
                'file_path' => 'يجب إرفاق ملف PDF حقيقي لا يتجاوز الحد المسموح.',
            ]);
        }
    }

    private function isOwnedFilePath(Visa $visa, string $filePath): bool
    {
        $pattern = '~^visas/'.preg_quote((string) $visa->booking_id, '~').'/([0-9a-f-]{36})\.pdf$~i';

        if (! preg_match($pattern, $filePath, $matches)) {
            return false;
        }

        $filename = basename($filePath);
        $extension = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
        $uuid = $matches[1];

        return $extension === 'pdf'
            && Str::isUuid($uuid);
    }
}
