<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('digester_status', function (Blueprint $table) {
            $table->id('status_id');
            $table->foreignId('reading_id')->constrained('sensor_readings', 'reading_id');
            $table->string('valve_status')->default('closed')->comment('open, closed');
            $table->string('overall_status')->default('normal')->comment('normal, warning, critical');
            $table->timestamp('updated_at')->useCurrent();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('digester_status');
    }
};
