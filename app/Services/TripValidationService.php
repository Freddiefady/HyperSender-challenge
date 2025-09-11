<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Driver;
use App\Models\Trip;
use App\Models\Vehicle;
use Carbon\Carbon;
use Illuminate\Support\Collection;

final class TripValidationService
{
    /**
     * Validate if a trip can be scheduled without conflicts
     */
    public function validateTrip(
        Driver $driver,
        Vehicle $vehicle,
        Carbon $scheduledStart,
        Carbon $scheduledEnd,
        ?Trip $excludeTrip = null
    ): array {
        $errors = [];
        $warnings = [];

        // Validate driver availability
        $driverConflicts = $this->checkDriverAvailability(
            $driver,
            $scheduledStart,
            $scheduledEnd,
            $excludeTrip?->id
        );

        if ($driverConflicts->isNotEmpty()) {
            $errors[] = [
                'type' => 'driver_conflict',
                'message' => "Driver {$driver->name} is not available during this time period",
                'conflicts' => $driverConflicts,
            ];
        }

        // Validate vehicle availability
        $vehicleConflicts = $this->checkVehicleAvailability(
            $vehicle,
            $scheduledStart,
            $scheduledEnd,
            $excludeTrip?->id
        );

        if ($vehicleConflicts->isNotEmpty()) {
            $errors[] = [
                'type' => 'vehicle_conflict',
                'message' => "Vehicle {$vehicle->display_name} is not available during this time period",
                'conflicts' => $vehicleConflicts,
            ];
        }

        // Check for warnings
        $warnings = array_merge($warnings, $this->checkWarnings($driver, $vehicle, $scheduledStart, $scheduledEnd));

        return [
            'valid' => empty($errors),
            'errors' => $errors,
            'warnings' => $warnings,
        ];
    }

    /**
     * Check driver availability for a time period
     */
    public function checkDriverAvailability(
        Driver $driver,
        Carbon $start,
        Carbon $end,
        ?int $excludeTripId = null
    ): Collection {
        return $driver->getOverlappingTrips($start, $end, $excludeTripId);
    }

    /**
     * Check vehicle availability for a time period
     */
    public function checkVehicleAvailability(
        Vehicle $vehicle,
        Carbon $start,
        Carbon $end,
        ?int $excludeTripId = null
    ): Collection {
        return $vehicle->getOverlappingTrips($start, $end, $excludeTripId);
    }

    /**
     * Get all conflicting trips for a driver and vehicle
     */
    public function getAllConflicts(
        Driver $driver,
        Vehicle $vehicle,
        Carbon $start,
        Carbon $end,
        ?int $excludeTripId = null
    ): array {
        return [
            'driver_conflicts' => $this->checkDriverAvailability($driver, $start, $end, $excludeTripId),
            'vehicle_conflicts' => $this->checkVehicleAvailability($vehicle, $start, $end, $excludeTripId),
        ];
    }

    /**
     * Find available drivers for a time period
     */
    public function findAvailableDrivers(
        int $companyId,
        Carbon $start,
        Carbon $end,
        ?int $excludeTripId = null
    ): Collection {
        return Driver::where('company_id', $companyId)
            ->where('is_active', true)
            ->get()
            ->filter(function ($driver) use ($start, $end, $excludeTripId) {
                return $driver->isAvailable($start, $end, $excludeTripId);
            });
    }

    /**
     * Find available vehicles for a time period
     */
    public function findAvailableVehicles(
        int $companyId,
        Carbon $start,
        Carbon $end,
        ?int $excludeTripId = null
    ): Collection {
        return Vehicle::where('company_id', $companyId)
            ->where('is_active', true)
            ->get()
            ->filter(function ($vehicle) use ($start, $end, $excludeTripId) {
                return $vehicle->isAvailable($start, $end, $excludeTripId);
            });
    }

    /**
     * Get availability summary for a driver
     */
    public function getDriverAvailabilitySummary(Driver $driver, Carbon $date): array
    {
        $startOfDay = $date->copy()->startOfDay();
        $endOfDay = $date->copy()->endOfDay();

        $trips = $driver->trips()
            ->where(function ($query) use ($startOfDay, $endOfDay) {
                $query->where('scheduled_start', '<', $endOfDay)
                    ->where('scheduled_end', '>', $startOfDay);
            })
            ->whereNotIn('status', ['completed', 'cancelled'])
            ->orderBy('scheduled_start')
            ->get();

        return [
            'date' => $date->format('Y-m-d'),
            'is_available' => $trips->isEmpty(),
            'scheduled_trips' => $trips,
            'busy_periods' => $trips->map(function ($trip) {
                return [
                    'start' => $trip->scheduled_start,
                    'end' => $trip->scheduled_end,
                    'trip_id' => $trip->id,
                    'destination' => $trip->destination,
                ];
            }),
        ];
    }

    /**
     * Get availability summary for a vehicle
     */
    public function getVehicleAvailabilitySummary(Vehicle $vehicle, Carbon $date): array
    {
        $startOfDay = $date->copy()->startOfDay();
        $endOfDay = $date->copy()->endOfDay();

        $trips = $vehicle->trips()
            ->where(function ($query) use ($startOfDay, $endOfDay) {
                $query->where('scheduled_start', '<', $endOfDay)
                    ->where('scheduled_end', '>', $startOfDay);
            })
            ->whereNotIn('status', ['completed', 'cancelled'])
            ->orderBy('scheduled_start')
            ->get();

        return [
            'date' => $date->format('Y-m-d'),
            'is_available' => $trips->isEmpty(),
            'scheduled_trips' => $trips,
            'busy_periods' => $trips->map(function ($trip) {
                return [
                    'start' => $trip->scheduled_start,
                    'end' => $trip->scheduled_end,
                    'trip_id' => $trip->id,
                    'driver' => $trip->driver->name,
                    'destination' => $trip->destination,
                ];
            }),
        ];
    }

    /**
     * Check for warnings (non-blocking issues)
     */
    private function checkWarnings(Driver $driver, Vehicle $vehicle, Carbon $start, Carbon $end): array
    {
        $warnings = [];

        // Check if driver's license is expiring soon
        if ($driver->is_license_expiring_soon) {
            $warnings[] = [
                'type' => 'license_expiring',
                'message' => "Driver's license expires on {$driver->license_expiry->format('Y-m-d')}",
            ];
        }

        // Check if driver's license is expired
        if ($driver->is_license_expired) {
            $warnings[] = [
                'type' => 'license_expired',
                'message' => "Driver's license expired on {$driver->license_expiry->format('Y-m-d')}",
            ];
        }

        // Check if driver or vehicle is inactive
        if (! $driver->is_active) {
            $warnings[] = [
                'type' => 'driver_inactive',
                'message' => 'Driver is marked as inactive',
            ];
        }

        if (! $vehicle->is_active) {
            $warnings[] = [
                'type' => 'vehicle_inactive',
                'message' => 'Vehicle is marked as inactive',
            ];
        }

        // Check if trip is very long (more than 24 hours)
        $duration = $start->diffInHours($end);
        if ($duration > 24) {
            $warnings[] = [
                'type' => 'long_trip',
                'message' => "Trip duration is {$duration} hours, which exceeds recommended maximum",
            ];
        }

        // Check if trip starts very soon (less than 2 hours from now)
        if ($start->diffInHours(Carbon::now()) < 2 && $start->isFuture()) {
            $warnings[] = [
                'type' => 'short_notice',
                'message' => 'Trip starts in less than 2 hours',
            ];
        }

        return $warnings;
    }
}
