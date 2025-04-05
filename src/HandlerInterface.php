<?php

declare(strict_types=1);

namespace WhoRdap;

use WhoRdap\Response\RdapAsnResponse;
use WhoRdap\Response\RdapDomainResponse;
use WhoRdap\Response\RdapIpResponse;
use WhoRdap\Response\WhoisAsnResponse;
use WhoRdap\Response\WhoisDomainResponse;
use WhoRdap\Response\WhoisIpResponse;

interface HandlerInterface
{
    public function processWhois(string $query, ?string $forceServer = null): WhoisIpResponse|WhoisAsnResponse|WhoisDomainResponse;

    public function processRdap(string $query, ?string $forceServer = null): RdapIpResponse|RdapAsnResponse|RdapDomainResponse;
}
