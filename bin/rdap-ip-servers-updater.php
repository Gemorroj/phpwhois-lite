#!/usr/bin/env php
<?php

require_once __DIR__.'/servers-updater/_functions.php';

$template = '<?php

declare(strict_types=1);

namespace WhoRdap\Resource;

use WhoRdap\IpServerListInterface;

class RdapIpServerList implements IpServerListInterface
{
    public string $serverDefault;
    /**
     * @var array<string, string>
     */
    public array $serversIpv4;
        /**
     * @var array<string, string>
     */
    public array $serversIpv6;

    public function __construct()
    {
        $this->serverDefault = \'https://rdap.arin.net/registry\';
        $this->serversIpv4 = [];
        $this->serversIpv6 = [];
    }
}';

$writePath = __DIR__.'/../src/Resource/RdapIpServerList.php';
$startTime = \microtime(true);

$ipv4Servers = require __DIR__.'/servers-updater/_parser_rdap_ipv4_iana.php';
$ipv6Servers = require __DIR__.'/servers-updater/_parser_rdap_ipv6_iana.php';

$countIpv4 = \count($ipv4Servers);
$countIpv6 = \count($ipv6Servers);

$templatedIpv4Servers = [];
foreach ($ipv4Servers as $ipv4 => $server) {
    $templatedIpv4Servers[] = \sprintf("'%s' => '%s',", $ipv4, $server);
}
$templatedIpv6Servers = [];
foreach ($ipv6Servers as $ipv6 => $server) {
    $templatedIpv6Servers[] = \sprintf("'%s' => '%s',", $ipv6, $server);
}

$templatedIpv4ServersStr = '$this->serversIpv4 = ['."\n".\implode("\n", $templatedIpv4Servers)."\n];";
$template = \str_replace('$this->serversIpv4 = [];', $templatedIpv4ServersStr, $template);

$templatedIpv6ServersStr = '$this->serversIpv6 = ['."\n".\implode("\n", $templatedIpv6Servers)."\n];";
$template = \str_replace('$this->serversIpv6 = [];', $templatedIpv6ServersStr, $template);

$write = \file_put_contents($writePath, $template);
if (false === $write) {
    throw new RuntimeException('Can\'t write to '.$writePath);
}

$endTime = \microtime(true);
$time = \round($endTime - $startTime, 2).' seconds';

echo 'RDAP servers updated. Now: '.$countIpv4.' Ipv4 and '.$countIpv6.' IPv6 servers'.\PHP_EOL;
echo 'Spent time: '.$time.\PHP_EOL;
echo 'Don\'t forget run php-cs-fixer'.\PHP_EOL;
