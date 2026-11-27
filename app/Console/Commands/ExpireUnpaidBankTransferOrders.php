<?php

namespace App\Console\Commands;

use App\Models\Order;
use App\Services\OrderCancellationService;
use Illuminate\Console\Command;

class ExpireUnpaidBankTransferOrders extends Command
{
    protected $signature = 'orders:expire-unpaid-bank-transfers';

    protected $description = 'Cancel expired unpaid bank-transfer orders and restore reserved stock';

    public function handle(OrderCancellationService $cancellationService): int
    {
        $expiredCount = 0;

        Order::query()
            ->where('payment_method', 'bank_transfer')
            ->whereIn('payment_status', ['unpaid', 'rejected'])
            ->whereIn('status', ['pending', 'confirmed'])
            ->whereNotNull('payment_deadline_at')
            ->where('payment_deadline_at', '<=', now())
            ->orderBy('id')
            ->chunkById(100, function ($orders) use ($cancellationService, &$expiredCount): void {
                foreach ($orders as $order) {
                    if ($cancellationService->expireUnpaidTransfer($order)) {
                        $expiredCount++;
                    }
                }
            });

        $this->info("Expired {$expiredCount} unpaid bank-transfer order(s).");

        return self::SUCCESS;
    }
}
