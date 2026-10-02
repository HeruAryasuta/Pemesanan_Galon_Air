<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table): void {
            $table->string('payment_proof_path')->nullable();
            $table->text('payment_review_note')->nullable();
            $table->timestamp('payment_submitted_at')->nullable();
            $table->timestamp('payment_reviewed_at')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table): void {
            $table->dropColumn([
                'payment_proof_path',
                'payment_review_note',
                'payment_submitted_at',
                'payment_reviewed_at',
            ]);
        });
    }
};
