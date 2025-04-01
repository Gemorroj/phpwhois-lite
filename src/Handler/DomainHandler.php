<?php

declare(strict_types=1);

namespace PHPWhoisLite\Handler;

use Algo26\IdnaConvert\Exception\AlreadyPunycodeException;
use Algo26\IdnaConvert\ToIdn;
use PHPWhoisLite\Data;
use PHPWhoisLite\Exception\DomainWhoisServerNotFoundException;
use PHPWhoisLite\HandlerInterface;
use PHPWhoisLite\QueryTypeEnum;
use PHPWhoisLite\Resource\WhoisServerList;
use PHPWhoisLite\WhoisClient;
use PHPWhoisLite\WhoisServerDetectorTrait;

final readonly class DomainHandler implements HandlerInterface
{
    use WhoisServerDetectorTrait;

    public function __construct(private WhoisClient $whoisClient, private WhoisServerList $whoisServerList = new WhoisServerList())
    {
    }

    public function process(string $query): Data
    {
        // todo: gtld servers - https://stackoverflow.com/questions/18270575/the-list-of-all-com-and-net-whois-servers

        try {
            $query = (new ToIdn())->convert($query);
        } catch (AlreadyPunycodeException) {
            // $query is already a Punycode
        }

        $findServer = $this->findServer($this->whoisServerList, $query);
        if (null === $findServer) {
            throw DomainWhoisServerNotFoundException::create($query);
        }

        $raw = $this->whoisClient->getData($findServer, $query);

        $registrarServer = $this->findRegistrarServer($raw);
        if ($registrarServer && $registrarServer !== $findServer) {
            $raw = $this->whoisClient->getData($registrarServer, $query);
        }

        return new Data(
            $raw,
            $registrarServer ?? $findServer,
            QueryTypeEnum::DOMAIN,
        );
    }
}
