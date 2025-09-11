<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\TripStatusEnum;
use App\Observers\TripObserver;
use Carbon\Carbon;
use Database\Factories\TripFactory;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Attributes\UseFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[ObservedBy(TripObserver::class)]
#[UseFactory(TripFactory::class)]
final class Trip extends Model
{
    use HasFactory;

    protected $fillable = [
        'company_id',
        'driver_id',
        'vehicle_id',
        'trip_number',
        'origin',
        'destination',
        'scheduled_start',
        'scheduled_end',
        'actual_start',
        'actual_end',
        'status',
        'distance_km',
        'fuel_consumed',
        'notes',
    ];

    protected $casts = [
        'scheduled_start' => 'datetime',
        'scheduled_end' => 'datetime',
        'actual_start' => 'datetime',
        'actual_end' => 'datetime',
        'distance_km' => 'decimal:2',
        'fuel_consumed' => 'decimal:2',
        'status' => TripStatusEnum::class,
    ];

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function driver(): BelongsTo
    {
        return $this->belongsTo(Driver::class);
    }

    public function vehicle(): BelongsTo
    {
        return $this->belongsTo(Vehicle::class);
    }

    /**
     * Check if this trip overlaps with another time period
     */
    public function overlaps(Carbon $start, Carbon $end): bool
    {
        return $this->scheduled_start < $end && $this->scheduled_end > $start;
    }

    /**
     * Get the duration of the scheduled trip in hours
     */
    public function getScheduledDurationAttribute(): float
    {
        return $this->scheduled_start->diffInHours($this->scheduled_end, true);
    }

    /**
     * Get the actual duration of the trip in hours (if completed)
     */
    public function getActualDurationAttribute(): ?float
    {
        if (! $this->actual_start || ! $this->actual_end) {
            return null;
        }

        return $this->actual_start->diffInHours($this->actual_end, true);
    }

    /**
     * Check if the trip is currently active (in progress)
     */
    public function getIsActiveAttribute(): bool
    {
        return $this->status === TripStatusEnum::IN_PROGRESS->value ||
            $this->status === TripStatusEnum::SCHEDULED->value;
    }

    /**
     * Check if the trip is overdue (scheduled to start but hasn't)
     */
    public function getIsOverdueAttribute(): bool
    {
        return $this->status === TripStatusEnum::SCHEDULED->value &&
            $this->scheduled_start < Carbon::now();
    }

    /**
     * Get fuel efficiency (km per liter)
     */
    public function getFuelEfficiencyAttribute(): ?float
    {
        if (! $this->distance_km || ! $this->fuel_consumed || $this->fuel_consumed === 0) {
            return null;
        }

        return round($this->distance_km / $this->fuel_consumed, 2);
    }

    /**
     * Scope for active trips
     */
    #[Scope]
    protected function active(Builder $query)
    {
        return $query->whereIn(
            'status',
            [TripStatusEnum::SCHEDULED->value, TripStatusEnum::IN_PROGRESS->value]
        );
    }

    /**
     * Scope for completed trips
     */
    #[Scope]
    protected function completed(Builder $query)
    {
        return $query->where('status', TripStatusEnum::COMPLETED->value);
    }

    #[Scope]
    protected function inProgress(Builder $query)
    {
        return $query->where('status', TripStatusEnum::IN_PROGRESS->value);
    }

    #[Scope]
    protected function cancelled(Builder $query)
    {
        return $query->where('status', TripStatusEnum::CANCELLED->value);
    }
}
