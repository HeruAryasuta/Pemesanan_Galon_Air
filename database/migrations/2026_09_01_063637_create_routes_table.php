<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('routes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('courier_id')->constrained();
            $table->date('route_date');
            $table->enum('status', ['planned', 'in_progress', 'completed'])->default('planned');
            $table->unsignedInteger('total_distance_meters')->nullable();
            $table->unsignedInteger('total_duration_seconds')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('routes');
    }
};
