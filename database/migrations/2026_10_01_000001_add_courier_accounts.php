<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table): void {
            $table->enum('role', ['customer', 'admin', 'courier'])->default('customer')->change();
        });

        Schema::table('couriers', function (Blueprint $table): void {
            $table->foreignId('user_id')->nullable()->unique()->constrained()->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('couriers', function (Blueprint $table): void {
            $table->dropConstrainedForeignId('user_id');
        });

        DB::table('users')->where('role', 'courier')->update(['role' => 'customer']);

        Schema::table('users', function (Blueprint $table): void {
            $table->enum('role', ['customer', 'admin'])->default('customer')->change();
        });
    }
};
