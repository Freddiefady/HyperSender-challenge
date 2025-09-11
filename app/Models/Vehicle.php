<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\TripStatusEnum;
use App\Enums\VehicleTypeEnum;
use Carbon\Carbon;
use Database\Factories\VehicleFactory;
use Illuminate\Database\Eloquent\Attributes\UseFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[UseFactory(VehicleFactory::class)]
final class Vehicle extends Model
{
    use HasFactory;

    protected $fillable = [
        'company_id',
        'brand',
        'model',
        'year',
        'color',
        'vin',
        'capacity_kg',
        'fuel_capacity',
        'license_plate',
        'vehicle_type',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'bool',
        'vehicle_type' => VehicleTypeEnum::class,
    ];

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function trips(): HasMany
    {
        return $this->hasMany(Trip::class);
    }

    public function drivers(): BelongsToMany
    {
        return $this->belongsToMany(Driver::class, 'trips')->distinct();
    }

    /**
     * Check if vehicle is available for a given time period
     */
    public function isAvailable(Carbon $start, Carbon $end, ?int $excludeTripId = null): bool
    {
        $query = $this->trips()
            ->where(function ($q) use ($start, $end) {
                $q->where(function ($query) use ($start, $end) {
                    // Trip starts before our period ends and ends after our period starts
                    $query->where('scheduled_start', '<', $end)
                        ->where('scheduled_end', '>', $start);
                });
            })
            ->whereNotIn('status', [TripStatusEnum::COMPLETED, TripStatusEnum::CANCELLED]);

        if ($excludeTripId) {
            $query->where('id', '!=', $excludeTripId);
        }

        return ! $query->exists();
    }

    /**
     * Get overlapping trips for a given time period
     */
    public function getOverlappingTrips(Carbon $start, Carbon $end, ?int $excludeTripId = null)
    {
        $query = $this->trips()
            ->where(function ($query) use ($start, $end) {
                //                $q->where(function ($query) use ($start, $end) {
                $query->where('scheduled_start', '<', $end)
                    ->where('scheduled_end', '>', $start);
                //                });
            })
            ->whereNotIn('status', [TripStatusEnum::COMPLETED, TripStatusEnum::CANCELLED]);

        if ($excludeTripId) {
            $query->where('id', '!=', $excludeTripId);
        }

        return $query->get();
    }

    public function getDisplayNameAttribute(): string
    {
        return "{$this->brand} - {$this->model} ({$this->license_plate})";
    }

    public function getTotalTripsAttribute(): int
    {
        return $this->trips()->count();
    }

    public function getCompletedTripsAttribute(): int
    {
        return $this->trips()->where('status', TripStatusEnum::COMPLETED->value)->count();
    }
}
