<?php

declare(strict_types=1);

namespace App\Filament\Resources\Vehicles\Schemas;

use App\Enums\VehicleTypeEnum;
use App\Models\Company;
use Filament\Forms\Components\ColorPicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

final class VehicleForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Vehicle Information')
                    ->schema([
                        Select::make('company_id')
                            ->label('Company')
                            ->options(Company::pluck('name', 'id'))
                            ->required()
                            ->searchable(),
                        TextInput::make('brand')
                            ->required()
                            ->maxLength(255),
                        TextInput::make('model')
                            ->required()
                            ->maxLength(255),
                        TextInput::make('year')
                            ->required()
                            ->numeric()
                            ->minValue(1990)
                            ->maxValue(date('Y') + 1),
                        ColorPicker::make('color')
                            ->required(),
                        Select::make('vehicle_type')
                            ->options(VehicleTypeEnum::class)
                            ->required(),
                        Toggle::make('is_active')
                            ->label('Active')
                            ->default(true),
                    ])
                    ->columns(2),

                Section::make('Registration Details')
                    ->schema([
                        TextInput::make('license_plate')
                            ->required()
                            ->maxLength(255),
                        TextInput::make('vin')
                            ->label('VIN')
                            ->required()
                            ->maxLength(17)
                            ->minLength(17),
                    ])
                    ->columns(2),

                Section::make('Specifications')
                    ->schema([
                        TextInput::make('capacity_kg')
                            ->label('Capacity (kg)')
                            ->numeric()
                            ->minValue(0),
                        TextInput::make('fuel_capacity')
                            ->label('Fuel Capacity (L)')
                            ->numeric()
                            ->step(0.01)
                            ->minValue(0),
                    ])
                    ->columns(2),
            ]);
    }
}
