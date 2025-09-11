<?php

declare(strict_types=1);

use App\Enums\TripStatusEnum;
use App\Models\Company;
use App\Models\Driver;
use App\Models\Trip;
use App\Models\Vehicle;
use App\Services\TripValidationService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->company = Company::factory()->create();
    $this->driver = Driver::factory()->forCompany($this->company)->create();
    $this->vehicle = Vehicle::factory()->forCompany($this->company)->create();
    $this->validationService = new TripValidationService();
});

describe('Trip Validation', function () {
    it('validates a trip without conflicts', function () {
        $result = $this->validationService->validateTrip(
            $this->driver,
            $this->vehicle,
            Carbon::today()->addHours(10),
            Carbon::today()->addHours(14)
        );

        expect($result['valid'])->toBeTrue();
        expect($result['errors'])->toBeEmpty();
    });

    it('detects driver conflicts', function () {
        // Create existing trip for the driver
        Trip::factory()->create([
            'company_id' => $this->company->id,
            'driver_id' => $this->driver->id,
            'vehicle_id' => Vehicle::factory()->forCompany($this->company)->create()->id,
            'scheduled_start' => Carbon::today()->addHours(10),
            'scheduled_end' => Carbon::today()->addHours(14),
            'status' => TripStatusEnum::SCHEDULED->value,
        ]);

        $result = $this->validationService->validateTrip(
            $this->driver,
            $this->vehicle,
            Carbon::today()->addHours(11),
            Carbon::today()->addHours(13)
        );

        expect($result['valid'])->toBeFalse();
        expect($result['errors'])->toHaveCount(1);
        expect($result['errors'][0]['type'])->toBe('driver_conflict');
    });

    it('detects vehicle conflicts', function () {
        // Create existing trip for the vehicle
        Trip::factory()->create([
            'company_id' => $this->company->id,
            'driver_id' => Driver::factory()->forCompany($this->company)->create()->id,
            'vehicle_id' => $this->vehicle->id,
            'scheduled_start' => Carbon::today()->addHours(10),
            'scheduled_end' => Carbon::today()->addHours(14),
            'status' => TripStatusEnum::SCHEDULED->value,
        ]);

        $result = $this->validationService->validateTrip(
            $this->driver,
            $this->vehicle,
            Carbon::today()->addHours(11),
            Carbon::today()->addHours(13)
        );

        expect($result['valid'])->toBeFalse();
        expect($result['errors'])->toHaveCount(1);
        expect($result['errors'][0]['type'])->toBe('vehicle_conflict');
    });

    it('detects both driver and vehicle conflicts', function () {
        // Create existing trip with same driver and vehicle
        Trip::factory()->create([
            'company_id' => $this->company->id,
            'driver_id' => $this->driver->id,
            'vehicle_id' => $this->vehicle->id,
            'scheduled_start' => Carbon::today()->addHours(10),
            'scheduled_end' => Carbon::today()->addHours(14),
            'status' => TripStatusEnum::SCHEDULED->value,
        ]);

        $result = $this->validationService->validateTrip(
            $this->driver,
            $this->vehicle,
            Carbon::today()->addHours(11),
            Carbon::today()->addHours(13)
        );

        expect($result['valid'])->toBeFalse();
        expect($result['errors'])->toHaveCount(2);
        expect(collect($result['errors'])->pluck('type'))->toContain('driver_conflict', 'vehicle_conflict');
    });

    it('can exclude a trip from validation', function () {
        $existingTrip = Trip::factory()->create([
            'company_id' => $this->company->id,
            'driver_id' => $this->driver->id,
            'vehicle_id' => $this->vehicle->id,
            'scheduled_start' => Carbon::today()->addHours(10),
            'scheduled_end' => Carbon::today()->addHours(14),
            'status' => TripStatusEnum::SCHEDULED->value,
        ]);

        // Without excluding the trip, validation should fail
        $result = $this->validationService->validateTrip(
            $this->driver,
            $this->vehicle,
            Carbon::today()->addHours(11),
            Carbon::today()->addHours(13)
        );
        expect($result['valid'])->toBeFalse();

        // With excluding the trip, validation should pass
        $result = $this->validationService->validateTrip(
            $this->driver,
            $this->vehicle,
            Carbon::today()->addHours(11),
            Carbon::today()->addHours(13),
            $existingTrip
        );
        expect($result['valid'])->toBeTrue();
    });
});

