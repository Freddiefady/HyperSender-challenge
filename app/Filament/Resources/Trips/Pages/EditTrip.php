<?php

declare(strict_types=1);

namespace App\Filament\Resources\Trips\Pages;

use App\Filament\Resources\Trips\TripResource;
use App\Models\Driver;
use App\Models\Vehicle;
use App\Services\TripValidationService;
use Carbon\Carbon;
use Filament\Actions\DeleteAction;
use Filament\Actions\ViewAction;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;

final class EditTrip extends EditRecord
{
    protected static string $resource = TripResource::class;

    protected function getHeaderActions(): array
    {
        return [
            ViewAction::make(),
            DeleteAction::make(),
        ];
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        // Only validate if schedule times or resources changed
        $originalRecord = $this->getRecord();

        $scheduleChanged = $data['scheduled_start'] !== $originalRecord->scheduled_start ||
            $data['scheduled_end'] !== $originalRecord->scheduled_end;
        $resourcesChanged = $data['driver_id'] !== $originalRecord->driver_id ||
            $data['vehicle_id'] !== $originalRecord->vehicle_id;

        if ($scheduleChanged || $resourcesChanged) {
            $validationService = app(TripValidationService::class);

            $driver = Driver::find($data['driver_id']);
            $vehicle = Vehicle::find($data['vehicle_id']);

            if ($driver && $vehicle) {
                $validation = $validationService->validateTrip(
                    $driver,
                    $vehicle,
                    Carbon::parse($data['scheduled_start']),
                    Carbon::parse($data['scheduled_end']),
                    $originalRecord // Exclude current trip from validation
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

                    $this->halt();
                }

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
        }

        return $data;
    }

    protected function getSavedNotification(): ?Notification
    {
        return Notification::make()
            ->success()
            ->title('Trip updated successfully')
            ->body('All changes have been saved and validations passed.');
    }
}
