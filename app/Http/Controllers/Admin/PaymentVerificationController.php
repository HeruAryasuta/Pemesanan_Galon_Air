<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Order;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class PaymentVerificationController extends Controller
{
    public function index(): View
    {
        $orders = Order::query()
            ->with('user')
            ->where('payment_method', 'bank_transfer')
            ->where('payment_status', 'pending_verification')
            ->latest('payment_submitted_at')
            ->paginate(15);

        return view('admin.payments.index', compact('orders'));
    }

    public function proof(Order $order): BinaryFileResponse
    {
        abort_unless($order->payment_method === 'bank_transfer' && $order->payment_proof_path, 404);

        $disk = Storage::disk('local');
        abort_unless($disk->exists($order->payment_proof_path), 404, 'Bukti pembayaran tidak ditemukan.');

        return response()->file($disk->path($order->payment_proof_path), [
            'X-Content-Type-Options' => 'nosniff',
            'Cache-Control' => 'private, no-store',
        ]);
    }

    public function review(Request $request, Order $order): RedirectResponse
    {
        $validated = $request->validate([
            'decision' => ['required', Rule::in(['approve', 'reject'])],
            'note' => ['nullable', 'string', 'max:1000', Rule::requiredIf($request->input('decision') === 'reject')],
        ]);

        try {
            DB::transaction(function () use ($order, $validated): void {
                $lockedOrder = Order::query()->whereKey($order->id)->lockForUpdate()->firstOrFail();

                if (
                    $lockedOrder->payment_method !== 'bank_transfer'
                    || $lockedOrder->payment_status !== 'pending_verification'
                    || ! $lockedOrder->payment_proof_path
                    || ! Storage::disk('local')->exists($lockedOrder->payment_proof_path)
                ) {
                    throw new \RuntimeException('Pembayaran ini tidak sedang menunggu verifikasi.');
                }

                if ($validated['decision'] === 'approve') {
                    $lockedOrder->update([
                        'payment_status' => 'paid',
                        'payment_review_note' => filled($validated['note'] ?? null) ? $validated['note'] : null,
                        'payment_reviewed_at' => now(),
                    ]);

                    return;
                }

                $lockedOrder->update([
                    'payment_status' => 'rejected',
                    'payment_review_note' => $validated['note'],
                    'payment_reviewed_at' => now(),
                ]);
            });
        } catch (\RuntimeException $e) {
            throw ValidationException::withMessages(['decision' => $e->getMessage()]);
        } catch (\Throwable $e) {
            Log::error('Gagal meninjau pembayaran order #' . $order->id . ': ' . $e->getMessage());

            return back()->with('error', 'Pembayaran gagal diperbarui. Silakan coba lagi.');
        }

        $message = $validated['decision'] === 'approve'
            ? 'Pembayaran transfer berhasil diverifikasi.'
            : 'Bukti pembayaran ditolak. Pelanggan dapat mengunggah bukti baru.';

        return redirect()->route('admin.orders.show', $order)->with('success', $message);
    }
}
