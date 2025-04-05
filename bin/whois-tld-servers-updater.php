#!/usr/bin/env php
<?php

require_once __DIR__.'/servers-updater/_functions.php';

$template = '<?php

declare(strict_types=1);

namespace WhoRdap\Resource;

use WhoRdap\TldServerListInterface;

class WhoisTldServerList implements TldServerListInterface
{
    public string $serverDefault;
    /**
     * @var array<string, string>
     */
    public array $servers;

    public function __construct()
    {
        $this->serverDefault = \'whois.iana.org\';
        $this->servers = [];
    }
}';

$writePath = __DIR__.'/../src/Resource/WhoisTldServerList.php';
$startTime = \microtime(true);

$tldIanaServers = require __DIR__.'/servers-updater/_parser_whois_tld_iana.php';
$tldWebposServers = require __DIR__.'/servers-updater/_parser_whois_tld_webpos.php';
$tldWhoisserversServers = require __DIR__.'/servers-updater/_parser_whois_tld_whoisservers.php';

echo 'Use IANA servers as master repository.'.\PHP_EOL;
$tldServers = $tldIanaServers;
echo 'Prepare webpos servers...'.\PHP_EOL;
foreach ($tldWebposServers as $tld => $server) {
    if (!isset($tldServers[$tld])) {
        echo 'Add webpos server '.$server.' for '.$tld.\PHP_EOL;
        $tldServers[$tld] = $server;
    }
}
echo 'Prepare whoisservers servers...'.\PHP_EOL;
foreach ($tldWhoisserversServers as $tld => $server) {
    if (!isset($tldServers[$tld])) {
        echo 'Add whoisservers server '.$server.' for '.$tld.\PHP_EOL;
        $tldServers[$tld] = $server;
    }
}

$tldServers = \cleanupTldServers($tldServers);
$count = \count($tldServers);

$templatedTldServers = [];
foreach ($tldServers as $tld => $server) {
    $templatedTldServers[] = \sprintf("'%s' => '%s',", $tld, $server);
}

$templatedTldServersStr = '$this->servers = ['."\n".\implode("\n", $templatedTldServers)."\n];";
$template = \str_replace('$this->servers = [];', $templatedTldServersStr, $template);

$write = \file_put_contents($writePath, $template);
if (false === $write) {
    throw new RuntimeException('Can\'t write to '.$writePath);
}

$endTime = \microtime(true);
$time = \round($endTime - $startTime, 2).' seconds';

echo 'WHOIS servers updated. Now: '.$count.' servers'.\PHP_EOL;
echo 'Spent time: '.$time.\PHP_EOL;
echo 'Don\'t forget run php-cs-fixer'.\PHP_EOL;
