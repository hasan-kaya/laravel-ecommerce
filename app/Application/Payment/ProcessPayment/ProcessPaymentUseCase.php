<?php

declare(strict_types=1);

namespace App\Application\Payment\ProcessPayment;

use App\Domain\Payment\Repository\PaymentRepositoryInterface;
use App\Domain\Payment\ValueObject\PaymentResult;
use App\Domain\Payment\Contract\PaymentServiceFactoryInterface;

/**
 * Process Payment Use Case
 *
 * Single Responsibility: Handle payment processing logic
 * Encapsulates payment provider selection and execution
 */
final readonly class ProcessPaymentUseCase
{
    public function __construct(
        private PaymentServiceFactoryInterface $paymentServiceFactory,
        private PaymentRepositoryInterface $paymentRepository,
    ) {
    }

    public function execute(ProcessPaymentCommand $command): PaymentResult
    {
        // 1. Get payment service via Factory (Strategy Pattern)
        $paymentService = $this->paymentServiceFactory->get($command->method);

        // 2. Process payment with gateway (Gateway returns payment_id as idempotency key)
        $response = $paymentService->process($command->amount, $command->metadata);

        // 3. Gateway returns payment_id (like Stripe's payment_intent_id or iyzico's paymentId)
        $paymentIdFromGateway = $response['payment_id'];

        // 4. Check if payment already exists with this payment_id (idempotency check)
        $existingPayment = $this->paymentRepository->findByIdempotencyKey($paymentIdFromGateway);

        if ($existingPayment) {
            // Payment already processed, return existing result
            return $existingPayment['status'] === 'success'
                ? PaymentResult::success(
                    transactionId: $existingPayment['transaction_id'] ?? '',
                    method: $command->method,
                    message: 'Payment already processed (idempotent)'
                )
                : PaymentResult::failure(
                    method: $command->method,
                    message: $existingPayment['error_message'] ?? 'Payment already failed'
                );
        }

        // 5. Create payment record with gateway's payment_id as idempotency_key
        $paymentRecordId = $this->paymentRepository->create([
            'idempotency_key' => $paymentIdFromGateway, // Gateway's payment_id
            'order_id' => $command->orderId,
            'payment_method' => $command->method->value,
            'amount' => $command->amount,
            'status' => $response['success'] ? 'success' : 'failed',
            'transaction_id' => $response['transaction_id'],
            'error_message' => $response['success'] ? null : $response['message'],
            'processed_at' => now(),
            'metadata' => $command->metadata,
        ]);

        // 6. Return result
        if ($response['success']) {
            return PaymentResult::success(
                transactionId: $response['transaction_id'],
                method: $command->method,
                message: $response['message']
            );
        }

        return PaymentResult::failure(
            method: $command->method,
            message: $response['message']
        );
    }
}
