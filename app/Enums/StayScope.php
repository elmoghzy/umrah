<?php

namespace App\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasIcon;
use Filament\Support\Contracts\HasLabel;
use Filament\Support\Icons\Heroicon;

enum StayScope: int implements HasColor, HasIcon, HasLabel
{
    case Both = 0;
    case MadinaOnly = 1;
    case MakkaOnly = 2;

    public function getLabel(): ?string
    {
        return match ($this) {
            self::Both => 'مكة والمدينة',
            self::MadinaOnly => 'المدينة فقط',
            self::MakkaOnly => 'مكة فقط',
        };
    }

    public function getColor(): string
    {
        return match ($this) {
            self::Both => 'success',
            self::MadinaOnly => 'info',
            self::MakkaOnly => 'warning',
        };
    }

    public function getIcon(): Heroicon
    {
        return match ($this) {
            self::Both => Heroicon::OutlinedMap,
            self::MadinaOnly => Heroicon::OutlinedBuildingLibrary,
            self::MakkaOnly => Heroicon::OutlinedBuildingOffice,
        };
    }
}
