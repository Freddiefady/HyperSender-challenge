<?php

declare(strict_types=1);

namespace App\Filament\Resources\Drivers\Tables;

use App\Models\Driver;
use Carbon\Carbon;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;

final class DriversTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->sortable()
                    ->searchable(),
                TextColumn::make('company.name')
                    ->sortable()
                    ->searchable(),
                TextColumn::make('email')
                    ->label('Email address')
                    ->sortable()
                    ->searchable(),
                TextColumn::make('phone')
                    ->searchable(),
                TextColumn::make('license_number')
                    ->searchable(),
                TextColumn::make('license_expiry')
                    ->date()
                    ->sortable()
                    ->color(fn (Driver $record): string => match (true) {
                        $record->is_license_expired => 'danger',
                        $record->is_license_expiring_soon => 'warning',
                        default => 'success',
                    }),
                IconColumn::make('is_active')
                    ->label('Active')
                    ->boolean()
                    ->searchable(),
                TextColumn::make('trips_count')
                    ->counts('trips')
                    ->badge()
                    ->label('Total Trips'),
                TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('company')
                    ->relationship('company', 'name'),
                TernaryFilter::make('is_active')
                    ->label('Active'),
                Filter::make('license_expiring')
                    ->query(fn ($query) => $query->where('license_expiry', '<=', Carbon::now()->addDays(30)))
                    ->label('License Expiring Soon'),
                Filter::make('license_expired')
                    ->query(fn ($query) => $query->where('license_expiry', '<', Carbon::now()))
                    ->label('License Expired'),
            ])
            ->recordActions([
                EditAction::make(),
                ViewAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
