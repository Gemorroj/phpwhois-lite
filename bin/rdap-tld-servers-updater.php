#!/usr/bin/env php
<?php

require_once __DIR__.'/servers-updater/_functions.php';

$template = '<?php

declare(strict_types=1);

namespace WhoRdap\Resource;

use WhoRdap\TldServerListInterface;

class RdapTldServerList implements TldServerListInterface
{
    public string $serverDefault;
    /**
     * @var array<string, string>
     */
    public array $servers;

    public function __construct()
    {
        $this->serverDefault = \'https://rdap.iana.org\';
        $this->servers = [];
    }
}';

$writePath = __DIR__.'/../src/Resource/RdapTldServerList.php';
$startTime = \microtime(true);

$tldServers = require __DIR__.'/servers-updater/_parser_rdap_tld_iana.php';

$countTld = \count($tldServers);

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

echo 'RDAP servers updated. Now: '.$countTld.' servers'.\PHP_EOL;
echo 'Spent time: '.$time.\PHP_EOL;
echo 'Don\'t forget run php-cs-fixer'.\PHP_EOL;
