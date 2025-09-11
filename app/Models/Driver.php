<?php

declare(strict_types=1);

namespace App\Models;

use Carbon\Carbon;
use Database\Factories\DriverFactory;
use Illuminate\Database\Eloquent\Attributes\UseFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[UseFactory(DriverFactory::class)]
final class Driver extends Model
{
    use HasFactory;

    protected $fillable = [
        'company_id',
        'name',
        'email',
        'phone',
        'license_number',
        'license_expiry',
        'is_active',
    ];

    protected $casts = [
        'license_expiry' => 'date',
        'is_active' => 'bool',
    ];

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function trips(): HasMany
    {
        return $this->hasMany(Trip::class);
    }

    public function vehicles(): BelongsToMany
    {
        return $this->belongsToMany(Vehicle::class, 'trips')->distinct();
    }

    /**
     * Check if driver is available for a given time period
     */
    public function isAvailable(Carbon $start, Carbon $end, ?int $excludeTripId = null): bool
    {
        $query = $this->trips()
            ->where(function ($q) use ($start, $end) {
                //                $q->where(function ($query) use ($start, $end) {
                // Trip starts before our period ends and ends after our period starts
                $q->where('scheduled_start', '<', $end)
                    ->where('scheduled_end', '>', $start);
                //                });
            })
            ->whereNotIn('status', ['completed', 'cancelled']);

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
            ->where(function ($q) use ($start, $end) {
                $q->where(function ($query) use ($start, $end) {
                    $query->where('scheduled_start', '<', $end)
                        ->where('scheduled_end', '>', $start);
                });
            })
            ->whereNotIn('status', ['completed', 'cancelled']);

        if ($excludeTripId) {
            $query->where('id', '!=', $excludeTripId);
        }

        return $query->get();
    }

    public function getIsLicenseExpiredAttribute(): bool
    {
        return $this->license_expiry < Carbon::now();
    }

    public function getIsLicenseExpiringSoonAttribute(): bool
    {
        return $this->license_expiry <= Carbon::now()->addDays(30);
    }
}
