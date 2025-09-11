<?php

declare(strict_types=1);

namespace App\Filament\Resources\Trips\Schemas;

use App\Enums\TripStatusEnum;
use App\Models\Trip;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

final class TripInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Trip Information')
                    ->schema([
                        TextEntry::make('trip_number'),
                        TextEntry::make('company.name')
                            ->label('Company'),
                        TextEntry::make('driver.name')
                            ->label('Driver'),
                        TextEntry::make('vehicle.display_name')
                            ->label('Vehicle'),
                        TextEntry::make('status')
                            ->badge(),
                    ])
                    ->columns(2),

                Section::make('Route Details')
                    ->schema([
                        TextEntry::make('origin'),
                        TextEntry::make('destination'),
                        TextEntry::make('distance_km')
                            ->label('Distance (km)')
                            ->suffix(' km'),
                    ])
                    ->columns(2),

                Section::make('Schedule')
                    ->schema([
                        TextEntry::make('scheduled_start')
                            ->dateTime(),
                        TextEntry::make('scheduled_end')
                            ->dateTime(),
                        TextEntry::make('scheduled_duration')
                            ->getStateUsing(fn (Trip $record): string => $record->scheduled_duration.' hours')
                            ->label('Scheduled Duration'),
                        TextEntry::make('actual_start')
                            ->dateTime()
                            ->placeholder('Not started'),
                        TextEntry::make('actual_end')
                            ->dateTime()
                            ->placeholder('Not completed'),
                        TextEntry::make('actual_duration')
                            ->getStateUsing(fn (Trip $record): ?string => $record->actual_duration ? $record->actual_duration.' hours' : null)
                            ->placeholder('Not completed')
                            ->label('Actual Duration'),
                    ])
                    ->columns(2),

                Section::make('Performance Data')
                    ->schema([
                        TextEntry::make('fuel_consumed')
                            ->suffix(' L')
                            ->placeholder('Not recorded'),
                        TextEntry::make('fuel_efficiency')
                            ->getStateUsing(fn (Trip $record): ?string => $record->fuel_efficiency ? $record->fuel_efficiency.' km/L' : null)
                            ->placeholder('Not calculated')
                            ->label('Fuel Efficiency'),
                    ])
                    ->columns(2)
                    ->visible(fn (Trip $record): bool => $record->status === TripStatusEnum::COMPLETED->value),

                Section::make('Additional Information')
                    ->schema([
                        TextEntry::make('notes')
                            ->placeholder('No notes')
                            ->columnSpanFull(),
                        TextEntry::make('created_at')
                            ->dateTime(),
                        TextEntry::make('updated_at')
                            ->dateTime(),
                    ])
                    ->columns(2),

            ]);
    }
}
