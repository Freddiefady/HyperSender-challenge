<?php

declare(strict_types=1);

namespace App\Filament\Resources\Trips\Schemas;

use App\Enums\TripStatusEnum;
use App\Models\Company;
use App\Models\Driver;
use App\Models\Vehicle;
use App\Services\TripValidationService;
use Carbon\Carbon;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;

final class TripForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Trip Details')
                    ->schema([
                        Select::make('company_id')
                            ->label('Company')
                            ->options(Company::pluck('name', 'id'))
                            ->required()
                            ->live()
                            ->afterStateUpdated(function (Set $set) {
                                $set('driver_id', null);
                                $set('vehicle_id', null);
                            }),
                        TextInput::make('trip_number')
                            ->label('Trip Number')
                            ->disabled()
                            ->dehydrated(false),
                        Select::make('driver_id')
                            ->label('Driver')
                            ->options(function (Get $get) {
                                $companyId = $get('company_id');
                                if (! $companyId) {
                                    return [];
                                }

                                return Driver::where('company_id', $companyId)
                                    ->active()
                                    ->pluck('name', 'id');
                            })
                            ->required()
                            ->searchable()
                            ->live(),
                        Select::make('vehicle_id')
                            ->label('Vehicle')
                            ->options(function (Get $get) {
                                $companyId = $get('company_id');
                                if (! $companyId) {
                                    return [];
                                }

                                return Vehicle::where('company_id', $companyId)
                                    ->active()
                                    ->withCount('drivers')
                                    ->get()
                                    ->mapWithKeys(function ($vehicle) {
                                        $driverCount = $vehicle->drivers_count;
                                        $label = $driverCount > 0
                                            ? "{$vehicle->display_name} ({$driverCount} drivers)"
                                            : "{$vehicle->display_name} (Unused)";

                                        return [$vehicle->id => $label];
                                    });
                            })
                            ->required()
                            ->searchable()
                            ->live()
                            ->helperText('Vehicles can be operated by multiple drivers'),
                    ])
                    ->columns(2),

                Section::make('Route Information')
                    ->schema([
                        TextInput::make('origin')
                            ->required()
                            ->maxLength(255),
                        TextInput::make('destination')
                            ->required()
                            ->maxLength(255),
                        DateTimePicker::make('scheduled_start')
                            ->required()
                            ->live()
                            ->after('now'),
                        DateTimePicker::make('scheduled_end')
                            ->required()
                            ->live()
                            ->after('scheduled_start')
                            ->afterStateUpdated(function (Get $get, $state, $operation) {
                                if ($operation === 'create' || $operation === 'edit') {
                                    self::validateTripTimes($get, $state);
                                }
                            }),
                    ])
                    ->columns(2),

                Section::make('Status & Details')
                    ->schema([
                        Select::make('status')
                            ->options(TripStatusEnum::class)
                            ->default(TripStatusEnum::SCHEDULED->value)
                            ->live(),
                        TextInput::make('distance_km')
                            ->label('Distance (km)')
                            ->numeric()
                            ->step(0.01),
                        DateTimePicker::make('actual_start')
                            ->visible(fn (Get $get): bool => in_array($get('status'), [TripStatusEnum::IN_PROGRESS->value, TripStatusEnum::COMPLETED->value]))
                            ->required(fn (Get $get): bool => in_array($get('status'), [TripStatusEnum::IN_PROGRESS->value, TripStatusEnum::COMPLETED->value])),
                        DateTimePicker::make('actual_end')
                            ->visible(fn (Get $get): bool => $get('status') === TripStatusEnum::COMPLETED->value)
                            ->required(fn (Get $get): bool => $get('status') === TripStatusEnum::COMPLETED->value)
                            ->after('actual_start'),
                        TextInput::make('fuel_consumed')
                            ->label('Fuel Consumed (L)')
                            ->numeric()
                            ->step(0.01)
                            ->visible(fn (Get $get): bool => $get('status') === TripStatusEnum::COMPLETED->value),
                        Textarea::make('notes')
                            ->rows(3)
                            ->columnSpanFull(),
                    ])
                    ->columns(2),
            ]);
    }

    private static function validateTripTimes(Get $get, $scheduledEnd): void
    {
        $driverId = $get('driver_id');
        $vehicleId = $get('vehicle_id');
        $scheduledStart = $get('scheduled_start');

        if (! $driverId || ! $vehicleId || ! $scheduledStart || ! $scheduledEnd) {
            return;
        }

        $validationService = app(TripValidationService::class);
        $driver = Driver::find($driverId);
        $vehicle = Vehicle::find($vehicleId);

        if (! $driver || ! $vehicle) {
            return;
        }

        $validation = $validationService->validateTrip(
            $driver,
            $vehicle,
            Carbon::parse($scheduledStart),
            Carbon::parse($scheduledEnd)
        );

        if (! $validation['valid']) {
            foreach ($validation['errors'] as $error) {
                Notification::make()
                    ->title('Scheduling Conflict')
                    ->body($error['message'])
                    ->danger()
                    ->send();
            }
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
