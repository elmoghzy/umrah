<?php

namespace App\Filament\Resources\Bookings\Pages;

use App\Filament\Resources\Bookings\BookingResource;
use Filament\Actions\Action;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;
use Filament\Support\Icons\Heroicon;

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
                ->url(fn (): string => route('reports.booking-list.print'))
                ->openUrlInNewTab(),
        ];
    }
}
