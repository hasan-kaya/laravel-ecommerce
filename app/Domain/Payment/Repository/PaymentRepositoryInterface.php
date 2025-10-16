<?php

declare(strict_types=1);

namespace App\Domain\Payment\Repository;

interface PaymentRepositoryInterface
{
    /**
     * Create a new payment record
     * 
     * @param array $data Payment data
     * @return int Payment ID
     */
    public function create(array $data): int;

    /**
     * Update payment status and details
     * 
     * @param int $paymentId
     * @param string $status
     * @param string|null $transactionId
     * @param string|null $errorMessage
     * @return bool
     */
    public function updateStatus(
        int $paymentId,
        string $status,
        ?string $transactionId = null,
        ?string $errorMessage = null
    ): bool;

    /**
     * Find payment by ID
     * 
     * @param int $id
     * @return array|null
     */
    public function findById(int $id): ?array;

    /**
     * Get all payments for an order
     * 
     * @param int $orderId
     * @return array
     */
    public function findByOrderId(int $orderId): array;

    /**
     * Find payment by idempotency key
     * 
     * @param string $idempotencyKey
     * @return array|null
     */
    public function findByIdempotencyKey(string $idempotencyKey): ?array;
}
