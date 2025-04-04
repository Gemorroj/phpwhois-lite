#!/usr/bin/env php
<?php

require_once __DIR__.'/servers-updater/_functions.php';

$template = '<?php

declare(strict_types=1);

namespace WhoRdap\Resource;

class TldServerList
{
    public Server $serverDefault;
    /**
     * @var array<string, Server>
     */
    public array $servers;

    public function __construct()
    {
        $this->serverDefault = new Server(\'https://rdap.iana.org\', ServerTypeEnum::RDAP);
        $this->servers = [];
    }
}';

$writePath = __DIR__.'/../src/Resource/TldServerList.php';
$startTime = \microtime(true);

$tldIanaServers = require __DIR__.'/servers-updater/_parser_tld_iana.php';
$tldWebposServers = require __DIR__.'/servers-updater/_parser_tld_webpos.php';
$tldWhoisserversServers = require __DIR__.'/servers-updater/_parser_tld_whoisservers.php';

echo 'Use IANA servers as master repository.'.\PHP_EOL;
$tldServers = $tldIanaServers;
echo 'Prepare webpos servers...'.\PHP_EOL;
foreach ($tldWebposServers as $tld => $value) {
    if (!isset($tldServers[$tld])) {
        echo 'Add webpos '.\strtoupper($value['type']).' server '.$value['server'].' for '.$tld.\PHP_EOL;
        $tldServers[$tld] = $value;
    }
}
echo 'Prepare whoisservers servers...'.\PHP_EOL;
foreach ($tldWhoisserversServers as $tld => $value) {
    if (!isset($tldServers[$tld])) {
        echo 'Add whoisservers '.\strtoupper($value['type']).' server '.$value['server'].' for '.$tld.\PHP_EOL;
        $tldServers[$tld] = $value;
    }
}

$tldServers = \cleanupTldServers($tldServers);
$count = \count($tldServers);

$templatedTldServers = [];
foreach ($tldServers as $tld => $value) {
    $type = match ($value['type']) {
        'whois' => 'ServerTypeEnum::WHOIS',
        'rdap' => 'ServerTypeEnum::RDAP',
    };

    $templatedTldServers[] = \sprintf("'%s' => new Server('%s', %s),", $tld, $value['server'], $type);
}

$templatedTldServersStr = '$this->servers = ['."\n".\implode("\n", $templatedTldServers)."\n];";
$template = \str_replace('$this->servers = [];', $templatedTldServersStr, $template);

$write = \file_put_contents($writePath, $template);
if (false === $write) {
    throw new RuntimeException('Can\'t write to '.$writePath);
}

$endTime = \microtime(true);
$time = \round($endTime - $startTime, 2).' seconds';

echo 'WHOIS/RDAP servers updated. Now: '.$count.' servers'.\PHP_EOL;
echo 'Spent time: '.$time.\PHP_EOL;
echo 'Don\'t forget run php-cs-fixer'.\PHP_EOL;
