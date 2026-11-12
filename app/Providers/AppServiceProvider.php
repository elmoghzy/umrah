<?php

namespace App\Providers;

use App\Models\Booking;
use App\Models\BookingDetail;
use App\Models\BookingType;
use App\Models\Customer;
use App\Models\Hotel;
use App\Models\Room;
use App\Models\RoomResident;
use App\Models\Visa;
use App\Observers\DashboardCacheObserver;
use Filament\Actions\Action;
use Filament\Forms\Components\Field;
use Filament\Forms\Components\Select;
use Filament\Infolists\Components\Entry;
use Filament\Tables\Columns\Column;
use Filament\Tables\Enums\PaginationMode;
use Filament\Tables\Filters\BaseFilter;
use Filament\Tables\Table;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Table::configureUsing(
            fn (Table $table): Table => $table
                ->defaultPaginationPageOption(10)
                ->paginationMode(PaginationMode::Simple)
                ->persistRecordsPerPageInSession(false),
        );

        Field::configureUsing(
            fn (Field $field): Field => $field->translateLabel(),
        );

        Select::configureUsing(
            fn (Select $select): Select => $select
                ->preload()
                ->optionsLimit(2000),
        );

        Column::configureUsing(
            fn (Column $column): Column => $column->translateLabel(),
        );

        BaseFilter::configureUsing(
            fn (BaseFilter $filter): BaseFilter => $filter->translateLabel(),
        );

        Action::configureUsing(
            fn (Action $action): Action => $action->translateLabel(),
        );

        Entry::configureUsing(
            fn (Entry $entry): Entry => $entry->translateLabel(),
        );

        foreach ([
            Booking::class,
            BookingDetail::class,
            BookingType::class,
            Customer::class,
            Hotel::class,
            Room::class,
            RoomResident::class,
            Visa::class,
        ] as $model) {
            $model::observe(DashboardCacheObserver::class);
        }
    }
}
