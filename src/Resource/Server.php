<?php

declare(strict_types=1);

namespace PHPWhoisLite\Resource;

final readonly class Server
{
    public function __construct(
        public string $server,
        public ServerTypeEnum $type,
    ) {
    }

    public function isEqual(self $server): bool
    {
        return $this->server === $server->server && $this->type === $server->type;
    }
}
