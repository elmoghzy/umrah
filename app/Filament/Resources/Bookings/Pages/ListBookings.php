<?php

namespace App\Filament\Resources\Bookings\Pages;

use App\Filament\Resources\Bookings\BookingResource;
use App\Models\Booking;
use Filament\Actions\Action;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;
use Filament\Support\Icons\Heroicon;
use Illuminate\Support\Collection;

class ListBookings extends ListRecords
{
    protected static string $resource = BookingResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
            Action::make('print')
                ->label('طباعة')
                ->icon(Heroicon::OutlinedPrinter)
                ->color('primary')
                ->url(function (): string {
                    $records = $this->getTableRecords();
                    $bookings = $records instanceof Collection
                        ? $records->all()
                        : $records->items();

                    return route('reports.bookings-page.print', [
                        'bookingIds' => collect($bookings)
                            ->map(fn (Booking $booking): int => (int) $booking->getKey())
                            ->all(),
                    ]);
                })
                ->openUrlInNewTab(),
        ];
    }
}
