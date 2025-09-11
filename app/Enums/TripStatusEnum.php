<?php

declare(strict_types=1);

namespace App\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;

enum TripStatusEnum: string implements HasColor, HasLabel
{
    case SCHEDULED = 'Scheduled';

    case IN_PROGRESS = 'In progress';

    case COMPLETED = 'Completed';

    case CANCELLED = 'Cancelled';

    public function getLabel(): string
    {
        return $this->value;
    }

    public function getColor(): string
    {
        return match ($this) {
            self::SCHEDULED => 'info',
            self::IN_PROGRESS => 'primary',
            self::COMPLETED => 'success',
            self::CANCELLED => 'danger',
            default => 'gray'
        };
    }
}
