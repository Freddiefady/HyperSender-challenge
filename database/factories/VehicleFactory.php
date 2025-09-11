<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\VehicleTypeEnum;
use App\Models\Company;
use App\Models\Driver;
use App\Models\Vehicle;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Vehicle>
 */
final class VehicleFactory extends Factory
{
    protected $model = Vehicle::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    private array $makes = [
        'car' => ['Toyota', 'Ford', 'Volkswagen', 'Honda', 'Hyundai'],
        'truck' => ['Volvo', 'Scania', 'MAN', 'Mercedes-Benz', 'Iveco'],
        'van' => ['Ford', 'Mercedes-Benz', 'Volkswagen', 'Iveco', 'Renault'],
        'bus' => ['Ford', 'Mercedes', 'Wrightbus', 'Volvo', 'BYD'],
        'motorcycle' => ['Honda', 'Yamaha', 'Suzuki', 'Harley-Davidson', 'Kawasaki'],
    ];

    private array $models = [
        'truck' => ['FH', 'R-Series', 'TGX', 'Actros', 'S-Way'],
        'van' => ['Transit', 'Sprinter', 'Crafter', 'Daily', 'Master'],
        'bus' => ['MAN', 'MVC', 'Cool Liner', 'City Master', 'Maxima'],
        'motorcycle' => ['Yamaha-YZF-R1', 'Honda-CBR600RR', 'Kawasaki-Concours', 'Gold wing'],
        'car' => ['Camry', 'Focus', 'Golf', 'Accord', 'Elantra'],
    ];

    public function definition(): array
    {
        $vehicleType = $this->faker->randomElement(['truck', 'van', 'bus', 'motorcycle', 'car']);
        $make = $this->faker->randomElement($this->makes[$vehicleType]);
        $model = $this->faker->randomElement($this->models[$vehicleType]);

        return [
            'company_id' => Company::factory(),
            'driver_id' => Driver::factory(),
            'brand' => $make,
            'model' => $model,
            'color' => $this->faker->colorName(),
            'year' => $this->faker->numberBetween(2015, 2024),
            'license_plate' => $this->faker->unique()->regexify('[A-Z]{2}[0-9]{2}[A-Z]{3}'),
            'vin' => $this->faker->unique()->regexify('[A-Z0-9]{17}'),
            'vehicle_type' => $vehicleType,
            'capacity_kg' => $this->getCapacityForType($vehicleType),
            'fuel_capacity' => $this->getFuelCapacityForType($vehicleType),
            'is_active' => $this->faker->boolean(90),
        ];
    }

    public function forCompany(Company $company): static
    {
        return $this->state(fn (array $attributes) => [
            'company_id' => $company->id,
        ]);
    }

    public function forDriver(Driver $driver): static
    {
        return $this->state(fn (array $attributes) => [
            'driver_id' => $driver->id,
        ]);
    }

    public function truck(): static
    {
        return $this->state(fn (array $attributes) => [
            'vehicle_type' => VehicleTypeEnum::TRUCK->value,
            'brand' => $this->faker->randomElement($this->makes['truck']),
            'model' => $this->faker->randomElement($this->models['truck']),
            'capacity_kg' => $this->faker->numberBetween(15000, 40000),
            'fuel_capacity' => $this->faker->numberBetween(400, 800),
        ]);
    }

    public function van(): static
    {
        return $this->state(fn (array $attributes) => [
            'vehicle_type' => VehicleTypeEnum::VAN->value,
            'brand' => $this->faker->randomElement($this->makes['van']),
            'model' => $this->faker->randomElement($this->models['van']),
            'capacity_kg' => $this->faker->numberBetween(1000, 3500),
            'fuel_capacity' => $this->faker->numberBetween(60, 100),
        ]);
    }

    public function car(): static
    {
        return $this->state(fn (array $attributes) => [
            'vehicle_type' => VehicleTypeEnum::CAR->value,
            'brand' => $this->faker->randomElement($this->makes['car']),
            'model' => $this->faker->randomElement($this->models['car']),
            'capacity_kg' => $this->faker->numberBetween(500, 1000),
            'fuel_capacity' => $this->faker->numberBetween(60, 100),
        ]);
    }

    public function motorcycle(): static
    {
        return $this->state(fn (array $attributes) => [
            'vehicle_type' => VehicleTypeEnum::MOTORCYCLE->value,
            'brand' => $this->faker->randomElement($this->makes['motorcycle']),
            'model' => $this->faker->randomElement($this->models['motorcycle']),
            'capacity_kg' => $this->faker->numberBetween(100, 200),
            'fuel_capacity' => $this->faker->numberBetween(30, 60),
        ]);
    }

    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_active' => false,
        ]);
    }

    private function getCapacityForType(string $type): int
    {
        return match ($type) {
            'truck' => $this->faker->numberBetween(15000, 40000),
            'van' => $this->faker->numberBetween(1000, 3500),
            'car' => $this->faker->numberBetween(200, 500),
            'bus' => $this->faker->numberBetween(1800, 2000),
            'motorcycle' => $this->faker->numberBetween(20, 50),
        };
    }

    private function getFuelCapacityForType(string $type): float
    {
        return match ($type) {
            'truck' => $this->faker->numberBetween(400, 800),
            'van' => $this->faker->numberBetween(60, 100),
            'bus' => $this->faker->numberBetween(100, 200),
            'car' => $this->faker->numberBetween(40, 80),
            'motorcycle' => $this->faker->numberBetween(20, 50),
        };
    }
}
