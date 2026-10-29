<?php

namespace App\Filament\Resources\Visas\Tables;

use App\Actions\Visas\UpdateVisaStatusAction;
use App\Enums\VisaStatus;
use App\Filament\Resources\Bookings\BookingResource;
use App\Models\BookingDetail;
use App\Models\Broker;
use App\Models\Visa;
use Filament\Actions\Action;
use Filament\Actions\BulkAction;
use Filament\Actions\BulkActionGroup;
use Filament\Forms\Components\DatePicker;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Grouping\Group;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

class VisasTable
{
    public static function configure(Table $table): Table
    {
        $bookingGroup = self::bookingGroup();

        return $table
            ->columns([
                TextColumn::make('customer.name_ar')
                    ->label('اسم المعتمر')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('relation')
                    ->label('الصفة')
                    ->state(fn (Visa $record): string => $record->customer_id === $record->booking?->customer_id ? 'ولي الأمر' : 'مرافق')
                    ->badge()
                    ->color(fn (Visa $record): string => $record->customer_id === $record->booking?->customer_id ? 'primary' : 'gray'),
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
                        ->when($data['from'] ?? null, fn (Builder $query, $from): Builder => $query->whereHas('booking.details', fn (Builder $details): Builder => $details->where('check_in', '>=', $from)))
                        ->when($data['until'] ?? null, fn (Builder $query, $until): Builder => $query->whereHas('booking.details', fn (Builder $details): Builder => $details->where('check_in', '<=', $until)))),
            ])
            ->recordActions([
                VisaRecordActions::updateStatus(),
                VisaRecordActions::replaceFile(),
                VisaRecordActions::file(),
                VisaRecordActions::history(),
                Action::make('openBooking')
                    ->label('فتح الحجز')
                    ->icon('heroicon-o-arrow-top-right-on-square')
                    ->url(fn (Visa $record): string => BookingResource::getUrl('view', ['record' => $record->booking_id]))
                    ->openUrlInNewTab(),
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
            ->groups([$bookingGroup])
            ->defaultGroup($bookingGroup)
            ->modifyQueryUsing(fn (Builder $query): Builder => $query
                ->with([
                    'booking' => function (BelongsTo $booking): BelongsTo {
                        $booking
                            ->with(['customer', 'broker', 'visas']);
                        $booking
                            ->withCount([
                                'visas',
                                'visas as issued_visas_count' => fn (Builder $visas): Builder => $visas->where('status', VisaStatus::Issued->value),
                            ]);

                        return $booking;
                    },
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

    private static function bookingGroup(): Group
    {
        return Group::make('booking.booking_number')
            ->label('الحجز')
            ->titlePrefixedWithLabel(false)
            ->collapsible()
            ->getTitleFromRecordUsing(fn (Visa $record): string => 'حجز '.$record->booking->booking_number.' — ولي الأمر: '.$record->booking->customer?->name_ar)
            ->getDescriptionFromRecordUsing(function (Visa $record): string {
                $booking = $record->booking;

                if (! array_key_exists('visas_count', $booking->getAttributes())) {
                    $booking->loadCount([
                        'visas',
                        'visas as issued_visas_count' => fn (Builder $visas): Builder => $visas->where('status', VisaStatus::Issued->value),
                    ]);
                }

                return ($booking->broker?->name ?? 'الفرع الرئيسي')
                    .' · أقرب سفر '.($record->travel_date_sort ? Carbon::parse($record->travel_date_sort)->format('d/m/Y') : '—')
                    .' · '.$booking->issued_visas_count.' من '.$booking->visas_count.' مستخرجة';
            })
            ->orderQueryUsing(function (Builder $query, string $direction): Builder {
                $query->join('bookings as visa_bookings', 'visa_bookings.id', '=', 'visas.booking_id')
                    ->addSelect('visas.*')
                    ->orderBy(
                        BookingDetail::query()
                            ->select('check_in')
                            ->whereColumn('booking_id', 'visa_bookings.id')
                            ->orderBy('check_in')
                            ->limit(1),
                        $direction,
                    )
                    ->orderBy(
                        Visa::query()
                            ->select('id')
                            ->whereColumn('id', 'visas.id')
                            ->whereColumn('customer_id', 'visa_bookings.customer_id'),
                        'desc',
                    )
                    ->orderBy('visas.id');

                return $query;
            });
    }
}
