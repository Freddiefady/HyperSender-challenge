<?php

declare(strict_types=1);

use App\Enums\TripStatusEnum;
use App\Models\Company;
use App\Models\Driver;
use App\Models\Trip;
use App\Models\Vehicle;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

describe('Company Relationships', function () {
    it('has many drivers', function () {
        $company = Company::factory()->create();
        $drivers = Driver::factory(3)->forCompany($company)->create();

        expect($company->drivers)->toHaveCount(3);
        expect($company->drivers->first())->toBeInstanceOf(Driver::class);
    });

    it('has many vehicles', function () {
        $company = Company::factory()->create();
        $vehicles = Vehicle::factory(3)->forCompany($company)->create();

        expect($company->vehicles)->toHaveCount(3);
        expect($company->vehicles->first())->toBeInstanceOf(Vehicle::class);
    });

    it('has many trips', function () {
        $company = Company::factory()->create();
        $driver = Driver::factory()->forCompany($company)->create();
        $vehicle = Vehicle::factory()->forCompany($company)->create();

        $trips = Trip::factory(2)->create([
            'company_id' => $company->id,
            'driver_id' => $driver->id,
            'vehicle_id' => $vehicle->id,
        ]);

        expect($company->trips)->toHaveCount(2);
        expect($company->trips->first())->toBeInstanceOf(Trip::class);
    });

    it('can get active drivers and vehicles', function () {
        $company = Company::factory()->create();

        Driver::factory(2)->active()->forCompany($company)->create();
        Driver::factory(1)->inactive()->forCompany($company)->create();

        Vehicle::factory(3)->forCompany($company)->create(['is_active' => true]);
        Vehicle::factory(1)->inactive()->forCompany($company)->create();

        expect($company->activeDrivers)->toHaveCount(2);
        expect($company->activeVehicles)->toHaveCount(3);
    });

    it('calculates active and total trips correctly', function () {
        $company = Company::factory()->create();
        $driver = Driver::factory()->forCompany($company)->create();
        $vehicle = Vehicle::factory()->forCompany($company)->create();

        // Create trips with different statuses
        Trip::factory()->create([
            'company_id' => $company->id,
            'driver_id' => $driver->id,
            'vehicle_id' => $vehicle->id,
            'status' => TripStatusEnum::SCHEDULED->value,
        ]);

        Trip::factory()->create([
            'company_id' => $company->id,
            'driver_id' => $driver->id,
            'vehicle_id' => $vehicle->id,
            'status' => TripStatusEnum::IN_PROGRESS->value,
        ]);

        Trip::factory()->create([
            'company_id' => $company->id,
            'driver_id' => $driver->id,
            'vehicle_id' => $vehicle->id,
            'status' => TripStatusEnum::COMPLETED->value,
        ]);

        expect($company->active_trips_count)->toBe(2);
        expect($company->total_trips_count)->toBe(3);
    });
});

describe('Driver Relationships', function () {
    it('belongs to a company', function () {
        $company = Company::factory()->create();
        $driver = Driver::factory()->forCompany($company)->create();

        expect($driver->company)->toBeInstanceOf(Company::class);
        expect($driver->company->id)->toBe($company->id);
    });

    it('has many trips', function () {
        $company = Company::factory()->create();
        $driver = Driver::factory()->forCompany($company)->create();
        $vehicle = Vehicle::factory()->forCompany($company)->create();

        Trip::factory(3)->create([
            'company_id' => $company->id,
            'driver_id' => $driver->id,
            'vehicle_id' => $vehicle->id,
        ]);

        expect($driver->trips)->toHaveCount(3);
        expect($driver->trips->first())->toBeInstanceOf(Trip::class);
    });

    it('has many vehicles through trips', function () {
        $company = Company::factory()->create();
        $driver = Driver::factory()->forCompany($company)->create();
        $vehicle1 = Vehicle::factory()->forCompany($company)->create();
        $vehicle2 = Vehicle::factory()->forCompany($company)->create();

        Trip::factory()->create([
            'company_id' => $company->id,
            'driver_id' => $driver->id,
            'vehicle_id' => $vehicle1->id,
        ]);

        Trip::factory()->create([
            'company_id' => $company->id,
            'driver_id' => $driver->id,
            'vehicle_id' => $vehicle2->id,
        ]);

        expect($driver->vehicles)->toHaveCount(2);
        expect($driver->vehicles->pluck('id'))->toContain($vehicle1->id, $vehicle2->id);
    });

    it('can check license status', function () {
        $validDriver = Driver::factory()->create([
            'license_expiry' => Carbon::now()->addMonths(6),
        ]);

        $expiringDriver = Driver::factory()->expiringSoon()->create();
        $expiredDriver = Driver::factory()->expiredLicense()->create();

        expect($validDriver->is_license_expired)->toBeFalse();
        expect($validDriver->is_license_expiring_soon)->toBeFalse();

        expect($expiringDriver->is_license_expired)->toBeFalse();
        expect($expiringDriver->is_license_expiring_soon)->toBeTrue();

        expect($expiredDriver->is_license_expired)->toBeTrue();
    });
});

