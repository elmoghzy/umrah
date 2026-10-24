<?php

namespace App\Actions;

use App\Models\Airplane;
use App\Models\Booking;
use App\Models\BookingDetail;
use App\Models\BookingTransfer;
use App\Models\BookingType;
use App\Models\Broker;
use App\Models\City;
use App\Models\Company;
use App\Models\Hotel;
use App\Models\Meal;
use App\Models\Room;
use App\Models\RoomType;
use App\Models\Transport;
use Filament\Notifications\Notification;
use Filament\Support\Exceptions\Halt;
use Illuminate\Database\Eloquent\Model;

class PreventForceDeleteWithRelations
{
    public static function handle(Model $record): void
    {
        $hasRelations = match (true) {
            $record instanceof City => $record->hotels()->exists()
                || $record->hasMany(BookingDetail::class)->exists()
                || $record->hasMany(BookingTransfer::class, 'from_city_id')->exists()
                || $record->hasMany(BookingTransfer::class, 'to_city_id')->exists(),
            $record instanceof Meal => $record->hasMany(BookingDetail::class)->exists(),
            $record instanceof BookingType => $record->hasMany(Booking::class)->exists(),
            $record instanceof RoomType => $record->hasMany(Room::class)->exists(),
            $record instanceof Airplane => $record->hasMany(Booking::class)->exists(),
            $record instanceof Transport => $record->transfers()->exists(),
            $record instanceof Broker => $record->hasMany(Booking::class)->exists(),
            $record instanceof Company => $record->hasMany(BookingDetail::class)->exists(),
            $record instanceof Hotel => $record->hasMany(BookingDetail::class)->exists(),
            default => false,
        };

        if (! $hasRelations) {
            return;
        }

        Notification::make()
            ->danger()
            ->title('لا يمكن الحذف النهائي')
            ->body('لا يمكن حذف هذا السجل نهائيًا لأنه مستخدم في حجوزات أو بيانات مرتبطة.')
            ->send();

        throw new Halt;
    }
}
