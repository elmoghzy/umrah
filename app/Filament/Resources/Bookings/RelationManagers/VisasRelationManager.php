<?php

namespace App\Filament\Resources\Bookings\RelationManagers;

use App\Filament\Resources\Visas\Tables\VisaRecordActions;
use App\Models\Booking;
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
            ->columns([
                TextColumn::make('customer.name_ar')
                    ->label('المعتمر')
                    ->searchable(),
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
            ->modifyQueryUsing(fn (Builder $query): Builder => $query->with(['customer', 'latestEvent.user']))
            ->defaultSort('id', 'asc');
    }

    public static function canViewForRecord(Model $ownerRecord, string $pageClass): bool
    {
        return $ownerRecord instanceof Booking && Gate::allows('view', $ownerRecord);
    }
}
