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

beforeEach(function () {
    $this->company = Company::factory()->create();
    $this->driver = Driver::factory()->forCompany($this->company)->create();
    $this->vehicle = Vehicle::factory()->forCompany($this->company)->create();
});

describe('Driver Availability', function () {
    it('can check if driver is available for a time period', function () {
        // Create a trip from 10 AM to 2 PM today
        $existingTrip = Trip::factory()->create([
            'company_id' => $this->company->id,
            'driver_id' => $this->driver->id,
            'vehicle_id' => $this->vehicle->id,
            'scheduled_start' => Carbon::today()->addHours(10),
            'scheduled_end' => Carbon::today()->addHours(14),
            'status' => TripStatusEnum::SCHEDULED->value,
        ]);

        // Test non-overlapping time (8 AM to 9 AM) - should be available
        expect($this->driver->isAvailable(
            Carbon::today()->addHours(8),
            Carbon::today()->addHours(9)
        ))->toBeTrue();

        // Test overlapping time (11 AM to 1 PM) - should not be available
        expect($this->driver->isAvailable(
            Carbon::today()->addHours(11),
            Carbon::today()->addHours(13)
        ))->toBeFalse();

        // Test overlapping time (1 PM to 3 PM) - should not be available
        expect($this->driver->isAvailable(
            Carbon::today()->addHours(13),
            Carbon::today()->addHours(15)
        ))->toBeFalse();

        // Test completely encompassing time (9 AM to 3 PM) - should not be available
        expect($this->driver->isAvailable(
            Carbon::today()->addHours(9),
            Carbon::today()->addHours(15)
        ))->toBeFalse();
    });

    it('ignores completed and cancelled trips when checking availability', function () {
        // Create completed trip
        Trip::factory()->create([
            'company_id' => $this->company->id,
            'driver_id' => $this->driver->id,
            'vehicle_id' => $this->vehicle->id,
            'scheduled_start' => Carbon::today()->addHours(10),
            'scheduled_end' => Carbon::today()->addHours(14),
            'status' => TripStatusEnum::COMPLETED->value,
        ]);

        // Driver should be available during the same time period
        expect($this->driver->isAvailable(
            Carbon::today()->addHours(11),
            Carbon::today()->addHours(13)
        ))->toBeTrue();

        // Create cancelled trip
        Trip::factory()->create([
            'company_id' => $this->company->id,
            'driver_id' => $this->driver->id,
            'vehicle_id' => $this->vehicle->id,
            'scheduled_start' => Carbon::today()->addHours(15),
            'scheduled_end' => Carbon::today()->addHours(18),
            'status' => TripStatusEnum::CANCELLED->value,
        ]);

        // Driver should be available during the cancelled trip time
        expect($this->driver->isAvailable(
            Carbon::today()->addHours(16),
            Carbon::today()->addHours(17)
        ))->toBeTrue();
    });

    it('can exclude a specific trip when checking availability', function () {
        $trip = Trip::factory()->create([
            'company_id' => $this->company->id,
            'driver_id' => $this->driver->id,
            'vehicle_id' => $this->vehicle->id,
            'scheduled_start' => Carbon::today()->addHours(10),
            'scheduled_end' => Carbon::today()->addHours(14),
            'status' => TripStatusEnum::SCHEDULED->value,
        ]);

        // Without excluding the trip, driver should not be available
        expect($this->driver->isAvailable(
            Carbon::today()->addHours(11),
            Carbon::today()->addHours(13)
        ))->toBeFalse();

        // When excluding the trip, driver should be available
        expect($this->driver->isAvailable(
            Carbon::today()->addHours(11),
            Carbon::today()->addHours(13),
            $trip->id
        ))->toBeTrue();
    });

    it('can get overlapping trips', function () {
        $trip1 = Trip::factory()->create([
            'company_id' => $this->company->id,
            'driver_id' => $this->driver->id,
            'vehicle_id' => $this->vehicle->id,
            'scheduled_start' => Carbon::today()->addHours(10),
            'scheduled_end' => Carbon::today()->addHours(14),
            'status' => TripStatusEnum::SCHEDULED->value,
        ]);

        $trip2 = Trip::factory()->create([
            'company_id' => $this->company->id,
            'driver_id' => $this->driver->id,
            'vehicle_id' => $this->vehicle->id,
            'scheduled_start' => Carbon::today()->addHours(16),
            'scheduled_end' => Carbon::today()->addHours(20),
            'status' => TripStatusEnum::IN_PROGRESS->value,
        ]);

        // Get overlapping trips for 11 AM to 1 PM (should only get trip1)
        $overlapping = $this->driver->getOverlappingTrips(
            Carbon::today()->addHours(11),
            Carbon::today()->addHours(13)
        );

        expect($overlapping)->toHaveCount(1);
        expect($overlapping->first()->id)->toBe($trip1->id);

        // Get overlapping trips for 9 AM to 9 PM (should get both trips)
        $overlapping = $this->driver->getOverlappingTrips(
            Carbon::today()->addHours(9),
            Carbon::today()->addHours(21)
        );

        expect($overlapping)->toHaveCount(2);
    });
});

