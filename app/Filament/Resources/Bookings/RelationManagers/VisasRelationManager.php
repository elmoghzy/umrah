<?php

namespace App\Filament\Resources\Bookings\RelationManagers;

use App\Enums\VisaStatus;
use App\Filament\Resources\Visas\Tables\VisaRecordActions;
use App\Models\Booking;
use App\Models\Visa;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Gate;

class VisasRelationManager extends RelationManager
{
    protected static string $relationship = 'visas';

    protected static ?string $title = 'التأشيرات';

    protected static string|\BackedEnum|null $icon = Heroicon::OutlinedIdentification;

    public function form(Schema $schema): Schema
    {
        return $schema->components([]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('customer.name_ar')
            ->description(function (): string {
                $booking = $this->getOwnerRecord();
                $booking->loadCount([
                    'visas',
                    'visas as issued_visas_count' => fn (Builder $query): Builder => $query->where('status', VisaStatus::Issued->value),
                ]);

                return $booking->issued_visas_count.' من '.$booking->visas_count.' مستخرجة';
            })
            ->columns([
                TextColumn::make('customer.name_ar')
                    ->label('المعتمر')
                    ->searchable(),
                TextColumn::make('relation')
                    ->label('الصفة')
                    ->state(fn (Visa $record): string => $record->customer_id === $record->booking?->customer_id ? 'ولي الأمر' : 'مرافق')
                    ->badge()
                    ->color(fn (Visa $record): string => $record->customer_id === $record->booking?->customer_id ? 'primary' : 'gray'),
                TextColumn::make('status')
                    ->label('الحالة')
                    ->badge(),
                TextColumn::make('issued_at')
                    ->label('تاريخ الاستخراج')
                    ->dateTime('d/m/Y H:i')
                    ->placeholder('—'),
                TextColumn::make('downloads_count')
                    ->label('عدد التحميلات')
                    ->numeric(),
                TextColumn::make('latestEvent.user.name')
                    ->label('آخر من حدّث')
                    ->placeholder('—'),
            ])
            ->recordActions([
                VisaRecordActions::updateStatus(),
                VisaRecordActions::replaceFile(),
                VisaRecordActions::file(),
                VisaRecordActions::history(),
            ])
            ->modifyQueryUsing(fn (Builder $query): Builder => $query
                ->with(['customer', 'booking', 'latestEvent.user'])
                ->orderBy(
                    Visa::query()
                        ->select('id')
                        ->whereColumn('id', 'visas.id')
                        ->where('customer_id', $this->getOwnerRecord()->customer_id),
                    'desc',
                )
                ->orderBy('id', 'asc'));
    }

    public static function canViewForRecord(Model $ownerRecord, string $pageClass): bool
    {
        return $ownerRecord instanceof Booking && Gate::allows('view', $ownerRecord);
    }
}
