<?php

declare(strict_types=1);

namespace App\Application\Payment\ProcessPayment;

use App\Domain\Payment\PaymentResult;
use App\Domain\Payment\PaymentServiceFactoryInterface;

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
    ) {
    }

    public function execute(ProcessPaymentCommand $command): PaymentResult
    {
        // 1. Get payment service via Factory (Strategy Pattern)
        $paymentService = $this->paymentServiceFactory->get($command->method);

        // 2. Process payment
        $response = $paymentService->process($command->amount, $command->metadata);

        // 3. Map to domain PaymentResult
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
