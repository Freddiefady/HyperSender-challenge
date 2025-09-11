<?php

declare(strict_types=1);

namespace App\Filament\Resources\Vehicles\Pages;

use App\Filament\Resources\Vehicles\VehicleResource;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;

final class ViewVehicle extends ViewRecord
{
    protected static string $resource = VehicleResource::class;

    protected function getHeaderActions(): array
    {
        return [
            EditAction::make(),
        ];
    }
}
