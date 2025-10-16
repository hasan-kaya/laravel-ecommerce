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
        // 1. Create payment record (PENDING)
        $paymentId = $this->paymentRepository->create([
            'order_id' => $command->orderId,
            'payment_method' => $command->method->value,
            'amount' => $command->amount,
            'status' => 'pending',
            'attempt_number' => $command->attemptNumber,
            'metadata' => $command->metadata,
        ]);

        // 2. Get payment service via Factory (Strategy Pattern)
        $paymentService = $this->paymentServiceFactory->get($command->method);

        // 3. Process payment with gateway
        $response = $paymentService->process($command->amount, $command->metadata);

        // 4. Update payment record based on result
        if ($response['success']) {
            $this->paymentRepository->updateStatus(
                $paymentId,
                'success',
                $response['transaction_id'],
                null
            );

            return PaymentResult::success(
                transactionId: $response['transaction_id'],
                method: $command->method,
                message: $response['message']
            );
        }

        $this->paymentRepository->updateStatus(
            $paymentId,
            'failed',
            null,
            $response['message']
        );

        return PaymentResult::failure(
            method: $command->method,
            message: $response['message']
        );
    }
}
