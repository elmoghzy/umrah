<?php

namespace App\Filament\Pages;

use App\Models\Airplane;
use App\Models\BookingType;
use App\Models\Broker;
use App\Models\City;
use App\Models\Company;
use App\Models\Hotel;
use App\Reports\TripMovementsQuery;
use Filament\Actions\Action;
use Filament\Forms\Components\DatePicker;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Arr;

class TripMovementsReport extends Page implements HasTable
{
    use InteractsWithTable;

    protected string $view = 'filament.pages.trip-movements-report';

    protected static string|\BackedEnum|null $navigationIcon = Heroicon::OutlinedMap;

    protected static string|\UnitEnum|null $navigationGroup = 'التقارير';

    protected static ?string $navigationLabel = 'حركة الرحلات';

    protected static ?string $title = 'تقرير حركة الرحلات';

    public function table(Table $table): Table
    {
        return $table
            ->query(TripMovementsQuery::make())
            ->columns([
                TextColumn::make('row_number')->label('م')->rowIndex(),
                TextColumn::make('booking.booking_number')->label('رقم الحجز')->sortable()->searchable(),
                TextColumn::make('booking.broker.name')->label('الفرع')->placeholder('الفرع الرئيسي')->sortable(),
                TextColumn::make('booking.customer.name_ar')->label('ولي الأمر')->searchable(),
                TextColumn::make('booking.bookingType.name')->label('النوع')->sortable(),
                TextColumn::make('hotel.name_ar')->label('الفندق')->sortable()->searchable(),
                TextColumn::make('check_in')->label('الدخول')->date('d/m/Y')->sortable(),
                TextColumn::make('check_out')->label('الخروج')->date('d/m/Y')->sortable(),
                TextColumn::make('created_at')->label('تاريخ التسجيل')->date('d/m/Y')->sortable(),
                TextColumn::make('booking.notes')->label('الملاحظة')->wrap(),
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
                SelectFilter::make('airplane_id')->label('الطيران')->options(fn (): array => Airplane::query()->orderBy('name')->pluck('name', 'id')->all())->searchable()->preload(),
                SelectFilter::make('company_id')->label('الشركة')->options(fn (): array => Company::query()->orderBy('name')->pluck('name', 'id')->all())->searchable()->preload(),
                $this->dateFilter(),
            ])
            ->deferFilters()
            ->filtersFormColumns(3)
            ->persistFiltersInSession()
            ->headerActions([
                Action::make('print')->label('طباعة')->icon(Heroicon::OutlinedPrinter)->color('primary')->url(fn (): string => route('reports.trip-movements.print', $this->printQuery()))->openUrlInNewTab(),
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
            ->query(fn (Builder $query, array $data): Builder => TripMovementsQuery::apply($query, ['date_range' => $data]));
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
            'airplaneId' => Arr::get($filters, 'airplane_id.value'),
            'companyId' => Arr::get($filters, 'company_id.value'),
            'dateFrom' => Arr::get($filters, 'date_range.from'),
            'dateTo' => Arr::get($filters, 'date_range.to'),
        ], fn ($value): bool => filled($value));
    }
}
