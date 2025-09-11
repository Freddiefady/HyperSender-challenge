<?php

declare(strict_types=1);

namespace App\Filament\Resources\Trips\Tables;

use App\Enums\TripStatusEnum;
use Carbon\Carbon;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

final class TripsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('trip_number')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('company.name')
                    ->searchable()
                    ->sortable()
                    ->toggleable(),
                TextColumn::make('driver.name')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('vehicle.display_name')
                    ->label('Vehicle')
                    ->searchable(['vehicles.brand', 'vehicles.model', 'vehicles.license_plate'])
                    ->sortable(),
                TextColumn::make('origin')
                    ->searchable()
                    ->limit(20),
                TextColumn::make('destination')
                    ->searchable()
                    ->limit(20),
                TextColumn::make('scheduled_start')
                    ->dateTime()
                    ->sortable(),
                TextColumn::make('scheduled_end')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('status')
                    ->badge(),
                TextColumn::make('distance_km')
                    ->label('Distance')
                    ->suffix(' km')
                    ->numeric()
                    ->toggleable(),
                IconColumn::make('is_overdue')
                    ->label('Overdue')
                    ->boolean()
                    ->visible(fn (): bool => true),
            ])
            ->filters([
                SelectFilter::make('company')
                    ->relationship('company', 'name'),
                SelectFilter::make('driver')
                    ->relationship('driver', 'name'),
                SelectFilter::make('status')
                    ->options(TripStatusEnum::class),
                Filter::make('overdue')
                    ->query(fn ($query) => $query->where('status', TripStatusEnum::SCHEDULED->value)
                        ->where('scheduled_start', '<', now()))
                    ->label('Overdue Trips'),
                Filter::make('today')
                    ->query(fn ($query) => $query->whereDate('scheduled_start', Carbon::today()))
                    ->label('Today'),
            ])
            ->recordActions([
                ViewAction::make(),
                EditAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('scheduled_start', 'desc');
    }
}
