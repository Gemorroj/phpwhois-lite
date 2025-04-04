#!/usr/bin/env php
<?php

require_once __DIR__.'/servers-updater/_functions.php';

$template = '<?php

declare(strict_types=1);

namespace WhoRdap\Resource;

class AsnServerList
{
    public Server $serverDefault;
    /**
     * @var array<string|int, Server>
     */
    public array $servers;

    public function __construct()
    {
        $this->serverDefault = new Server(\'https://rdap.arin.net/registry\', ServerTypeEnum::RDAP);
        $this->servers = [];
    }
}';

$writePath = __DIR__.'/../src/Resource/AsnServerList.php';
$startTime = \microtime(true);

$asnServers = require __DIR__.'/servers-updater/_parser_asn_iana.php';
$count = \count($asnServers);

$templatedAsnServers = [];
foreach ($asnServers as $asn => $value) {
    $type = match ($value['type']) {
        'whois' => 'ServerTypeEnum::WHOIS',
        'rdap' => 'ServerTypeEnum::RDAP',
    };

    $templatedAsnServers[] = \sprintf("'%s' => new Server('%s', %s),", $asn, $value['server'], $type);
}

$templatedAsnServersStr = '$this->servers = ['."\n".\implode("\n", $templatedAsnServers)."\n];";
$template = \str_replace('$this->servers = [];', $templatedAsnServersStr, $template);

$write = \file_put_contents($writePath, $template);
if (false === $write) {
    throw new RuntimeException('Can\'t write to '.$writePath);
}

$endTime = \microtime(true);
$time = \round($endTime - $startTime, 2).' seconds';

echo 'WHOIS/RDAP servers updated. Now: '.$count.' servers'.\PHP_EOL;
echo 'Spent time: '.$time.\PHP_EOL;
echo 'Don\'t forget run php-cs-fixer'.\PHP_EOL;
