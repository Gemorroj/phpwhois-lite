<?php

declare(strict_types=1);

namespace PHPWhoisLite\Handler;

use Algo26\IdnaConvert\Exception\AlreadyPunycodeException;
use Algo26\IdnaConvert\ToIdn;
use PHPWhoisLite\Data;
use PHPWhoisLite\HandlerInterface;

final readonly class DomainHandler implements HandlerInterface
{
    public function parse(string $query): ?Data
    {
        try {
            $query = (new ToIdn())->convert($query);
        } catch (AlreadyPunycodeException $e) {
            // $query is already a Punycode
        }

        throw new \Exception('Not implemented');
    }
}
