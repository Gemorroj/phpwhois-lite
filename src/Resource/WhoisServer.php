<?php

declare(strict_types=1);

namespace PHPWhoisLite\Resource;

final readonly class WhoisServer
{
    public function __construct(
        public string $tld,
        public string $server,
    ) {
    }
}
