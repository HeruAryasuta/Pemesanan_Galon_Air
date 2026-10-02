<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use App\Models\Order;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;

class OrderPaymentController extends Controller
{
    public function store(Request $request, Order $order): RedirectResponse
    {
        abort_unless($order->user_id === $request->user()->id, 404);

        $validated = $request->validate([
            'payment_proof' => ['required', 'file', 'mimes:jpg,jpeg,png,webp,pdf', 'max:5120'],
        ]);

        if ($order->payment_method !== 'bank_transfer') {
            throw ValidationException::withMessages([
                'payment_proof' => 'Bukti transfer hanya dapat diunggah untuk pesanan dengan metode transfer bank.',
            ]);
        }

        if (in_array($order->status, ['cancelled', 'delivered'], true)) {
            throw ValidationException::withMessages([
                'payment_proof' => 'Pesanan ini sudah ditutup dan tidak menerima bukti pembayaran.',
            ]);
        }

        if (! in_array($order->payment_status, ['unpaid', 'rejected'], true)) {
            throw ValidationException::withMessages([
                'payment_proof' => 'Bukti pembayaran sedang ditinjau atau sudah terverifikasi.',
            ]);
        }

        $newProofPath = $validated['payment_proof']->store('payment-proofs/' . $order->id, 'local');

        try {
            DB::transaction(function () use ($order, $newProofPath): void {
                $lockedOrder = Order::query()->whereKey($order->id)->lockForUpdate()->firstOrFail();

                if (
                    $lockedOrder->payment_method !== 'bank_transfer'
                    || ! in_array($lockedOrder->payment_status, ['unpaid', 'rejected'], true)
                    || in_array($lockedOrder->status, ['cancelled', 'delivered'], true)
                ) {
                    throw new \RuntimeException('Pesanan ini tidak lagi dapat menerima bukti pembayaran.');
                }

                $lockedOrder->update([
                    'payment_proof_path' => $newProofPath,
                    'payment_status' => 'pending_verification',
                    'payment_review_note' => null,
                    'payment_submitted_at' => now(),
                    'payment_reviewed_at' => null,
                ]);
            });
        } catch (\Throwable $e) {
            Storage::disk('local')->delete($newProofPath);

            if ($e instanceof \RuntimeException) {
                throw ValidationException::withMessages(['payment_proof' => $e->getMessage()]);
            }

            Log::error('Gagal menyimpan bukti pembayaran order #' . $order->id . ': ' . $e->getMessage());

            return back()->withInput()->with('error', 'Bukti pembayaran gagal disimpan. Silakan coba lagi.');
        }

        if ($order->payment_proof_path && $order->payment_proof_path !== $newProofPath) {
            Storage::disk('local')->delete($order->payment_proof_path);
        }

        return redirect()->route('user.orders.show', $order)
            ->with('success', 'Bukti transfer berhasil dikirim dan menunggu verifikasi admin.');
    }
}
