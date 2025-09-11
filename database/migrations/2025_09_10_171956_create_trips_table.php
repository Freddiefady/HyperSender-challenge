<?php

declare(strict_types=1);

use App\Enums\TripStatusEnum;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class() extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('trips', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained('companies')->onDelete('cascade');
            $table->foreignId('driver_id')->constrained('drivers')->onDelete('cascade');
            $table->foreignId('vehicle_id')->constrained('vehicles')->onDelete('cascade');

            $table->string('trip_number')->unique();
            $table->string('origin');
            $table->string('destination');
            $table->datetime('scheduled_start');
            $table->datetime('scheduled_end');
            $table->datetime('actual_start')->nullable();
            $table->datetime('actual_end')->nullable();
            $table->string('status')->default(TripStatusEnum::SCHEDULED->value);
            $table->decimal('distance_km', 8, 2)->nullable();
            $table->decimal('fuel_consumed', 8, 2)->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();

            // Indexes for performance
            $table->index(['scheduled_start', 'scheduled_end']);
            $table->index(['driver_id', 'scheduled_start', 'scheduled_end']);
            $table->index(['vehicle_id', 'scheduled_start', 'scheduled_end']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('trips');
    }
};
