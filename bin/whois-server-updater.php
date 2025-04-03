#!/usr/bin/env php
<?php

require_once __DIR__.'/whois-server-updater/_functions.php';

$template = '<?php

declare(strict_types=1);

namespace PHPWhoisLite\Resource;

final class ServerList
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

$writePath = __DIR__.'/../src/Resource/ServerList.php';
$startTime = \microtime(true);

$ianaServers = require __DIR__.'/whois-server-updater/_parser_iana.php';
$webposServers = require __DIR__.'/whois-server-updater/_parser_webpos.php';
$whoisserversServers = require __DIR__.'/whois-server-updater/_parser_whoisservers.php';

echo 'Use IANA servers as master repository.'.\PHP_EOL;
$servers = $ianaServers;
echo 'Prepare webpos servers...'.\PHP_EOL;
foreach ($webposServers as $tld => $value) {
    if (!isset($servers[$tld])) {
        echo 'Add webpos '.\strtoupper($value['type']).' server '.$value['server'].' for '.$tld.\PHP_EOL;
        $servers[$tld] = $value;
    }
}
echo 'Prepare whoisservers servers...'.\PHP_EOL;
foreach ($whoisserversServers as $tld => $value) {
    if (!isset($servers[$tld])) {
        echo 'Add whoisservers '.\strtoupper($value['type']).' server '.$value['server'].' for '.$tld.\PHP_EOL;
        $servers[$tld] = $value;
    }
}

$servers = \cleanupServers($servers);

$templatedServers = [];
foreach ($servers as $tld => $value) {
    $type = match ($value['type']) {
        'whois' => 'ServerTypeEnum::WHOIS',
        'rdap' => 'ServerTypeEnum::RDAP',
    };

    $templatedServers[] = \sprintf("'%s' => new Server('%s', %s),", $tld, $value['server'], $type);
}

$templatedServersStr = '$this->servers = ['."\n".\implode("\n", $templatedServers)."\n];";
$template = \str_replace('$this->servers = [];', $templatedServersStr, $template);

$write = \file_put_contents($writePath, $template);
if (false === $write) {
    throw new RuntimeException('Can\'t write to '.$writePath);
}

$endTime = \microtime(true);
$time = \round($endTime - $startTime, 2).' seconds';

echo 'WHOIS servers updated.'.\PHP_EOL;
echo 'Spent time: '.$time.\PHP_EOL;
echo 'Don\'t forget run php-cs-fixer'.\PHP_EOL;
