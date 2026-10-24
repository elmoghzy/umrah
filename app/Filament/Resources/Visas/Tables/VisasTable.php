<?php

namespace App\Filament\Resources\Visas\Tables;

use App\Actions\Visas\UpdateVisaStatusAction;
use App\Enums\VisaStatus;
use App\Models\BookingDetail;
use App\Models\Broker;
use App\Models\Visa;
use Filament\Actions\BulkAction;
use Filament\Actions\BulkActionGroup;
use Filament\Forms\Components\DatePicker;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

class VisasTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('customer.name_ar')
                    ->label('اسم المعتمر')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('booking.booking_number')
                    ->label('رقم الحجز')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('booking.broker.name')
                    ->label('الفرع')
                    ->placeholder('الفرع الرئيسي')
                    ->visible(fn (): bool => auth()->user()?->isMainAdmin() ?? false),
                TextColumn::make('status')
                    ->label('الحالة')
                    ->badge(),
                TextColumn::make('travel_date_sort')
                    ->label('أقرب سفر')
                    ->date('d/m/Y')
                    ->sortable(),
                TextColumn::make('issued_at')
                    ->label('تاريخ الاستخراج')
                    ->dateTime('d/m/Y H:i')
                    ->placeholder('—')
                    ->sortable(),
                TextColumn::make('latestEvent.user.name')
                    ->label('آخر من حدّث')
                    ->placeholder('—'),
            ])
            ->filters([
                SelectFilter::make('status')
                    ->label('الحالة')
                    ->options(VisaStatus::class)
                    ->default(VisaStatus::Pending->value),
                SelectFilter::make('broker_id')
                    ->label('الفرع')
                    ->options(fn (): array => Broker::query()->orderBy('name')->pluck('name', 'id')->all())
                    ->query(fn (Builder $query, array $data): Builder => $query->when(
                        $data['value'] ?? null,
                        fn (Builder $query, $brokerId): Builder => $query->whereHas('booking', fn (Builder $booking): Builder => $booking->where('broker_id', $brokerId)),
                    ))
                    ->visible(fn (): bool => auth()->user()?->isMainAdmin() ?? false),
                Filter::make('travel_period')
                    ->label('فترة السفر')
                    ->schema([
                        DatePicker::make('from')->label('من'),
                        DatePicker::make('until')->label('إلى'),
                    ])
                    ->query(fn (Builder $query, array $data): Builder => $query
                        ->when($data['from'] ?? null, fn (Builder $query, $from): Builder => $query->whereHas('booking.details', fn (Builder $details): Builder => $details->whereDate('check_in', '>=', $from)))
                        ->when($data['until'] ?? null, fn (Builder $query, $until): Builder => $query->whereHas('booking.details', fn (Builder $details): Builder => $details->whereDate('check_in', '<=', $until)))),
            ])
            ->recordActions([
                VisaRecordActions::updateStatus(),
                VisaRecordActions::replaceFile(),
                VisaRecordActions::file(),
                VisaRecordActions::history(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    BulkAction::make('markPending')
                        ->label('تعليم كقيد الاستخراج')
                        ->icon('heroicon-o-arrow-path')
                        ->requiresConfirmation()
                        ->action(function (Collection $records): void {
                            foreach ($records as $record) {
                                $visa = Visa::query()
                                    ->forUser(auth()->user())
                                    ->with('booking')
                                    ->findOrFail($record->getKey());

                                app(UpdateVisaStatusAction::class)->handle($visa, VisaStatus::Pending);
                            }
                        }),
                ]),
            ])
            ->modifyQueryUsing(fn (Builder $query): Builder => $query
                ->with([
                    'booking.customer',
                    'booking.broker',
                    'customer',
                    'latestEvent.user',
                ])
                ->addSelect([
                    'travel_date_sort' => BookingDetail::query()
                        ->select('check_in')
                        ->whereColumn('booking_id', 'visas.booking_id')
                        ->orderBy('check_in')
                        ->limit(1),
                ]))
            ->defaultSort('travel_date_sort', 'asc')
            ->persistFiltersInSession();
    }
}
