<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sensor_readings', function (Blueprint $table) {
            $table->id('reading_id');
            $table->decimal('temperature', 6, 2)->comment('Celsius');
            $table->decimal('ph', 4, 2)->comment('pH level');
            $table->decimal('gas_level', 8, 2)->comment('ppm');
            $table->decimal('pressure', 6, 2)->comment('kPa');
            $table->decimal('flow_rate', 8, 4)->comment('L/min');
            $table->unsignedTinyInteger('status')->default(0)->comment('0=normal, 1=warning, 2=critical');
            $table->boolean('valve_open')->default(false);
            $table->timestamp('recorded_at')->useCurrent();
            $table->timestamps();

            $table->index('recorded_at');
            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sensor_readings');
    }
};