describe('Vehicle Relationships', function () {
    it('belongs to a company', function () {
        $company = Company::factory()->create();
        $vehicle = Vehicle::factory()->forCompany($company)->create();

        expect($vehicle->company)->toBeInstanceOf(Company::class);
        expect($vehicle->company->id)->toBe($company->id);
    });

    it('has many trips', function () {
        $company = Company::factory()->create();
        $driver = Driver::factory()->forCompany($company)->create();
        $vehicle = Vehicle::factory()->forCompany($company)->create();

        Trip::factory(3)->create([
            'company_id' => $company->id,
            'driver_id' => $driver->id,
            'vehicle_id' => $vehicle->id,
        ]);

        expect($vehicle->trips)->toHaveCount(3);
        expect($vehicle->trips->first())->toBeInstanceOf(Trip::class);
    });

    it('has many drivers through trips', function () {
        $company = Company::factory()->create();
        $driver1 = Driver::factory()->forCompany($company)->create();
        $driver2 = Driver::factory()->forCompany($company)->create();
        $vehicle = Vehicle::factory()->forCompany($company)->create();

        Trip::factory()->create([
            'company_id' => $company->id,
            'driver_id' => $driver1->id,
            'vehicle_id' => $vehicle->id,
        ]);

        Trip::factory()->create([
            'company_id' => $company->id,
            'driver_id' => $driver2->id,
            'vehicle_id' => $vehicle->id,
        ]);

        expect($vehicle->drivers)->toHaveCount(2);
        expect($vehicle->drivers->pluck('id'))->toContain($driver1->id, $driver2->id);
    });

    it('calculates display name correctly', function () {
        $vehicle = Vehicle::factory()->create([
            'brand' => 'Ford',
            'model' => 'Transit',
            'license_plate' => 'ABC123',
        ]);

        expect($vehicle->display_name)->toBe('Ford - Transit (ABC123)');
    });

    it('counts trips correctly', function () {
        $company = Company::factory()->create();
        $driver = Driver::factory()->forCompany($company)->create();
        $vehicle = Vehicle::factory()->forCompany($company)->create();

        Trip::factory()->create([
            'company_id' => $company->id,
            'driver_id' => $driver->id,
            'vehicle_id' => $vehicle->id,
            'status' => TripStatusEnum::COMPLETED->value,
        ]);

        Trip::factory()->create([
            'company_id' => $company->id,
            'driver_id' => $driver->id,
            'vehicle_id' => $vehicle->id,
            'status' => TripStatusEnum::SCHEDULED->value,
        ]);

        expect($vehicle->total_trips)->toBe(2);
        expect($vehicle->completed_trips)->toBe(1);
    });
});

describe('Trip Relationships', function () {
    it('belongs to company, driver, and vehicle', function () {
        $company = Company::factory()->create();
        $driver = Driver::factory()->forCompany($company)->create();
        $vehicle = Vehicle::factory()->forCompany($company)->create();

        $trip = Trip::factory()->create([
            'company_id' => $company->id,
            'driver_id' => $driver->id,
            'vehicle_id' => $vehicle->id,
        ]);

        expect($trip->company)->toBeInstanceOf(Company::class);
        expect($trip->driver)->toBeInstanceOf(Driver::class);
        expect($trip->vehicle)->toBeInstanceOf(Vehicle::class);

        expect($trip->company->id)->toBe($company->id);
        expect($trip->driver->id)->toBe($driver->id);
        expect($trip->vehicle->id)->toBe($vehicle->id);
    });

    it('calculates durations correctly', function () {
        $trip = Trip::factory()->create([
            'scheduled_start' => Carbon::now(),
            'scheduled_end' => Carbon::now()->addHours(8),
            'actual_start' => Carbon::now()->addMinutes(15),
            'actual_end' => Carbon::now()->addHours(7)->addMinutes(45),
        ]);

        expect($trip->scheduled_duration)->toBe(8.0);
        expect($trip->actual_duration)->toBe(7.5);
    });

    it('calculates fuel efficiency correctly', function () {
        $trip = Trip::factory()->create([
            'distance_km' => 100,
            'fuel_consumed' => 10,
        ]);

        expect($trip->fuel_efficiency)->toBe(10.0);

        // Test null cases
        $trip2 = Trip::factory()->create([
            'distance_km' => null,
            'fuel_consumed' => 10,
        ]);

        expect($trip2->fuel_efficiency)->toBeNull();
    });

    it('detects active and overdue trips', function () {
        $activeTrip = Trip::factory()->create([
            'status' => TripStatusEnum::IN_PROGRESS->value,
        ]);

        $overdueTrip = Trip::factory()->create([
            'status' => TripStatusEnum::SCHEDULED->value,
            'scheduled_start' => Carbon::now()->subHour(),
        ]);

        $onTimeTrip = Trip::factory()->create([
            'status' => TripStatusEnum::SCHEDULED->value,
            'scheduled_start' => Carbon::now()->addHour(),
        ]);

        expect($activeTrip->is_active)->toBeTrue();
        expect($overdueTrip->is_overdue)->toBeTrue();
        expect($onTimeTrip->is_overdue)->toBeFalse();
    });

    it('auto-generates trip number on creation', function () {
        $trip = Trip::factory()->create();

        expect($trip->trip_number)->not->toBeNull();
        expect($trip->trip_number)->toStartWith('TRP-');
    });

    it('has scopes for filtering', function () {
        $company = Company::factory()->create();
        $driver = Driver::factory()->forCompany($company)->create();
        $vehicle = Vehicle::factory()->forCompany($company)->create();

        Trip::factory()->create([
            'company_id' => $company->id,
            'driver_id' => $driver->id,
            'vehicle_id' => $vehicle->id,
            'status' => TripStatusEnum::SCHEDULED->value,
        ]);

        Trip::factory()->create([
            'company_id' => $company->id,
            'driver_id' => $driver->id,
            'vehicle_id' => $vehicle->id,
            'status' => TripStatusEnum::COMPLETED->value,
        ]);

        expect(Trip::active()->count())->toBe(1);
        expect(Trip::completed()->count())->toBe(1);
    });
});
