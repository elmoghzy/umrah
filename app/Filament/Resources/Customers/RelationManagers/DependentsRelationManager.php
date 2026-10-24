<?php

namespace App\Filament\Resources\Customers\RelationManagers;

use App\Actions\PreventForceDeleteWithRelations;
use Filament\Actions\AssociateAction;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\DissociateAction;
use Filament\Actions\DissociateBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ForceDeleteAction;
use Filament\Actions\RestoreAction;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class DependentsRelationManager extends RelationManager
{
    protected static string $relationship = 'dependents';

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('name_ar')
                    ->label('الاسم بالعربية')
                    ->required(),
                TextInput::make('name_en')
                    ->label('الاسم بالإنجليزية'),
                TextInput::make('phone')
                    ->label('الهاتف')
                    ->tel(),
                Textarea::make('note')
                    ->label('ملاحظات')
                    ->columnSpanFull(),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('name_ar')
            ->columns([
                TextColumn::make('name_ar')
                    ->label('الاسم بالعربية')
                    ->searchable(),
                TextColumn::make('name_en')
                    ->label('الاسم بالإنجليزية')
                    ->searchable(),
                TextColumn::make('phone')
                    ->label('الهاتف')
                    ->searchable(),
                TextColumn::make('deleted_at')
                    ->label('تاريخ الحذف')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('created_at')
                    ->label('تاريخ الإضافة')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('updated_at')
                    ->label('آخر تحديث')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                TrashedFilter::make(),
            ])
            ->headerActions([
                CreateAction::make(),
                AssociateAction::make(),
            ])
            ->recordActions([
                EditAction::make(),
                DissociateAction::make(),
                DeleteAction::make(),
                ForceDeleteAction::make()
                    ->before(function (Model $record): void {
                        PreventForceDeleteWithRelations::handle($record);
                    }),
                RestoreAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DissociateBulkAction::make(),
                    DeleteBulkAction::make(),
                ]),
            ])
            ->striped()
            ->defaultSort('id', 'desc')
            ->persistFiltersInSession()
            ->modifyQueryUsing(fn (Builder $query) => $query
                ->withoutGlobalScopes([
                    SoftDeletingScope::class,
                ]));
    }
}
