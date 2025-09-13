<?php

declare(strict_types=1);

namespace App\Filament\Resources\Drivers\Schemas;

use App\Enums\TripStatusEnum;
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

                Section::make('Vehicle History')
                    ->schema([
                        TextEntry::make('vehicles_operated')
                            ->getStateUsing(function (Driver $record): string {
                                $vehicles = $record->vehicles()
                                    ->withPivot(['scheduled_start'])
                                    ->orderByPivot('scheduled_start', 'desc')
                                    ->get();

                                if ($vehicles->isEmpty()) {
                                    return 'No vehicles operated yet';
                                }

                                return $vehicles->map(function ($vehicle) use ($record) {
                                    $lastTrip = $record->trips()
                                        ->where('vehicle_id', $vehicle->id)
                                        ->latest('scheduled_start')
                                        ->first();

                                    $lastUsed = $lastTrip ? $lastTrip->scheduled_start->format('M j, Y') : 'Unknown';

                                    return "{$vehicle->display_name} (Last used: {$lastUsed})";
                                })->join('<br>');
                            })
                            ->html()
                            ->columnSpanFull(),
                        TextEntry::make('current_assignment')
                            ->getStateUsing(function (Driver $record): string {
                                $activeTrip = $record->trips()
                                    ->whereIn(
                                        'status', [TripStatusEnum::SCHEDULED->value, TripStatusEnum::IN_PROGRESS->value]
                                    )
                                    ->with('vehicle')
                                    ->latest('scheduled_start')
                                    ->first();

                                if (! $activeTrip) {
                                    return 'No active assignment';
                                }

                                $status = $activeTrip->status === TripStatusEnum::IN_PROGRESS->value
                                    ? 'Currently driving'
                                    : TripStatusEnum::SCHEDULED->value;

                                return "{$status}: {$activeTrip->vehicle->display_name} ({$activeTrip->scheduled_start->format('M j, H:i')} - {$activeTrip->scheduled_end->format('H:i')})";
                            })
                            ->columnSpanFull(),
                    ])
                    ->columns(1),
            ]);
    }
}
