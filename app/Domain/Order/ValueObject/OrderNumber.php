<?php

declare(strict_types=1);

namespace App\Domain\Order\ValueObject;

/**
 * Order Number Value Object
 * 
 * Encapsulates order number generation logic
 * Format: ORD-YYYYMMDD-RANDOM6
 */
final readonly class OrderNumber
{
    private function __construct(
        public string $value,
    ) {
    }

    public static function generate(): self
    {
        $date = date('Ymd');
        $random = strtoupper(substr(uniqid(), -6));
        
        return new self("ORD-{$date}-{$random}");
    }

    public static function fromString(string $value): self
    {
        if (!self::isValid($value)) {
            throw new \InvalidArgumentException("Invalid order number format: {$value}");
        }

        return new self($value);
    }

    public static function isValid(string $value): bool
    {
        // Format: ORD-20251016-ABC123
        return preg_match('/^ORD-\d{8}-[A-Z0-9]{6}$/', $value) === 1;
    }

    public function __toString(): string
    {
        return $this->value;
    }
}
