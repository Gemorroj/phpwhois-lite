<?php

declare(strict_types=1);

namespace WhoRdap\Exception;

final class InvalidResponseException extends NetworkException
{
    public function __construct(string $message, public readonly string $response)
    {
        parent::__construct($message);
    }
}
