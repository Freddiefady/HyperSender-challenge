<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\TripStatusEnum;
use Database\Factories\CompanyFactory;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Attributes\UseFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[UseFactory(CompanyFactory::class)]
final class Company extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'description',
        'email',
        'phone',
        'address',
        'registration_number',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'bool',
    ];

    public function drivers(): HasMany
    {
        return $this->hasMany(Driver::class);
    }

    public function vehicles(): HasMany
    {
        return $this->hasMany(Vehicle::class);
    }

    public function trips(): HasMany
    {
        return $this->hasMany(Trip::class);
    }

    public function activeDrivers(): HasMany
    {
        return $this->drivers()->where('is_active', true);
    }

    public function activeVehicles(): HasMany
    {
        return $this->vehicles()->where('is_active', true);
    }

    public function getActiveTripsCountAttribute(): int
    {
        return $this->trips()->whereIn(
            'status',
            [TripStatusEnum::SCHEDULED->value, TripStatusEnum::IN_PROGRESS->value]
        )->count();
    }

    public function getTotalTripsCountAttribute(): int
    {
        return $this->trips()->count();
    }

    #[Scope]
    protected function active(Builder $query)
    {
        return $query->where('is_active', true);
    }
}
