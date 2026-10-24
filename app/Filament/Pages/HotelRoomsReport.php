<?php

namespace App\Filament\Pages;

use App\Models\BookingType;
use App\Models\Broker;
use App\Models\City;
use App\Models\Hotel;
use App\Reports\HotelRoomsQuery;
use Filament\Actions\Action;
use Filament\Forms\Components\DatePicker;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\Summarizers\Sum;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Arr;

class HotelRoomsReport extends Page implements HasTable
{
    use InteractsWithTable;

    protected string $view = 'filament.pages.hotel-rooms-report';

    protected static string|\BackedEnum|null $navigationIcon = Heroicon::OutlinedHomeModern;

    protected static string|\UnitEnum|null $navigationGroup = 'التقارير';

    protected static ?string $navigationLabel = 'الغرف المحجوزة';

    protected static ?string $title = 'تقرير الغرف المحجوزة في الفندق';

    public function table(Table $table): Table
    {
        return $table
            ->query(HotelRoomsQuery::make())
            ->columns([
                TextColumn::make('booking.booking_number')->label('رقم الحجز')->sortable()->searchable(),
                TextColumn::make('booking.broker.name')->label('الفرع')->placeholder('الفرع الرئيسي')->sortable(),
                TextColumn::make('booking.bookingType.name')->label('النوع')->sortable(),
                TextColumn::make('booking.customer.name_ar')->label('ولي الأمر')->searchable(),
                TextColumn::make('check_in')->label('الدخول')->date('d/m/Y')->sortable(),
                TextColumn::make('check_out')->label('الخروج')->date('d/m/Y')->sortable(),
                TextColumn::make('rooms_count')->label('عدد الغرف')->sortable()->summarize(Sum::make()),
            ])
            ->filters([
                SelectFilter::make('hotel_id')->label('الفندق')->options(fn (): array => Hotel::query()->orderBy('name_ar')->pluck('name_ar', 'id')->all())->searchable()->preload(),
                SelectFilter::make('city_id')->label('المدينة')->options(fn (): array => City::query()->orderBy('name')->pluck('name', 'id')->all())->searchable()->preload(),
                SelectFilter::make('booking_type_id')->label('نوع الحجز')->options(fn (): array => BookingType::query()->orderBy('name')->pluck('name', 'id')->all())->searchable()->preload(),
                SelectFilter::make('broker_id')
                    ->label('الفرع')
                    ->options(fn (): array => Broker::query()->orderBy('name')->pluck('name', 'id')->all())
                    ->searchable()
                    ->preload()
                    ->query(fn (Builder $query, array $data): Builder => $query->when($data['value'] ?? null, fn (Builder $query, $value): Builder => $query->whereHas('booking', fn (Builder $booking): Builder => $booking->where('broker_id', $value))))
                    ->visible(fn (): bool => auth()->user()?->isMainAdmin() ?? false),
                $this->dateFilter(),
            ])
            ->deferFilters()
            ->filtersFormColumns(3)
            ->persistFiltersInSession()
            ->headerActions([
                Action::make('print')->label('طباعة')->icon(Heroicon::OutlinedPrinter)->color('primary')->url(fn (): string => route('reports.hotel-rooms.print', $this->printQuery()))->openUrlInNewTab(),
            ])
            ->striped()
            ->paginated([10, 25, 50])
            ->defaultPaginationPageOption(25)
            ->defaultSort('id', 'desc')
            ->emptyStateHeading('لا توجد نتائج');
    }

    private function dateFilter(): Filter
    {
        return Filter::make('date_range')
            ->label('فترة الرحلة')
            ->form([
                DatePicker::make('from')->label('من')->native(false)->displayFormat('d/m/Y'),
                DatePicker::make('to')->label('إلى')->native(false)->displayFormat('d/m/Y'),
            ])
            ->query(fn (Builder $query, array $data): Builder => HotelRoomsQuery::apply($query, ['date_range' => $data]));
    }

    /** @return array<string, mixed> */
    private function printQuery(): array
    {
        $filters = $this->tableFilters ?? [];

        return array_filter([
            'hotelId' => Arr::get($filters, 'hotel_id.value'),
            'cityId' => Arr::get($filters, 'city_id.value'),
            'bookingTypeId' => Arr::get($filters, 'booking_type_id.value'),
            'brokerId' => Arr::get($filters, 'broker_id.value'),
            'dateFrom' => Arr::get($filters, 'date_range.from'),
            'dateTo' => Arr::get($filters, 'date_range.to'),
        ], fn ($value): bool => filled($value));
    }
}
