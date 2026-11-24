<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('service_zones', function (Blueprint $table): void {
            $table->id();
            $table->string('code', 20)->unique();
            $table->string('name', 100);
            $table->string('area_description', 255);
            $table->unsignedSmallInteger('slot_count')->default(1);
            $table->timestamps();
        });

        $now = now();
        DB::table('service_zones')->insert([
            ['code' => 'WIL-01', 'name' => 'Wilayah 1', 'area_description' => 'Waru / Wadung Asri', 'slot_count' => 1, 'created_at' => $now, 'updated_at' => $now],
            ['code' => 'WIL-02', 'name' => 'Wilayah 2', 'area_description' => 'Gedangan', 'slot_count' => 1, 'created_at' => $now, 'updated_at' => $now],
            ['code' => 'WIL-03', 'name' => 'Wilayah 3', 'area_description' => 'Sedati', 'slot_count' => 1, 'created_at' => $now, 'updated_at' => $now],
            ['code' => 'WIL-04', 'name' => 'Wilayah 4', 'area_description' => 'Rungkut', 'slot_count' => 1, 'created_at' => $now, 'updated_at' => $now],
            ['code' => 'WIL-05', 'name' => 'Wilayah 5', 'area_description' => 'Gunung Anyar', 'slot_count' => 1, 'created_at' => $now, 'updated_at' => $now],
            ['code' => 'WIL-06', 'name' => 'Wilayah 6', 'area_description' => 'Sukolilo', 'slot_count' => 1, 'created_at' => $now, 'updated_at' => $now],
        ]);

        Schema::table('couriers', function (Blueprint $table): void {
            $table->foreignId('service_zone_id')->nullable()->after('vehicle_type')->constrained()->nullOnDelete();
        });

        Schema::table('addresses', function (Blueprint $table): void {
            $table->foreignId('service_zone_id')->nullable()->after('full_address')->constrained()->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('addresses', function (Blueprint $table): void {
            $table->dropConstrainedForeignId('service_zone_id');
        });

        Schema::table('couriers', function (Blueprint $table): void {
            $table->dropConstrainedForeignId('service_zone_id');
        });

        Schema::dropIfExists('service_zones');
    }
};
