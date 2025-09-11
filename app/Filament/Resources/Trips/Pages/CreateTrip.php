<?php

declare(strict_types=1);

namespace App\Filament\Resources\Trips\Pages;

use App\Filament\Resources\Trips\TripResource;
use App\Models\Driver;
use App\Models\Vehicle;
use App\Services\TripValidationService;
use Carbon\Carbon;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\CreateRecord;

final class CreateTrip extends CreateRecord
{
    protected static string $resource = TripResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        // Validate the trip before creation
        $validationService = app(TripValidationService::class);

        $driver = Driver::find($data['driver_id']);
        $vehicle = Vehicle::find($data['vehicle_id']);

        if ($driver && $vehicle) {
            $validation = $validationService->validateTrip(
                $driver,
                $vehicle,
                Carbon::parse($data['scheduled_start']),
                Carbon::parse($data['scheduled_end'])
            );

            if (! $validation['valid']) {
                foreach ($validation['errors'] as $error) {
                    Notification::make()
                        ->title('Validation Error')
                        ->body($error['message'])
                        ->danger()
                        ->persistent()
                        ->send();
                }

                // Stop the creation process
                $this->halt();
            }

            // Show warnings but allow creation
            if (! empty($validation['warnings'])) {
                foreach ($validation['warnings'] as $warning) {
                    Notification::make()
                        ->title('Warning')
                        ->body($warning['message'])
                        ->warning()
                        ->send();
                }
            }
        }

        return $data;
    }

    protected function getCreatedNotification(): ?Notification
    {
        return Notification::make()
            ->success()
            ->title('Trip created successfully')
            ->body('The trip has been scheduled and all validations passed.');
    }
}