describe('Warning Detection', function () {
    it('warns about expired driver license', function () {
        $driver = Driver::factory()->expiredLicense()->forCompany($this->company)->create();

        $result = $this->validationService->validateTrip(
            $driver,
            $this->vehicle,
            Carbon::today()->addHours(10),
            Carbon::today()->addHours(14)
        );

        expect($result['valid'])->toBeTrue();
        expect($result['warnings'])->not->toBeEmpty();
        expect(collect($result['warnings'])->pluck('type'))->toContain('license_expired');
    });

    it('warns about expiring driver license', function () {
        $driver = Driver::factory()->expiringSoon()->forCompany($this->company)->create();

        $result = $this->validationService->validateTrip(
            $driver,
            $this->vehicle,
            Carbon::today()->addHours(10),
            Carbon::today()->addHours(14)
        );

        expect($result['valid'])->toBeTrue();
        expect($result['warnings'])->not->toBeEmpty();
        expect(collect($result['warnings'])->pluck('type'))->toContain('license_expiring');
    });

    it('warns about inactive driver', function () {
        $driver = Driver::factory()->inactive()->forCompany($this->company)->create();

        $result = $this->validationService->validateTrip(
            $driver,
            $this->vehicle,
            Carbon::today()->addHours(10),
            Carbon::today()->addHours(14)
        );

        expect($result['valid'])->toBeTrue();
        expect($result['warnings'])->not->toBeEmpty();
        expect(collect($result['warnings'])->pluck('type'))->toContain('driver_inactive');
    });

    it('warns about inactive vehicle', function () {
        $vehicle = Vehicle::factory()->inactive()->forCompany($this->company)->create();

        $result = $this->validationService->validateTrip(
            $this->driver,
            $vehicle,
            Carbon::today()->addHours(10),
            Carbon::today()->addHours(14)
        );

        expect($result['valid'])->toBeTrue();
        expect($result['warnings'])->not->toBeEmpty();
        expect(collect($result['warnings'])->pluck('type'))->toContain('vehicle_inactive');
    });

    it('warns about long trips', function () {
        $result = $this->validationService->validateTrip(
            $this->driver,
            $this->vehicle,
            Carbon::today()->addHours(10),
            Carbon::today()->addHours(36) // 26 hours trip
        );

        expect($result['valid'])->toBeTrue();
        expect($result['warnings'])->not->toBeEmpty();
        expect(collect($result['warnings'])->pluck('type'))->toContain('long_trip');
    });

    it('warns about short notice trips', function () {
        $result = $this->validationService->validateTrip(
            $this->driver,
            $this->vehicle,
            Carbon::now()->addMinutes(30), // Starting in 30 minutes
            Carbon::now()->addHours(4)
        );

        expect($result['valid'])->toBeTrue();
        expect($result['warnings'])->not->toBeEmpty();
        expect(collect($result['warnings'])->pluck('type'))->toContain('short_notice');
    });
});

