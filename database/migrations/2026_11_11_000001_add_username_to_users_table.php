<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table): void {
            $table->string('username')->nullable()->unique()->after('name');
        });

        $depotCode = Str::of(config('courier.depot_code', 'PWD'))->ascii()->upper()->replaceMatches('/[^A-Z0-9]/', '');
        $couriers = DB::table('couriers')
            ->join('users', 'users.id', '=', 'couriers.user_id')
            ->select('users.id as user_id', 'couriers.id as courier_id', 'couriers.name')
            ->orderBy('couriers.id')
            ->get();

        foreach ($couriers as $courier) {
            $firstName = Str::of($courier->name)->ascii()->lower()->explode(' ')->first();
            $namePart = preg_replace('/[^a-z0-9]/', '', $firstName) ?: 'kurir';
            $username = $namePart.str_pad((string) $courier->courier_id, 2, '0', STR_PAD_LEFT).$depotCode;

            DB::table('users')->where('id', $courier->user_id)->update(['username' => $username]);
        }
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table): void {
            $table->dropUnique(['username']);
            $table->dropColumn('username');
        });
    }
};
