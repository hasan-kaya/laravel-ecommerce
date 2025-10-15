<?php

declare(strict_types=1);

namespace App\Domain\Shared\Exceptions;

use Exception;

abstract class DomainException extends Exception
{
    public function __construct(string $message, int $code = 400)
    {
        parent::__construct($message, $code);
    }
}