describe('Finding Available Resources', function () {
    it('can find available drivers for a time period', function () {
        // Create additional drivers
        $driver2 = Driver::factory()->forCompany($this->company)->create();
        $driver3 = Driver::factory()->forCompany($this->company)->create();

        // Book one driver
        Trip::factory()->create([
            'company_id' => $this->company->id,
            'driver_id' => $this->driver->id,
            'vehicle_id' => $this->vehicle->id,
            'scheduled_start' => Carbon::today()->addHours(10),
            'scheduled_end' => Carbon::today()->addHours(14),
            'status' => TripStatusEnum::SCHEDULED->value,
        ]);

        $availableDrivers = $this->validationService->findAvailableDrivers(
            $this->company->id,
            Carbon::today()->addHours(11),
            Carbon::today()->addHours(13)
        );

        expect($availableDrivers)->toHaveCount(2);
        expect($availableDrivers->pluck('id'))->toContain($driver2->id, $driver3->id);
        expect($availableDrivers->pluck('id'))->not->toContain($this->driver->id);
    });

    it('can find available vehicles for a time period', function () {
        // Create additional vehicles
        $vehicle2 = Vehicle::factory()->forCompany($this->company)->create();
        $vehicle3 = Vehicle::factory()->forCompany($this->company)->create();

        // Book one vehicle
        Trip::factory()->create([
            'company_id' => $this->company->id,
            'driver_id' => $this->driver->id,
            'vehicle_id' => $this->vehicle->id,
            'scheduled_start' => Carbon::today()->addHours(10),
            'scheduled_end' => Carbon::today()->addHours(14),
            'status' => TripStatusEnum::SCHEDULED->value,
        ]);

        $availableVehicles = $this->validationService->findAvailableVehicles(
            $this->company->id,
            Carbon::today()->addHours(11),
            Carbon::today()->addHours(13)
        );

        expect($availableVehicles)->toHaveCount(2);
        expect($availableVehicles->pluck('id'))->toContain($vehicle2->id, $vehicle3->id);
        expect($availableVehicles->pluck('id'))->not->toContain($this->vehicle->id);
    });

    it('excludes inactive drivers and vehicles', function () {
        $inactiveDriver = Driver::factory()->inactive()->forCompany($this->company)->create();
        $inactiveVehicle = Vehicle::factory()->inactive()->forCompany($this->company)->create();

        $availableDrivers = $this->validationService->findAvailableDrivers(
            $this->company->id,
            Carbon::today()->addHours(10),
            Carbon::today()->addHours(14)
        );

        $availableVehicles = $this->validationService->findAvailableVehicles(
            $this->company->id,
            Carbon::today()->addHours(10),
            Carbon::today()->addHours(14)
        );

        expect($availableDrivers->pluck('id'))->not->toContain($inactiveDriver->id);
        expect($availableVehicles->pluck('id'))->not->toContain($inactiveVehicle->id);
    });
});

describe('Availability Summaries', function () {
    it('can get driver availability summary for a day', function () {
        $date = Carbon::today();

        // Create some trips for the driver
        Trip::factory()->create([
            'company_id' => $this->company->id,
            'driver_id' => $this->driver->id,
            'vehicle_id' => $this->vehicle->id,
            'scheduled_start' => $date->copy()->addHours(10),
            'scheduled_end' => $date->copy()->addHours(14),
            'status' => TripStatusEnum::SCHEDULED->value,
        ]);

        $summary = $this->validationService->getDriverAvailabilitySummary($this->driver, $date);

        expect($summary['date'])->toBe($date->format('Y-m-d'));
        expect($summary['is_available'])->toBeFalse();
        expect($summary['scheduled_trips'])->toHaveCount(1);
        expect($summary['busy_periods'])->toHaveCount(1);
    });

    it('can get vehicle availability summary for a day', function () {
        $date = Carbon::today();

        Trip::factory()->create([
            'company_id' => $this->company->id,
            'driver_id' => $this->driver->id,
            'vehicle_id' => $this->vehicle->id,
            'scheduled_start' => $date->copy()->addHours(10),
            'scheduled_end' => $date->copy()->addHours(14),
            'status' => TripStatusEnum::SCHEDULED->value,
        ]);

        $summary = $this->validationService->getVehicleAvailabilitySummary($this->vehicle, $date);

        expect($summary['date'])->toBe($date->format('Y-m-d'));
        expect($summary['is_available'])->toBeFalse();
        expect($summary['scheduled_trips'])->toHaveCount(1);
        expect($summary['busy_periods'])->toHaveCount(1);
    });
});
