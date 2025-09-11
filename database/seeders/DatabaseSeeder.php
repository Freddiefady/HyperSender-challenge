<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\Company;
use App\Models\Driver;
use App\Models\Trip;
use App\Models\Vehicle;
use Carbon\Carbon;
use Illuminate\Database\Seeder;

final class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        //        User::factory()->create([
        //            'name' => 'Test User',
        //            'email' => 'test@example.com',
        //        ]);

        // Create companies with their drivers, vehicles, and trips
        Company::factory(5)->create()->each(function ($company) {
            // Create drivers for each company
            $drivers = Driver::factory(rand(3, 8))
                ->forCompany($company)
                ->create();

            // Create vehicles for each company
            $vehicles = Vehicle::factory(rand(2, 6))
                ->forCompany($company)
                ->create();

            // Create some specific vehicle types
            Vehicle::factory(2)->truck()->forCompany($company)->create();
            Vehicle::factory(1)->van()->forCompany($company)->create();
            Vehicle::factory(1)->car()->forCompany($company)->create();
            Vehicle::factory(1)->motorcycle()->forCompany($company)->create();

            // Create trips with proper relationships
            $this->createTripsForCompany($company, $drivers, $vehicles);
        });

        // Create some drivers with expiring licenses
        $companies = Company::all();
        Driver::factory(3)
            ->expiringSoon()
            ->forCompany($companies->random())
            ->create();

        Driver::factory(2)
            ->expiredLicense()
            ->forCompany($companies->random())
            ->create();
    }

    private function createTripsForCompany($company, $drivers, $vehicles): void
    {
        $activeDrivers = $drivers->where('is_active', true);
        $activeVehicles = $vehicles->where('is_active', true);

        if ($activeDrivers->isEmpty() || $activeVehicles->isEmpty()) {
            return;
        }

        // Create trips with different statuses
        $this->createScheduledTrips($company, $activeDrivers, $activeVehicles, 15);
        $this->createInProgressTrips($company, $activeDrivers, $activeVehicles, 3);
        $this->createCompletedTrips($company, $activeDrivers, $activeVehicles, 25);
        $this->createCancelledTrips($company, $activeDrivers, $activeVehicles, 2);
    }

    private function createScheduledTrips($company, $drivers, $vehicles, $count): void
    {
        for ($i = 0; $i < $count; $i++) {
            $driver = $drivers->random();
            $vehicle = $vehicles->random();

            // Schedule trips in the future
            $scheduledStart = Carbon::now()->addDays(rand(1, 30))->addHours(rand(6, 18));
            $duration = rand(4, 24);
            $scheduledEnd = $scheduledStart->copy()->addHours($duration);

            // Ensure no conflicts
            if ($this->hasConflict($driver, $vehicle, $scheduledStart, $scheduledEnd)) {
                continue;
            }

            Trip::factory()
                ->scheduled()
                ->create([
                    'company_id' => $company->id,
                    'driver_id' => $driver->id,
                    'vehicle_id' => $vehicle->id,
                    'scheduled_start' => $scheduledStart,
                    'scheduled_end' => $scheduledEnd,
                ]);
        }
    }

    private function createInProgressTrips($company, $drivers, $vehicles, $count): void
    {
        for ($i = 0; $i < $count; $i++) {
            $driver = $drivers->random();
            $vehicle = $vehicles->random();

            // Trips that started recently
            $scheduledStart = Carbon::now()->subHours(rand(1, 6));
            $duration = rand(8, 24);
            $scheduledEnd = $scheduledStart->copy()->addHours($duration);

            if ($this->hasConflict($driver, $vehicle, $scheduledStart, $scheduledEnd)) {
                continue;
            }

            Trip::factory()
                ->inProgress()
                ->create([
                    'company_id' => $company->id,
                    'driver_id' => $driver->id,
                    'vehicle_id' => $vehicle->id,
                    'scheduled_start' => $scheduledStart,
                    'scheduled_end' => $scheduledEnd,
                ]);
        }
    }

    private function createCompletedTrips($company, $drivers, $vehicles, $count): void
    {
        for ($i = 0; $i < $count; $i++) {
            $driver = $drivers->random();
            $vehicle = $vehicles->random();

            // Completed trips in the past
            $scheduledStart = Carbon::now()->subDays(rand(1, 90))->addHours(rand(6, 18));
            $duration = rand(4, 24);
            $scheduledEnd = $scheduledStart->copy()->addHours($duration);

            Trip::factory()
                ->completed()
                ->create([
                    'company_id' => $company->id,
                    'driver_id' => $driver->id,
                    'vehicle_id' => $vehicle->id,
                    'scheduled_start' => $scheduledStart,
                    'scheduled_end' => $scheduledEnd,
                ]);
        }
    }

    private function createCancelledTrips($company, $drivers, $vehicles, $count): void
    {
        for ($i = 0; $i < $count; $i++) {
            $driver = $drivers->random();
            $vehicle = $vehicles->random();

            $scheduledStart = Carbon::now()->subDays(rand(1, 30))->addHours(rand(6, 18));
            $duration = rand(4, 24);
            $scheduledEnd = $scheduledStart->copy()->addHours($duration);

            Trip::factory()
                ->cancelled()
                ->create([
                    'company_id' => $company->id,
                    'driver_id' => $driver->id,
                    'vehicle_id' => $vehicle->id,
                    'scheduled_start' => $scheduledStart,
                    'scheduled_end' => $scheduledEnd,
                ]);
        }
    }

    private function hasConflict($driver, $vehicle, $scheduledStart, $scheduledEnd): bool
    {
        return ! $driver->isAvailable($scheduledStart, $scheduledEnd) ||
            ! $vehicle->isAvailable($scheduledStart, $scheduledEnd);
    }
}
