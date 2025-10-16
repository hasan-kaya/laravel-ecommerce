<?php

declare(strict_types=1);

namespace App\Domain\Order\ValueObject;

/**
 * Line Total Value Object
 * 
 * Encapsulates line item calculation logic
 * Immutable value object representing price * quantity
 */
final readonly class LineTotal
{
    private function __construct(
        public float $unitPrice,
        public int $quantity,
        public float $total,
    ) {
    }

    public static function calculate(float $unitPrice, int $quantity): self
    {
        if ($unitPrice < 0) {
            throw new \InvalidArgumentException("Unit price cannot be negative");
        }

        if ($quantity <= 0) {
            throw new \InvalidArgumentException("Quantity must be greater than zero");
        }

        $total = round($unitPrice * $quantity, 2);

        return new self($unitPrice, $quantity, $total);
    }

    public function toArray(): array
    {
        return [
            'unit_price' => $this->unitPrice,
            'quantity' => $this->quantity,
            'total' => $this->total,
        ];
    }
}
