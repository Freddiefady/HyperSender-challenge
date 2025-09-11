<?php

declare(strict_types=1);

namespace App\Observers;

use App\Models\Trip;

final class TripObserver
{
    /**
     * Handle the Trip "created" event.
     */
    public function creating(Trip $trip): void
    {
        if (empty($trip->trip_number)) {
            $trip->trip_number = 'TRP-'.mb_strtoupper(uniqid());
        }
    }

    /**
     * Handle the Trip "updated" event.
     */
    public function updated(Trip $trip): void
    {
        //
    }

    /**
     * Handle the Trip "deleted" event.
     */
    public function deleted(Trip $trip): void
    {
        //
    }

    /**
     * Handle the Trip "restored" event.
     */
    public function restored(Trip $trip): void
    {
        //
    }

    /**
     * Handle the Trip "force deleted" event.
     */
    public function forceDeleted(Trip $trip): void
    {
        //
    }
}
