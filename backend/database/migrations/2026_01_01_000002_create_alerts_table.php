<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('alerts', function (Blueprint $table) {
            $table->id('alert_id');
            $table->foreignId('reading_id')->constrained('sensor_readings', 'reading_id');
            $table->string('alert_type')->comment('overpressure, threshold_breach, warning, etc.');
            $table->string('severity')->comment('warning, critical');
            $table->text('message');
            $table->boolean('acknowledged')->default(false);
            $table->timestamp('created_at')->useCurrent();
            $table->timestamps();

            $table->index('alert_type');
            $table->index('severity');
            $table->index('created_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('alerts');
    }
};
