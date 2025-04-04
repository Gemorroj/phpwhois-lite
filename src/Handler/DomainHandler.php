<?php

declare(strict_types=1);

namespace PHPWhoisLite\Handler;

use Algo26\IdnaConvert\Exception\AlreadyPunycodeException;
use Algo26\IdnaConvert\Exception\InvalidCharacterException as Algo26InvalidCharacterException;
use Algo26\IdnaConvert\ToIdn;
use PHPWhoisLite\Exception\InvalidCharacterException;
use PHPWhoisLite\Exception\InvalidWhoisServerException;
use PHPWhoisLite\Exception\NetworkException;
use PHPWhoisLite\Exception\QueryRateLimitExceededException;
use PHPWhoisLite\Exception\RegistrarServerException;
use PHPWhoisLite\Exception\TimeoutException;
use PHPWhoisLite\HandlerInterface;
use PHPWhoisLite\NetworkClient\NetworkClient;
use PHPWhoisLite\NetworkClient\RdapResponse;
use PHPWhoisLite\NetworkClient\WhoisResponse;
use PHPWhoisLite\Resource\Server;
use PHPWhoisLite\Resource\ServerTypeEnum;
use PHPWhoisLite\Resource\TldServerList;
use PHPWhoisLite\Response\DomainRegistrarResponse;
use PHPWhoisLite\Response\DomainResponse;
use Psr\Cache\InvalidArgumentException;

final readonly class DomainHandler implements HandlerInterface
{
    public function __construct(private NetworkClient $networkClient, private TldServerList $serverList = new TldServerList())
    {
    }

    /**
     * @throws InvalidArgumentException
     * @throws InvalidCharacterException
     * @throws QueryRateLimitExceededException
     * @throws TimeoutException
     * @throws NetworkException
     * @throws \JsonException
     */
    public function process(string $query, ?Server $forceServer = null): DomainResponse
    {
        try {
            $query = (new ToIdn())->convert($query);
        } catch (AlreadyPunycodeException) {
            // $query is already a Punycode
        } catch (Algo26InvalidCharacterException $e) {
            throw new InvalidCharacterException('Invalid query: '.$query, previous: $e);
        }

        $server = $forceServer ?? $this->findTldServer($query);

        $q = $this->prepareServerQuery($server, $query);
        $response = $this->networkClient->getResponse($server, $q);
        $domainRegistrarResponse = null;

        if (!$forceServer) {
            $registrarServer = $this->findRegistrarServer($response);
            if ($registrarServer && !$registrarServer->isEqual($server)) {
                $q = $this->prepareServerQuery($registrarServer, $query);
                try {
                    $registrarResponse = $this->networkClient->getResponse($registrarServer, $q);
                    $domainRegistrarResponse = new DomainRegistrarResponse($registrarResponse, $registrarServer);
                } catch (\Exception $e) {
                    $domainRegistrarResponse = new RegistrarServerException('Can\'t load info from registrar server.', previous: $e);
                }
            }
        }

        return new DomainResponse(
            $response,
            $server,
            $domainRegistrarResponse,
        );
    }

    private function findTldServer(string $query): Server
    {
        if (0 === \substr_count($query, '.')) {
            return $this->serverList->serverDefault;
        }

        $dp = \explode('.', $query);
        $np = \count($dp) - 1;
        $tldTests = [];

        for ($i = 0; $i < $np; ++$i) {
            \array_shift($dp);
            $tldTests[] = '.'.\implode('.', $dp);
        }

        foreach ($tldTests as $tld) {
            if (isset($this->serverList->servers[$tld])) {
                return $this->serverList->servers[$tld];
            }
        }

        return $this->serverList->serverDefault;
    }

    private function prepareServerQuery(Server $server, string $query): string
    {
        if (ServerTypeEnum::RDAP === $server->type) {
            return '/domain/'.$query;
        }

        return $query;
    }

    private function findRegistrarServer(RdapResponse|WhoisResponse $response): ?Server
    {
        // https://rdap.verisign.com/com/v1/domain/vk.com
        if ($response instanceof RdapResponse) {
            // https://datatracker.ietf.org/doc/html/rfc9083#name-links
            $links = $response->data['links'] ?? [];
            foreach ($links as $link) {
                if ($link['href'] && 'related' === $link['rel'] && 'application/rdap+json' === $link['type']) {
                    $href = \strtolower($link['href']);
                    $posDomain = \strpos($href, '/domain/');
                    if (false !== $posDomain) {
                        $href = \substr($href, 0, $posDomain);
                    }

                    return new Server($href, ServerTypeEnum::RDAP);
                }
            }
            // https://datatracker.ietf.org/doc/html/rfc9083#section-4.7
            /*if (isset($response->data['port43'])) {
                return new Server($response->data['port43'], ServerTypeEnum::WHOIS);
            }*/

            return null;
        }

        $matches = [];
        if (\preg_match('/Registrar WHOIS Server:(.+)/i', $response->data, $matches)) {
            $server = \trim($matches[1]);
            if ('' === $server) {
                return null;
            }

            try {
                return $this->prepareWhoisServer($server);
            } catch (InvalidWhoisServerException) {
                return null;
            }
        }

        return null;
    }

    /**
     * @throws InvalidWhoisServerException
     */
    private function prepareWhoisServer(string $whoisServer): Server
    {
        $whoisServer = \strtolower($whoisServer);

        if (\str_starts_with($whoisServer, 'rwhois://')) {
            $whoisServer = \substr($whoisServer, 9);
        }
        if (\str_starts_with($whoisServer, 'whois://')) {
            $whoisServer = \substr($whoisServer, 8);
        }

        $parsedWhoisServer = \parse_url($whoisServer);
        if (isset($parsedWhoisServer['scheme'])) {
            throw new InvalidWhoisServerException('Invalid WHOIS server path.');
        }
        if (isset($parsedWhoisServer['path']) && $parsedWhoisServer['path'] === $whoisServer) {
            // https://stackoverflow.com/questions/1418423/the-hostname-regex
            if (!\preg_match('/^(?=.{1,255}$)[0-9A-Za-z](?:(?:[0-9A-Za-z]|-){0,61}[0-9A-Za-z])?(?:\.[0-9A-Za-z](?:(?:[0-9A-Za-z]|-){0,61}[0-9A-Za-z])?)*\.?$/', $parsedWhoisServer['path'])) {
                // something strange path. /passwd for example
                throw new InvalidWhoisServerException('Invalid WHOIS server path.');
            }
        }

        return new Server(
            $whoisServer,
            ServerTypeEnum::WHOIS,
        );
    }
}
