<?php

namespace App\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasIcon;
use Filament\Support\Contracts\HasLabel;
use Filament\Support\Icons\Heroicon;

enum VisaStatus: string implements HasColor, HasIcon, HasLabel
{
    case Pending = 'pending';
    case Issued = 'issued';
    case Rejected = 'rejected';

    public function getLabel(): ?string
    {
        return match ($this) {
            self::Pending => 'قيد الاستخراج',
            self::Issued => 'تم استخراج التأشيرة',
            self::Rejected => 'مرفوضة',
        };
    }

    public function getColor(): string
    {
        return match ($this) {
            self::Pending => 'warning',
            self::Issued => 'success',
            self::Rejected => 'danger',
        };
    }

    public function getIcon(): Heroicon
    {
        return match ($this) {
            self::Pending => Heroicon::OutlinedClock,
            self::Issued => Heroicon::OutlinedCheckCircle,
            self::Rejected => Heroicon::OutlinedXCircle,
        };
    }
}
