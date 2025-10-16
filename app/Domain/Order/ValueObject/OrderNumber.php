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
        // Use 4 bytes of cryptographically secure random = 8 hex characters
        // Collision probability: 1 in 4,294,967,296 (virtually impossible)
        $random = strtoupper(bin2hex(random_bytes(4)));
        
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
        // Format: ORD-20251016-ABC12345 (8 chars: microtime + random)
        return preg_match('/^ORD-\d{8}-[A-Z0-9]{8}$/', $value) === 1;
    }

    public function __toString(): string
    {
        return $this->value;
    }
}
