<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('couriers', function (Blueprint $table): void {
            $table->text('nik')->nullable()->after('phone');
        });

        Schema::table('courier_applications', function (Blueprint $table): void {
            $table->text('nik')->nullable()->after('phone');
        });
    }

    public function down(): void
    {
        Schema::table('couriers', function (Blueprint $table): void {
            $table->dropColumn('nik');
        });

        Schema::table('courier_applications', function (Blueprint $table): void {
            $table->dropColumn('nik');
        });
    }
};