describe('Vehicle Availability', function () {
    it('can check if vehicle is available for a time period', function () {
        // Create a trip from 10 AM to 2 PM today
        Trip::factory()->create([
            'company_id' => $this->company->id,
            'driver_id' => $this->driver->id,
            'vehicle_id' => $this->vehicle->id,
            'scheduled_start' => Carbon::today()->addHours(10),
            'scheduled_end' => Carbon::today()->addHours(14),
            'status' => TripStatusEnum::SCHEDULED->value,
        ]);

        // Test non-overlapping time (8 AM to 9 AM) - should be available
        expect($this->vehicle->isAvailable(
            Carbon::today()->addHours(8),
            Carbon::today()->addHours(9)
        ))->toBeTrue();

        // Test overlapping time (11 AM to 1 PM) - should not be available
        expect($this->vehicle->isAvailable(
            Carbon::today()->addHours(11),
            Carbon::today()->addHours(13)
        ))->toBeFalse();
    });

    it('ignores completed and cancelled trips when checking availability', function () {
        Trip::factory()->create([
            'company_id' => $this->company->id,
            'driver_id' => $this->driver->id,
            'vehicle_id' => $this->vehicle->id,
            'scheduled_start' => Carbon::today()->addHours(10),
            'scheduled_end' => Carbon::today()->addHours(14),
            'status' => TripStatusEnum::COMPLETED->value,
        ]);

        // Vehicle should be available during the same time period
        expect($this->vehicle->isAvailable(
            Carbon::today()->addHours(11),
            Carbon::today()->addHours(13)
        ))->toBeTrue();
    });
});

describe('Trip Overlap Detection', function () {
    it('can detect if a trip overlaps with a time period', function () {
        $trip = Trip::factory()->create([
            'scheduled_start' => Carbon::today()->addHours(10),
            'scheduled_end' => Carbon::today()->addHours(14),
        ]);

        // Test overlapping scenarios
        expect($trip->overlaps(
            Carbon::today()->addHours(11),
            Carbon::today()->addHours(13)
        ))->toBeTrue();

        expect($trip->overlaps(
            Carbon::today()->addHours(9),
            Carbon::today()->addHours(11)
        ))->toBeTrue();

        expect($trip->overlaps(
            Carbon::today()->addHours(13),
            Carbon::today()->addHours(15)
        ))->toBeTrue();

        // Test non-overlapping scenarios
        expect($trip->overlaps(
            Carbon::today()->addHours(8),
            Carbon::today()->addHours(9)
        ))->toBeFalse();

        expect($trip->overlaps(
            Carbon::today()->addHours(15),
            Carbon::today()->addHours(16)
        ))->toBeFalse();
    });
});

describe('Double Booking Prevention', function () {
    it('prevents driver double booking', function () {
        // Create first trip
        $trip1 = Trip::factory()->create([
            'company_id' => $this->company->id,
            'driver_id' => $this->driver->id,
            'vehicle_id' => $this->vehicle->id,
            'scheduled_start' => Carbon::today()->addHours(10),
            'scheduled_end' => Carbon::today()->addHours(14),
            'status' => TripStatusEnum::SCHEDULED->value,
        ]);

        // Try to create overlapping trip with same driver but different vehicle
        $anotherVehicle = Vehicle::factory()->forCompany($this->company)->create();

        expect($this->driver->isAvailable(
            Carbon::today()->addHours(11),
            Carbon::today()->addHours(13)
        ))->toBeFalse();
    });

    it('prevents vehicle double booking', function () {
        // Create first trip
        $trip1 = Trip::factory()->create([
            'company_id' => $this->company->id,
            'driver_id' => $this->driver->id,
            'vehicle_id' => $this->vehicle->id,
            'scheduled_start' => Carbon::today()->addHours(10),
            'scheduled_end' => Carbon::today()->addHours(14),
            'status' => TripStatusEnum::SCHEDULED->value,
        ]);

        // Try to create overlapping trip with same vehicle but different driver
        $anotherDriver = Driver::factory()->forCompany($this->company)->create();

        expect($this->vehicle->isAvailable(
            Carbon::today()->addHours(11),
            Carbon::today()->addHours(13)
        ))->toBeFalse();
    });

    it('allows sequential trips with same driver and vehicle', function () {
        // Create first trip
        Trip::factory()->create([
            'company_id' => $this->company->id,
            'driver_id' => $this->driver->id,
            'vehicle_id' => $this->vehicle->id,
            'scheduled_start' => Carbon::today()->addHours(10),
            'scheduled_end' => Carbon::today()->addHours(14),
            'status' => TripStatusEnum::SCHEDULED->value,
        ]);

        // Check availability for trip right after the first one
        expect($this->driver->isAvailable(
            Carbon::today()->addHours(14),
            Carbon::today()->addHours(18)
        ))->toBeTrue();

        expect($this->vehicle->isAvailable(
            Carbon::today()->addHours(14),
            Carbon::today()->addHours(18)
        ))->toBeTrue();
    });
});
