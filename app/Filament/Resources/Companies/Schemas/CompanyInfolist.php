<?php

declare(strict_types=1);

namespace App\Filament\Resources\Companies\Schemas;

use App\Models\Company;
use Filament\Infolists\Components\IconEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

final class CompanyInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Company Information')
                    ->schema([
                        TextEntry::make('name'),
                        TextEntry::make('email'),
                        TextEntry::make('phone'),
                        TextEntry::make('address')
                            ->columnSpanFull(),
                    ])
                    ->columns(2),

                Section::make('Registration Details')
                    ->schema([
                        TextEntry::make('registration_number')
                            ->label('Registration Number'),
                        IconEntry::make('is_active')
                            ->boolean()
                            ->label('Active'),
                        TextEntry::make('created_at')
                            ->dateTime(),
                        TextEntry::make('updated_at')
                            ->dateTime(),
                    ])
                    ->columns(2),

                Section::make('Statistics')
                    ->schema([
                        TextEntry::make('drivers_count')
                            ->getStateUsing(fn (Company $record): int => $record->drivers()->count())
                            ->label('Total Drivers'),
                        TextEntry::make('active_drivers_count')
                            ->getStateUsing(fn (Company $record): int => $record->activeDrivers()->count())
                            ->label('Active Drivers'),
                        TextEntry::make('vehicles_count')
                            ->getStateUsing(fn (Company $record): int => $record->vehicles()->count())
                            ->label('Total Vehicles'),
                        TextEntry::make('active_vehicles_count')
                            ->getStateUsing(fn (Company $record): int => $record->activeVehicles()->count())
                            ->label('Active Vehicles'),
                        TextEntry::make('total_trips_count')
                            ->getStateUsing(fn (Company $record): int => $record->total_trips_count)
                            ->label('Total Trips'),
                        TextEntry::make('active_trips_count')
                            ->getStateUsing(fn (Company $record): int => $record->active_trips_count)
                            ->label('Active Trips'),
                    ])
                    ->columns(3),
            ]);
    }
}
