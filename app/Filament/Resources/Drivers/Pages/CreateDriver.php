<?php

declare(strict_types=1);

namespace App\Filament\Resources\Drivers\Pages;

use App\Filament\Resources\Drivers\DriverResource;
use Filament\Resources\Pages\CreateRecord;

final class CreateDriver extends CreateRecord
{
    protected static string $resource = DriverResource::class;
}
