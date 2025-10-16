<?php

declare(strict_types=1);

namespace App\Infrastructure\Payment\Repository;

use App\Domain\Payment\Repository\PaymentRepositoryInterface;
use App\Infrastructure\Eloquent\Payment;

final readonly class EloquentPaymentRepository implements PaymentRepositoryInterface
{
    public function create(array $data): int
    {
        $payment = Payment::create($data);
        return $payment->id;
    }

    public function updateStatus(
        int $paymentId,
        string $status,
        ?string $transactionId = null,
        ?string $errorMessage = null
    ): bool {
        return Payment::where('id', $paymentId)->update([
            'status' => $status,
            'transaction_id' => $transactionId,
            'error_message' => $errorMessage,
            'processed_at' => now(),
        ]) > 0;
    }

    public function findById(int $id): ?array
    {
        $payment = Payment::find($id);

        if (!$payment) {
            return null;
        }

        return [
            'id' => $payment->id,
            'order_id' => $payment->order_id,
            'payment_method' => $payment->payment_method,
            'amount' => (float) $payment->amount,
            'status' => $payment->status,
            'transaction_id' => $payment->transaction_id,
            'error_message' => $payment->error_message,
            'processed_at' => $payment->processed_at?->toIso8601String(),
            'created_at' => $payment->created_at->toIso8601String(),
        ];
    }

    public function findByOrderId(int $orderId): array
    {
        return Payment::where('order_id', $orderId)
            ->orderBy('created_at', 'asc')
            ->get()
            ->map(fn($payment) => [
                'id' => $payment->id,
                'payment_method' => $payment->payment_method,
                'amount' => (float) $payment->amount,
                'status' => $payment->status,
                'transaction_id' => $payment->transaction_id,
                'error_message' => $payment->error_message,
                'processed_at' => $payment->processed_at?->toIso8601String(),
                'created_at' => $payment->created_at->toIso8601String(),
            ])
            ->toArray();
    }

    public function findByIdempotencyKey(string $idempotencyKey): ?array
    {
        $payment = Payment::where('idempotency_key', $idempotencyKey)->first();

        if (!$payment) {
            return null;
        }

        return [
            'id' => $payment->id,
            'order_id' => $payment->order_id,
            'payment_method' => $payment->payment_method,
            'amount' => (float) $payment->amount,
            'status' => $payment->status,
            'transaction_id' => $payment->transaction_id,
            'error_message' => $payment->error_message,
            'processed_at' => $payment->processed_at?->toIso8601String(),
            'created_at' => $payment->created_at->toIso8601String(),
        ];
    }
}
