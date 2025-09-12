<?php

declare(strict_types=1);

namespace App\Filament\Resources\Vehicles\Tables;

use App\Enums\VehicleTypeEnum;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;

final class VehiclesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('display_name')
                    ->label('Vehicle')
                    ->searchable(['brand', 'model', 'license_plate'])
                    ->sortable(),
                TextColumn::make('company.name')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('vehicle_type')
                    ->badge(),
                TextColumn::make('year')
                    ->sortable(),
                TextColumn::make('license_plate')
                    ->searchable(),
                TextColumn::make('capacity_kg')
                    ->label('Capacity (kg)')
                    ->numeric()
                    ->sortable(),
                IconColumn::make('is_active')
                    ->boolean()
                    ->label('Active'),
                TextColumn::make('trips_count')
                    ->counts('trips')
                    ->label('Total Trips'),
            ])
            ->filters([
                SelectFilter::make('company')
                    ->relationship('company', 'name'),
                SelectFilter::make('vehicle_type')
                    ->options(VehicleTypeEnum::class),
                TernaryFilter::make('is_active')
                    ->label('Active'),
            ])
            ->recordActions([
                ViewAction::make(),
                EditAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
