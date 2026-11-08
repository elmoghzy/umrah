<?php

namespace App\Filament\Resources\Users\Tables;

use App\Models\User;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class UsersTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')->label('الاسم')->searchable(),
                TextColumn::make('email')->label('البريد الإلكتروني')->searchable(),
                TextColumn::make('job')->label('الوظيفة')->searchable(),
                TextColumn::make('role')
                    ->label('نوع المستخدم')
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        User::ROLE_MAIN_ADMIN => 'مدير',
                        User::ROLE_BRANCH_EMPLOYEE => 'مستخدم',
                        default => $state,
                    }),
                TextColumn::make('broker.name')->label('الفرع')->placeholder('الفرع الرئيسي')->searchable(),
                IconColumn::make('is_active')->label('نشط')->boolean(),
            ])
            ->filters([
                //
            ])
            ->recordActions([
                EditAction::make()->label('تعديل'),
                DeleteAction::make()->label('حذف'),
            ])
            ->striped()
            ->defaultSort('id', 'desc')
            ->modifyQueryUsing(fn (Builder $query): Builder => $query->with(['broker:id,name']))
            ->deferLoading(app()->isProduction())
            ->paginated([10, 25, 50])
            ->persistSearchInSession()
            ->persistSortInSession()
            ->searchDebounce('500ms');
    }
}
