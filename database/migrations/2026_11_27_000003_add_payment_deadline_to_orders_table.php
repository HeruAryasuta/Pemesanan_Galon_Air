<?php

use Carbon\Carbon;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table): void {
            $table->timestamp('payment_deadline_at')->nullable()->index();
        });

        $deadlineHours = (int) config('payments.bank_transfer.deadline_hours', 24);

        DB::table('orders')
            ->where('payment_method', 'bank_transfer')
            ->orderBy('id')
            ->chunkById(100, function ($orders) use ($deadlineHours): void {
                foreach ($orders as $order) {
                    $orderedAt = $order->ordered_at ?? $order->created_at;
                    if ($orderedAt === null) {
                        continue;
                    }

                    DB::table('orders')
                        ->where('id', $order->id)
                        ->update([
                            'payment_deadline_at' => Carbon::parse($orderedAt)->addHours($deadlineHours),
                        ]);
                }
            });
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table): void {
            $table->dropIndex(['payment_deadline_at']);
            $table->dropColumn('payment_deadline_at');
        });
    }
};
