<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table): void {
            $table->string('order_address_label')->nullable();
            $table->text('order_address_text')->nullable();
            $table->decimal('order_latitude', 10, 7)->nullable();
            $table->decimal('order_longitude', 10, 7)->nullable();
            $table->unsignedBigInteger('order_service_zone_id')->nullable()->index();
            $table->string('order_service_zone_name')->nullable();
            $table->string('order_service_zone_area')->nullable();
            $table->timestamp('cancelled_at')->nullable();
            $table->foreignId('cancelled_by')->nullable()->constrained('users')->nullOnDelete();
            $table->text('cancellation_reason')->nullable();
            $table->timestamp('stock_restored_at')->nullable();
            $table->string('refund_status', 20)->default('not_required');
            $table->string('refund_reference')->nullable();
            $table->text('refund_note')->nullable();
            $table->timestamp('refunded_at')->nullable();
            $table->foreignId('refund_processed_by')->nullable()->constrained('users')->nullOnDelete();
        });

        DB::table('orders')
            ->join('addresses', 'addresses.id', '=', 'orders.address_id')
            ->leftJoin('service_zones', 'service_zones.id', '=', 'addresses.service_zone_id')
            ->select([
                'orders.id',
                'addresses.label',
                'addresses.full_address',
                'addresses.latitude',
                'addresses.longitude',
                'addresses.service_zone_id',
                'service_zones.name as service_zone_name',
                'service_zones.area_description as service_zone_area',
            ])
            ->orderBy('orders.id')
            ->chunkById(100, function ($orders): void {
                foreach ($orders as $order) {
                    DB::table('orders')
                        ->where('id', $order->id)
                        ->update([
                            'order_address_label' => $order->label,
                            'order_address_text' => $order->full_address,
                            'order_latitude' => $order->latitude,
                            'order_longitude' => $order->longitude,
                            'order_service_zone_id' => $order->service_zone_id,
                            'order_service_zone_name' => $order->service_zone_name,
                            'order_service_zone_area' => $order->service_zone_area,
                        ]);
                }
            }, 'orders.id', 'id');
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table): void {
            $table->dropConstrainedForeignId('cancelled_by');
            $table->dropConstrainedForeignId('refund_processed_by');
            $table->dropIndex(['order_service_zone_id']);
            $table->dropColumn([
                'order_address_label',
                'order_address_text',
                'order_latitude',
                'order_longitude',
                'order_service_zone_id',
                'order_service_zone_name',
                'order_service_zone_area',
                'cancelled_at',
                'cancellation_reason',
                'stock_restored_at',
                'refund_status',
                'refund_reference',
                'refund_note',
                'refunded_at',
            ]);
        });
    }
};
