<?php

declare(strict_types=1);

use App\Enums\VehicleTypeEnum;
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
        Schema::create('vehicles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained('companies')->onDelete('cascade');
            $table->foreignId('driver_id')->nullable()->constrained('drivers')->onDelete('cascade');
            $table->string('vehicle_type')->default(VehicleTypeEnum::CAR);
            $table->string('brand');
            $table->string('model');
            $table->string('color');
            $table->string('year');
            $table->string('vin')->unique();
            $table->string('license_plate')->unique();
            $table->integer('capacity_kg')->nullable();
            $table->decimal('fuel_capacity', 8, 2)->nullable();
            $table->string('is_active')->default(true);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('vehicles');
    }
};
