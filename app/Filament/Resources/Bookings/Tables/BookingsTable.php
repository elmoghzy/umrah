<?php

namespace App\Filament\Resources\Bookings\Tables;

use App\Actions\SimpleResourceTableActions;
use App\Enums\StayScope;
use App\Enums\VisaStatus;
use App\Models\Booking;
use App\Models\Hotel;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\RestoreBulkAction;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\DatePicker;
use Filament\Tables\Columns\Summarizers\Sum;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class BookingsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('booking_number')->label('رقم الحجز')->searchable()->sortable(),
                TextColumn::make('broker.name')->label('الفرع')->placeholder('الفرع الرئيسي')->sortable(),
                TextColumn::make('customer.name_ar')->label('ولي الأمر')->searchable(),
                TextColumn::make('bookingType.name')->label('النوع')->searchable(),
                TextColumn::make('stay_scope')->label('النطاق')->badge(),
                TextColumn::make('residents_count')->label('عدد المعتمرين')->counts('residents'),
                TextColumn::make('visas_progress')
                    ->label('التأشيرات')
                    ->state(fn (Booking $record): string => $record->visas_issued_count.'/'.$record->visas_total_count)
                    ->badge()
                    ->color(fn (Booking $record): string => $record->visas_total_count > 0 && $record->visas_issued_count === $record->visas_total_count
                        ? 'success'
                        : 'warning'),
                TextColumn::make('net_amount')
                    ->label('الصافي')
                    ->money(
                        config('umrah.currency_code'),
                        locale: config('umrah.currency_locale'),
                        decimalPlaces: (int) config('umrah.currency_decimal_places'),
                    )
                    ->sortable()
                    ->summarize(Sum::make()->money(
                        config('umrah.currency_code'),
                        locale: config('umrah.currency_locale'),
                        decimalPlaces: (int) config('umrah.currency_decimal_places'),
                    )),
                TextColumn::make('details.check_in')->label('أول دخول')->date()->sortable(),
                TextColumn::make('created_at')->label('تاريخ الإنشاء')->dateTime()->sortable(),
            ])
            ->filters([
                TrashedFilter::make(),
                SelectFilter::make('booking_type_id')->label('النوع')->relationship('bookingType', 'name'),
                SelectFilter::make('broker_id')->label('الفرع')->relationship('broker', 'name')->visible(fn (): bool => auth()->user()?->isMainAdmin() ?? false),
                SelectFilter::make('airplane_id')->label('الطيران')->relationship('airplane', 'name'),
                SelectFilter::make('stay_scope')->label('النطاق')->options(StayScope::class),
                SelectFilter::make('hotel_id')
                    ->label('الفندق')
                    ->options(fn (): array => Hotel::query()->pluck('name_ar', 'id')->all())
                    ->query(fn (Builder $query, array $data): Builder => $query->when($data['value'] ?? null, fn (Builder $query, $value): Builder => $query->whereHas('details', fn (Builder $details): Builder => $details->where('hotel_id', $value)))),
                Filter::make('check_in_range')
                    ->label('فترة الدخول')
                    ->form([
                        DatePicker::make('from')->label('من'),
                        DatePicker::make('until')->label('إلى'),
                    ])
                    ->query(fn (Builder $query, array $data): Builder => $query
                        ->when($data['from'] ?? null, fn (Builder $query, $date): Builder => $query->whereHas('details', fn (Builder $details): Builder => $details->whereDate('check_in', '>=', $date)))
                        ->when($data['until'] ?? null, fn (Builder $query, $date): Builder => $query->whereHas('details', fn (Builder $details): Builder => $details->whereDate('check_in', '<=', $date)))),
            ])
            ->modifyQueryUsing(fn (Builder $query): Builder => $query->withCount([
                'visas as visas_total_count',
                'visas as visas_issued_count' => fn (Builder $visas): Builder => $visas->where('status', VisaStatus::Issued->value),
            ]))
            ->recordActions([
                ViewAction::make()->label('عرض'),
                ...SimpleResourceTableActions::recordActions(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                    RestoreBulkAction::make(),
                ]),
            ])
            ->striped()
            ->defaultSort('id', 'desc')
            ->persistFiltersInSession()
            ->extraAttributes([
                'x-on:pointerdown' => <<<'JS'
                    const viewport = $event.target.closest('.fi-ta-content-ctn');
                    const isInteractive = $event.target.closest('button, a, input, select, textarea, label, [role="button"], [contenteditable="true"]');

                    if ($event.pointerType !== 'mouse' || $event.button !== 0 || isInteractive || !viewport || viewport.scrollWidth <= viewport.clientWidth) {
                        return;
                    }

                    $el.dataset.tablePanning = 'true';
                    $el.dataset.tablePanStartX = String($event.clientX);
                    $el.dataset.tablePanStartScroll = String(viewport.scrollLeft);
                    $el.dataset.tablePanDirection = getComputedStyle(viewport).direction;
                    $el.style.cursor = 'grabbing';
                    $el.style.userSelect = 'none';
                JS,
                'x-on:pointermove.window' => <<<'JS'
                    if ($el.dataset.tablePanning !== 'true') {
                        return;
                    }

                    const viewport = $el.querySelector('.fi-ta-content-ctn');
                    const deltaX = $event.clientX - Number($el.dataset.tablePanStartX);
                    const direction = $el.dataset.tablePanDirection === 'rtl' ? 1 : -1;

                    viewport.scrollLeft = Number($el.dataset.tablePanStartScroll) + (deltaX * direction);
                JS,
                'x-on:pointerup.window' => <<<'JS'
                    $el.dataset.tablePanning = 'false';
                    $el.style.removeProperty('cursor');
                    $el.style.removeProperty('user-select');
                JS,
                'x-on:pointercancel.window' => <<<'JS'
                    $el.dataset.tablePanning = 'false';
                    $el.style.removeProperty('cursor');
                    $el.style.removeProperty('user-select');
                JS,
            ])
            ->groups(['bookingType.name']);
    }
}
