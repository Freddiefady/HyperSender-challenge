<?php

declare(strict_types=1);

namespace App\Filament\Resources\Drivers\Schemas;

use App\Models\Driver;
use Filament\Infolists\Components\IconEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

final class DriverInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Personal Information')
                    ->schema([
                        TextEntry::make('name'),
                        TextEntry::make('company.name')
                            ->label('Company'),
                        TextEntry::make('email'),
                        TextEntry::make('phone'),
                    ])
                    ->columns(2),

                Section::make('License Information')
                    ->schema([
                        TextEntry::make('license_number'),
                        TextEntry::make('license_expiry')
                            ->date()
                            ->color(fn (Driver $record): string => match (true) {
                                $record->is_license_expired => 'danger',
                                $record->is_license_expiring_soon => 'warning',
                                default => 'success',
                            }),
                        IconEntry::make('is_active')
                            ->boolean()
                            ->label('Active'),
                        TextEntry::make('created_at')
                            ->dateTime(),
                    ])
                    ->columns(2),

                Section::make('Statistics')
                    ->schema([
                        TextEntry::make('total_trips')
                            ->getStateUsing(fn (Driver $record): int => $record->trips()->count())
                            ->label('Total Trips'),
                        TextEntry::make('completed_trips')
                            ->getStateUsing(fn (Driver $record): int => $record->trips()->completed()->count())
                            ->label('Completed Trips'),
                        TextEntry::make('active_trips')
                            ->getStateUsing(fn (Driver $record): int => $record->trips()->active()->count())
                            ->label('Active Trips'),
                        TextEntry::make('vehicles_operated')
                            ->getStateUsing(fn (Driver $record): int => $record->vehicles()->count())
                            ->label('Vehicles Operated'),
                    ])
                    ->columns(2),
            ]);
    }
}
