<?php

declare(strict_types=1);

namespace App\Filament\Resources\Vehicles\Schemas;

use App\Models\Vehicle;
use Filament\Infolists\Components\IconEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

final class VehicleInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Vehicle Information')
                    ->schema([
                        TextEntry::make('company.name')
                            ->label('Company'),
                        TextEntry::make('drivers.name')
                            ->label('Driver'),
                        TextEntry::make('brand'),
                        TextEntry::make('model'),
                        TextEntry::make('year'),
                        TextEntry::make('vehicle_type')
                            ->badge(),
                        IconEntry::make('is_active')
                            ->boolean()
                            ->label('Active'),
                    ])
                    ->columns(2),

                Section::make('Registration Details')
                    ->schema([
                        TextEntry::make('license_plate'),
                        TextEntry::make('vin')
                            ->label('VIN'),
                        TextEntry::make('created_at')
                            ->dateTime(),
                        TextEntry::make('updated_at')
                            ->dateTime(),
                    ])
                    ->columns(2),

                Section::make('Specifications')
                    ->schema([
                        TextEntry::make('capacity_kg')
                            ->label('Capacity (kg)')
                            ->numeric(),
                        TextEntry::make('fuel_capacity')
                            ->label('Fuel Capacity (L)')
                            ->numeric(),
                    ])
                    ->columns(2),

                Section::make('Statistics')
                    ->schema([
                        TextEntry::make('total_trips')
                            ->getStateUsing(fn (Vehicle $record): int => $record->total_trips)
                            ->label('Total Trips'),
                        TextEntry::make('completed_trips')
                            ->getStateUsing(fn (Vehicle $record): int => $record->completed_trips)
                            ->label('Completed Trips'),
                        TextEntry::make('active_trips')
                            ->getStateUsing(fn (Vehicle $record): int => $record->trips()->active()->count())
                            ->label('Active Trips'),
                        TextEntry::make('drivers_count')
                            ->getStateUsing(fn (Vehicle $record): int => $record->drivers()->count())
                            ->label('Drivers Used'),
                    ])
                    ->columns(2),
            ]);
    }
}
