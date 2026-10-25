<?php

namespace App\Filament\Resources\Visas\Tables;

use App\Actions\Visas\UpdateVisaStatusAction;
use App\Enums\VisaStatus;
use App\Models\Visa;
use Filament\Actions\Action;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Utilities\Get;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\HtmlString;
use Illuminate\Support\Str;
use InvalidArgumentException;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;

class VisaRecordActions
{
    public static function updateStatus(): Action
    {
        return Action::make('updateVisaStatus')
            ->label('تحديث الحالة')
            ->icon('heroicon-o-arrow-path')
            ->authorize('update')
            ->fillForm(fn (Visa $record): array => [
                'status' => $record->status->value,
                'visa_number' => $record->visa_number,
            ])
            ->schema([
                Select::make('status')
                    ->label('الحالة')
                    ->options(VisaStatus::class)
                    ->required()
                    ->live(),
                self::fileUpload()
                    ->label('ملف التأشيرة PDF')
                    ->required(fn (Get $get, Visa $record): bool => self::statusValue($get('status')) === VisaStatus::Issued->value && blank($record->file_path))
                    ->helperText('أرفق ملف PDF، ويصبح إلزامياً عند اختيار حالة تم استخراج التأشيرة.'),
                Textarea::make('rejected_reason')
                    ->label('سبب الرفض')
                    ->minLength(3)
                    ->maxLength(255)
                    ->visible(fn (Get $get): bool => self::statusValue($get('status')) === VisaStatus::Rejected->value)
                    ->required(fn (Get $get): bool => self::statusValue($get('status')) === VisaStatus::Rejected->value),
                TextInput::make('visa_number')
                    ->label('رقم التأشيرة')
                    ->maxLength(100),
            ])
            ->action(function (Visa $record, array $data): void {
                $visa = self::authorizedRecord($record);

                app(UpdateVisaStatusAction::class)->handle(
                    $visa,
                    self::statusFromState($data['status']),
                    self::singleFilePath($data['file_path'] ?? null),
                    $data['rejected_reason'] ?? null,
                    $data['visa_number'] ?? null,
                    self::singleOriginalName($data['original_name'] ?? null),
                );
            });
    }

    public static function replaceFile(): Action
    {
        return Action::make('replaceVisaFile')
            ->label('استبدال الملف')
            ->icon('heroicon-o-arrow-up-tray')
            ->authorize('update')
            ->visible(fn (Visa $record): bool => filled($record->file_path))
            ->schema([
                self::fileUpload()
                    ->label('ملف التأشيرة الجديد')
                    ->required(),
            ])
            ->action(function (Visa $record, array $data): void {
                $visa = self::authorizedRecord($record);

                app(UpdateVisaStatusAction::class)->handle(
                    $visa,
                    $visa->status,
                    self::singleFilePath($data['file_path'] ?? null),
                    originalName: self::singleOriginalName($data['original_name'] ?? null),
                );
            });
    }

    public static function file(): Action
    {
        return Action::make('viewVisaFile')
            ->label('معاينة / تحميل')
            ->icon('heroicon-o-document-arrow-down')
            ->authorize('view')
            ->visible(fn (Visa $record): bool => $record->isDownloadable())
            ->url(fn (Visa $record): string => route('visas.file', ['visa' => $record]))
            ->openUrlInNewTab();
    }

    public static function history(): Action
    {
        return Action::make('visaHistory')
            ->label('سجل التغييرات')
            ->icon('heroicon-o-clock')
            ->authorize('view')
            ->modalSubmitAction(false)
            ->modalCancelActionLabel('إغلاق')
            ->schema([
                Placeholder::make('events')
                    ->label('')
                    ->content(function (Visa $record): HtmlString {
                        $events = $record->events()->with('user')->get();

                        if ($events->isEmpty()) {
                            return new HtmlString('<p>لا توجد تغييرات مسجلة.</p>');
                        }

                        $items = $events->map(function ($event): string {
                            $from = VisaStatus::tryFrom((string) $event->from_status)?->getLabel() ?? '—';
                            $to = VisaStatus::tryFrom((string) $event->to_status)?->getLabel() ?? (string) $event->to_status;
                            $user = e($event->user?->name ?? 'النظام');
                            $note = filled($event->note) ? '<div>'.e($event->note).'</div>' : '';

                            return '<li><strong>'.e($from).' ← '.e($to).'</strong><small> — '.$user.'، '.$event->created_at?->format('Y-m-d H:i').'</small>'.$note.'</li>';
                        })->implode('');

                        return new HtmlString('<ol class="space-y-3">'.$items.'</ol>');
                    }),
            ]);
    }

    private static function fileUpload(): FileUpload
    {
        return FileUpload::make('file_path')
            ->disk(config('umrah.visa_disk', 'visas'))
            ->directory(fn (Visa $record): string => 'visas/'.$record->booking_id)
            ->visibility('private')
            ->acceptedFileTypes(['application/pdf'])
            ->maxSize((int) config('umrah.visa_max_kb', 5120))
            ->storeFileNamesIn('original_name')
            ->getUploadedFileNameForStorageUsing(fn (TemporaryUploadedFile $file): string => Str::uuid().'.pdf');
    }

    private static function authorizedRecord(Visa $record): Visa
    {
        $visa = Visa::query()
            ->forUser(auth()->user())
            ->with('booking')
            ->findOrFail($record->getKey());

        Gate::authorize('update', $visa);

        return $visa;
    }

    private static function singleFilePath(mixed $state): ?string
    {
        if (is_array($state)) {
            $state = reset($state) ?: null;
        }

        return is_string($state) && filled($state) ? $state : null;
    }

    private static function statusFromState(mixed $state): VisaStatus
    {
        if ($state instanceof VisaStatus) {
            return $state;
        }

        if (is_string($state)) {
            return VisaStatus::from($state);
        }

        throw new InvalidArgumentException('حالة التأشيرة غير صالحة.');
    }

    private static function statusValue(mixed $state): ?string
    {
        return $state instanceof VisaStatus ? $state->value : (is_string($state) ? $state : null);
    }

    private static function singleOriginalName(mixed $state): ?string
    {
        if (is_array($state)) {
            $state = reset($state) ?: null;
        }

        return is_string($state) && filled($state) ? $state : null;
    }
}
