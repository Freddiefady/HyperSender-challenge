<?php

declare(strict_types=1);

namespace App\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;

enum VehicleTypeEnum: string implements HasColor, HasLabel
{
    case CAR = 'car';
    case BUS = 'bus';
    case TRUCK = 'truck';
    case MOTORCYCLE = 'motorcycle';
    case VAN = 'van';

    public function getLabel(): string
    {
        return $this->value;
    }

    public function getColor(): string
    {
        return match ($this) {
            self::CAR => 'success',
            self::VAN => 'info',
            self::BUS => 'wheat',
            self::TRUCK => 'orrange',
            self::MOTORCYCLE => 'gray',
            default => 'danger'
        };
    }
}
