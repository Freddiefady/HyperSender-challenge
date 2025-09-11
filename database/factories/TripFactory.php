<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\TripStatusEnum;
use App\Models\Company;
use App\Models\Driver;
use App\Models\Trip;
use App\Models\Vehicle;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Trip>
 */
final class TripFactory extends Factory
{
    protected $model = Trip::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    private array $cities = [
        'New York', 'Los Angeles', 'Chicago', 'Houston', 'Phoenix',
        'Philadelphia', 'San Antonio', 'San Diego', 'Dallas', 'San Jose',
        'Austin', 'Jacksonville', 'Fort Worth', 'Columbus', 'Charlotte',
        'San Francisco', 'Indianapolis', 'Seattle', 'Denver', 'Boston',
    ];

    public function definition(): array
    {
        $scheduledStart = $this->faker->dateTimeBetween('-1 month', '+2 months');
        $duration = $this->faker->numberBetween(2, 48); // 2-48 hours
        $scheduledEnd = Carbon::parse($scheduledStart)->addHours($duration);

        $status = $this->faker->randomElement(['scheduled', 'in_progress', 'completed', 'cancelled']);

        $distance = $this->faker->numberBetween(100, 2000);
        $fuelConsumed = $status === TripStatusEnum::COMPLETED->value
            ? $distance / $this->faker->numberBetween(6, 12)
            : null;

        return [
            'company_id' => Company::factory(),
            'driver_id' => Driver::factory(),
            'vehicle_id' => Vehicle::factory(),
            'trip_number' => null, // Will be auto-generated in TripObserver
            'origin' => $this->faker->randomElement($this->cities),
            'destination' => $this->faker->randomElement(array_diff($this->cities, [])),
            'scheduled_start' => $scheduledStart,
            'scheduled_end' => $scheduledEnd,
            'actual_start' => $this->getActualStart($status, $scheduledStart),
            'actual_end' => $this->getActualEnd($status, $scheduledStart, $duration),
            'status' => $status,
            'distance_km' => $distance,
            'fuel_consumed' => $fuelConsumed,
            'notes' => $this->faker->optional()->sentence(),
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
            'company_id' => $driver->company_id,
        ]);
    }

    public function forVehicle(Vehicle $vehicle): static
    {
        return $this->state(fn (array $attributes) => [
            'vehicle_id' => $vehicle->id,
            'company_id' => $vehicle->company_id,
        ]);
    }

    public function scheduled(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => TripStatusEnum::SCHEDULED->value,
            'actual_start' => null,
            'actual_end' => null,
            'fuel_consumed' => null,
        ]);
    }

    public function inProgress(): static
    {
        return $this->state(function (array $attributes) {
            $scheduledStart = Carbon::parse($attributes['scheduled_start']);
            $actualStart = $scheduledStart->copy()->addMinutes($this->faker->numberBetween(-30, 60));

            return [
                'status' => TripStatusEnum::IN_PROGRESS->value,
                'actual_start' => $actualStart,
                'actual_end' => null,
                'fuel_consumed' => null,
            ];
        });
    }

    public function completed(): static
    {
        return $this->state(function (array $attributes) {
            $scheduledStart = Carbon::parse($attributes['scheduled_start']);
            $scheduledEnd = Carbon::parse($attributes['scheduled_end']);
            $actualStart = $scheduledStart->copy()->addMinutes($this->faker->numberBetween(-30, 60));
            $actualEnd = $scheduledEnd->copy()->addMinutes($this->faker->numberBetween(-60, 120));

            $distance = $attributes['distance_km'];
            $fuelConsumed = $distance / $this->faker->numberBetween(6, 12);

            return [
                'status' => TripStatusEnum::COMPLETED->value,
                'actual_start' => $actualStart,
                'actual_end' => $actualEnd,
                'fuel_consumed' => round($fuelConsumed, 2),
            ];
        });
    }

    public function cancelled(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => TripStatusEnum::CANCELLED->value,
            'actual_start' => null,
            'actual_end' => null,
            'fuel_consumed' => null,
        ]);
    }

    private function getActualStart(string $status, $scheduledStart): ?Carbon
    {
        if (in_array($status, [TripStatusEnum::COMPLETED->value, TripStatusEnum::IN_PROGRESS->value])) {
            return Carbon::parse($scheduledStart)->addMinutes($this->faker->numberBetween(-30, 60));
        }

        return null;
    }

    private function getActualEnd(string $status, $scheduledStart, int $duration): ?Carbon
    {
        if ($status === TripStatusEnum::COMPLETED->value) {
            $actualStart = Carbon::parse($scheduledStart)->addMinutes($this->faker->numberBetween(-30, 60));

            return $actualStart->addHours($duration)->addMinutes($this->faker->numberBetween(-60, 120));
        }

        return null;
    }
}
