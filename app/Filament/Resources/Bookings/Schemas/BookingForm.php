<?php

namespace App\Filament\Resources\Bookings\Schemas;

use App\Actions\Customers\CreateGuardianAction;
use App\Enums\StayScope;
use App\Models\Booking;
use App\Models\City;
use App\Models\Company;
use App\Models\Customer;
use App\Models\Hotel;
use App\Models\Meal;
use App\Models\RoomType;
use App\Models\Transport;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Repeater\TableColumn;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\TimePicker;
use Filament\Forms\Components\ToggleButtons;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Tabs;
use Filament\Schemas\Components\Tabs\Tab;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Number;

class BookingForm
{
    /** @return array{transfers: array<int, array<string, mixed>>} */
    public static function transferState(Booking $booking): array
    {
        return [
            'transfers' => $booking->transfers->map(fn ($transfer): array => [
                'transport_id' => $transfer->transport_id,
                'from_city_id' => $transfer->from_city_id,
                'to_city_id' => $transfer->to_city_id,
                'travel_date' => $transfer->travel_date?->toDateString(),
                'travel_time' => $transfer->travel_time ? Carbon::parse($transfer->travel_time)->format('H:i') : null,
                'price' => $transfer->price,
                'note' => $transfer->note,
            ])->all(),
        ];
    }

    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Tabs::make('booking_tabs')
                    ->tabs([
                        self::mainTab(),
                        self::stayTab(),
                        self::roomsTab(),
                        self::transfersTab(),
                        self::financeTab(),
                    ])
                    ->persistTabInQueryString()
                    ->columnSpanFull(),
            ]);
    }

    protected static function mainTab(): Tab
    {
        return Tab::make('بيانات الحجز')
            ->icon(Heroicon::OutlinedClipboardDocumentList)
            ->schema([
                Section::make()->schema([
                    Select::make('customer_id')
                        ->label('ولي الأمر')
                        ->relationship('customer', 'name_ar', modifyQueryUsing: fn (Builder $query): Builder => $query->whereNull('guardian_id'))
                        ->searchable()
                        ->searchDebounce(300)
                        ->optionsLimit(20)
                        ->live()
                        ->required()
                        ->createOptionForm([
                            TextInput::make('name_ar')->label('اسم ولي الأمر')->required(),
                            TextInput::make('phone')->label('الهاتف')->tel(),
                        ])
                        ->createOptionUsing(fn (array $data): int => app(CreateGuardianAction::class)->handle($data)->getKey())
                        ->afterStateUpdated(function (?int $state, Set $set): void {
                            $dependents = $state
                                ? Customer::query()
                                    ->where('guardian_id', $state)
                                    ->orderBy('id')
                                    ->get(['name_ar', 'name_en', 'phone'])
                                    ->map(fn (Customer $dependent): array => [
                                        'name_ar' => $dependent->name_ar,
                                        'name_en' => $dependent->name_en,
                                        'phone' => $dependent->phone,
                                    ])
                                    ->all()
                                : [];

                            $set('guardian_dependents', $dependents);
                        }),
                    TextInput::make('booking_number')
                        ->label('رقم الحجز')
                        ->placeholder('يُولَّد عند الحفظ')
                        ->prefixIcon(Heroicon::OutlinedHashtag)
                        ->disabled()
                        ->dehydrated(false),
                    Select::make('booking_type_id')->label('نوع الحجز')->relationship('bookingType', 'name')->searchable()->preload(),
                    Select::make('broker_id')
                        ->label('الفرع')
                        ->relationship('broker', 'name')
                        ->searchable()
                        ->preload()
                        ->visible(fn (): bool => auth()->user()?->isMainAdmin() ?? false),
                    Placeholder::make('assigned_branch')
                        ->label('الفرع')
                        ->content(fn (): string => auth()->user()?->broker?->name ?? 'الفرع غير محدد')
                        ->visible(fn (): bool => auth()->user() !== null && ! auth()->user()->isMainAdmin()),
                    Select::make('airplane_id')->label('الطيران')->relationship('airplane', 'name')->searchable()->preload(),
                ])->columns(2),
                Section::make('المعتمرون التابعون')
                    ->schema([
                        Repeater::make('guardian_dependents')
                            ->hiddenLabel()
                            ->table([
                                TableColumn::make('اسم المعتمر'),
                                TableColumn::make('الاسم بالإنجليزية'),
                                TableColumn::make('الهاتف'),
                            ])
                            ->schema([
                                TextInput::make('name_ar')
                                    ->disabled()
                                    ->dehydrated(false),
                                TextInput::make('name_en')
                                    ->disabled()
                                    ->dehydrated(false),
                                TextInput::make('phone')
                                    ->disabled()
                                    ->dehydrated(false),
                            ])
                            ->defaultItems(0)
                            ->addable(false)
                            ->deletable(false)
                            ->reorderable(false)
                            ->dehydrated(false),
                    ]),
                Section::make('نطاق الإقامة')
                    ->description('اختر المدن اللي هتتضمنها الإقامة، وهتظهر بيانات الفنادق حسب اختيارك.')
                    ->schema([
                        ToggleButtons::make('stay_scope')
                            ->hiddenLabel()
                            ->options(StayScope::class)
                            ->grouped()
                            ->default(StayScope::Both)
                            ->live()
                            ->disabledOn('edit')
                            ->required(),
                    ]),
                Section::make('ملاحظات')
                    ->collapsible()
                    ->schema([
                        Textarea::make('notes')->hiddenLabel()->rows(3),
                    ]),
            ]);
    }

    protected static function stayTab(): Tab
    {
        return Tab::make('الإقامة')
            ->icon(Heroicon::OutlinedBuildingOffice2)
            ->schema([
                self::detailSection('فندق المدينة', 2, 'madina'),
                self::detailSection('فندق مكة', 1, 'makka'),
            ]);
    }

    protected static function roomsTab(): Tab
    {
        return Tab::make('الغرف')
            ->icon(Heroicon::OutlinedHomeModern)
            ->badge(fn (Get $get): ?int => count($get('rooms') ?? []) ?: null)
            ->schema([
                Section::make()->schema([
                    Repeater::make('rooms')
                        ->hiddenLabel()
                        ->schema([
                            Select::make('room_type_id')
                                ->label('نوع الغرفة')
                                ->options(RoomType::query()->orderBy('size')->pluck('name', 'id'))
                                ->searchable()
                                ->preload()
                                ->live()
                                ->required()
                                ->afterStateUpdated(function (?int $state, Get $get, Set $set): void {
                                    $roomType = $state ? RoomType::query()->find($state) : null;
                                    $set('price', $roomType?->price ?? 0);
                                }),
                            TextInput::make('price')
                                ->label('السعر')
                                ->numeric()
                                ->suffix(config('umrah.currency_symbol'))
                                ->default(0)
                                ->live(),
                            Placeholder::make('total_price')
                                ->label('الإجمالي')
                                ->content(function (Get $get): string {
                                    $roomType = RoomType::query()->find($get('room_type_id'));

                                    return self::formatCurrency((float) ($get('price') ?? 0) * (int) ($roomType?->size ?? 0));
                                }),
                        ])
                        ->columns(3)
                        ->defaultItems(1)
                        ->addActionLabel('إضافة غرفة'),
                ]),
            ]);
    }

    protected static function financeTab(): Tab
    {
        return Tab::make('الخدمات والمالية')
            ->icon(Heroicon::OutlinedBanknotes)
            ->schema([
                Section::make('الخدمات الإضافية')->schema([
                    TextInput::make('services_note')->label('بيان الخدمة')->maxLength(500),
                    TextInput::make('service_amount')->label('قيمة الخدمة')->numeric()->suffix(config('umrah.currency_symbol'))->default(0)->live(),
                ])->columns(2),
                Section::make('البيانات المالية')->schema([
                    Placeholder::make('financial_total')->label('إجمالي الحجز')->content(fn (Get $get): string => self::formatCurrency(self::roomsTotal($get('rooms') ?? []))),
                    Placeholder::make('transport_amount')->label('المواصلات')->content(fn (Get $get): string => self::formatCurrency(self::transfersTotal($get('transfers') ?? []))),
                    TextInput::make('discount')->label('التخفيض')->numeric()->suffix(config('umrah.currency_symbol'))->default(0)->live(),
                    Placeholder::make('net_amount')->label('الصافي')->content(fn (Get $get): string => self::formatCurrency(self::roomsTotal($get('rooms') ?? []) + (float) ($get('service_amount') ?? 0) + self::transfersTotal($get('transfers') ?? []) - (float) ($get('discount') ?? 0))),
                ])->columns(4),
            ]);
    }

    protected static function transfersTab(): Tab
    {
        return Tab::make('المواصلات')
            ->icon(Heroicon::OutlinedTruck)
            ->schema([
                Section::make('رحلات المواصلات')
                    ->description('إضافة رحلات المواصلات اختيارية، ويمكن إضافة أكثر من رحلة للحجز.')
                    ->schema([
                        Repeater::make('transfers')
                            ->hiddenLabel()
                            ->table([
                                TableColumn::make('وسيلة المواصلات')->width('140px'),
                                TableColumn::make('من')->width('140px'),
                                TableColumn::make('إلى')->width('140px'),
                                TableColumn::make('التاريخ')->width('140px'),
                                TableColumn::make('الوقت')->width('115px'),
                                TableColumn::make('السعر')->width('120px'),
                            ])
                            ->schema([
                                Select::make('transport_id')
                                    ->label('الوسيلة')
                                    ->options(fn (): array => self::transportOptions())
                                    ->searchable()
                                    ->required(),
                                Select::make('from_city_id')
                                    ->label('من')
                                    ->options(fn (): array => self::cityOptions())
                                    ->searchable()
                                    ->required(),
                                Select::make('to_city_id')
                                    ->label('إلى')
                                    ->options(fn (): array => self::cityOptions())
                                    ->searchable()
                                    ->required()
                                    ->different('from_city_id')
                                    ->validationMessages([
                                        'different' => 'مدينة الوصول يجب أن تختلف عن مدينة المغادرة.',
                                    ]),
                                DatePicker::make('travel_date')
                                    ->label('التاريخ')
                                    ->displayFormat('d/m/Y'),
                                TimePicker::make('travel_time')
                                    ->label('الوقت')
                                    ->seconds(false),
                                TextInput::make('price')
                                    ->label('السعر')
                                    ->numeric()
                                    ->suffix(config('umrah.currency_symbol'))
                                    ->minValue(0)
                                    ->default(0)
                                    ->live(onBlur: true),
                                Hidden::make('note'),
                            ])
                            ->defaultItems(0)
                            ->addActionLabel('إضافة رحلة مواصلات'),
                    ]),
            ]);
    }

    protected static function detailSection(string $label, int $cityId, string $prefix): Section
    {
        return Section::make($label)
            ->icon($cityId === 2 ? Heroicon::OutlinedBuildingLibrary : Heroicon::OutlinedBuildingOffice)
            ->visible(fn (Get $get): bool => self::scopeIncludes($get('stay_scope'), $cityId))
            ->schema([
                Hidden::make($prefix.'_city_id')->default($cityId),
                Select::make($prefix.'_hotel_id')
                    ->label('الفندق')
                    ->options(Hotel::query()->where('city_id', $cityId)->pluck('name_ar', 'id'))
                    ->searchable()
                    ->preload()
                    ->required(),
                Select::make($prefix.'_company_id')->label('الشركة')->options(Company::query()->pluck('name', 'id'))->searchable()->preload()->required(),
                Select::make($prefix.'_meal_id')->label('الوجبة')->options(Meal::query()->pluck('name', 'id'))->searchable()->preload()->required(),
                DatePicker::make($prefix.'_check_in')
                    ->label('تاريخ الدخول')
                    ->live()
                    ->afterStateUpdated(function (?string $state, Get $get, Set $set) use ($prefix): void {
                        $checkOut = $get($prefix.'_check_out');
                        if ($state && $checkOut && Carbon::parse($checkOut)->greaterThanOrEqualTo(Carbon::parse($state))) {
                            $set($prefix.'_nights', Carbon::parse($state)->diffInDays(Carbon::parse($checkOut)));
                        }
                    })
                    ->required(),
                DatePicker::make($prefix.'_check_out')
                    ->label('تاريخ الخروج')
                    ->afterOrEqual($prefix.'_check_in')
                    ->live()
                    ->afterStateUpdated(function (?string $state, Get $get, Set $set) use ($prefix): void {
                        $checkIn = $get($prefix.'_check_in');
                        if ($state && $checkIn && Carbon::parse($state)->greaterThanOrEqualTo(Carbon::parse($checkIn))) {
                            $set($prefix.'_nights', Carbon::parse($checkIn)->diffInDays(Carbon::parse($state)));
                        }
                    })
                    ->required(),
                TextInput::make($prefix.'_nights')
                    ->label('عدد الليالي')
                    ->numeric()
                    ->minValue(1)
                    ->live()
                    ->afterStateUpdated(function (?int $state, Get $get, Set $set) use ($prefix): void {
                        $checkIn = $get($prefix.'_check_in');
                        if ($checkIn && $state) {
                            $set($prefix.'_check_out', Carbon::parse($checkIn)->addDays($state)->toDateString());
                        }
                    })
                    ->dehydrated(false),
            ])->columns(3);
    }

    protected static function scopeIncludes(mixed $scope, int $cityId): bool
    {
        $scope = $scope instanceof StayScope ? $scope->value : (int) $scope;

        return $scope === 0 || ($scope === 1 && $cityId === 2) || ($scope === 2 && $cityId === 1);
    }

    protected static function roomsTotal(array $rooms): float
    {
        return collect($rooms)->sum(function (array $room): float {
            $roomType = RoomType::query()->find($room['room_type_id'] ?? null);

            return round((float) ($room['price'] ?? 0) * (int) ($roomType?->size ?? 0), 2);
        });
    }

    protected static function transfersTotal(array $transfers): float
    {
        return round(collect($transfers)->sum(fn (array $transfer): float => (float) ($transfer['price'] ?? 0)), 2);
    }

    protected static function formatCurrency(float|int|string|null $amount): string
    {
        return Number::currency(
            (float) ($amount ?? 0),
            config('umrah.currency_code'),
            config('umrah.currency_locale'),
            (int) config('umrah.currency_decimal_places'),
        );
    }

    /** @return array<int, string> */
    protected static function transportOptions(): array
    {
        $request = request();
        $cacheKey = 'booking_form.transport_options';

        if (! $request->attributes->has($cacheKey)) {
            $request->attributes->set($cacheKey, Transport::query()->orderBy('name')->pluck('name', 'id')->all());
        }

        return $request->attributes->get($cacheKey);
    }

    /** @return array<int, string> */
    protected static function cityOptions(): array
    {
        $request = request();
        $cacheKey = 'booking_form.city_options';

        if (! $request->attributes->has($cacheKey)) {
            $request->attributes->set($cacheKey, City::query()->orderBy('name')->pluck('name', 'id')->all());
        }

        return $request->attributes->get($cacheKey);
    }
}
