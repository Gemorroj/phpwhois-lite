#!/usr/bin/env php
<?php

$template = '<?php

declare(strict_types=1);

namespace PHPWhoisLite\Resource;

final readonly class WhoisServerList
{
    public string $whoisServerDefault;
    /**
     * @var WhoisServer[] $whoisServers
     */
    public array $whoisServers;
    public function __construct()
    {
        $this->whoisServerDefault = \'whois.iana.org:43\';
        $this->whoisServers = [];
    }
}';

$host = 'http://whoisservers.net';
$port = 80;
$writePath = __DIR__.'/../src/Resource/WhoisServerList.php';

$startTime = \microtime(true);

echo 'Connect to '.$host.'...'.\PHP_EOL;
$fp = \connect($host);

echo 'Load main page...'.\PHP_EOL;
$mainPage = \request($fp, $host.'/');

$matches = [];
\preg_match_all('/<h4><a href="(.+)">(.+)<\/a><\/h4>/', $mainPage, $matches, \PREG_SET_ORDER);

echo 'Start scan domains...'.\PHP_EOL;
$servers = [];
foreach ($matches as $match) {
    $tld = $match[2];
    $urlPage = $match[1];

    echo 'Load "'.$tld.'" domain page...'.\PHP_EOL;
    $domainPage = \request($fp, $host.$urlPage);

    $matchesPage = [];
    \preg_match('/<tr><td>WHOIS Server: <\/td><td>(.+)<\/td><\/tr>/', $domainPage, $matchesPage);
    $server = $matchesPage[1];

    $server = \htmlspecialchars_decode($server);
    if (!\str_starts_with($server, 'http://') && !\str_starts_with($server, 'https://')) {
        // is whois server
        if (!\str_contains($server, ':')) {
            // default whois port
            $server .= ':43';
        }
    }

    $servers[] = \sprintf("new WhoisServer('%s', '%s'),", $tld, $server);
}
\disconnect($fp);
echo 'End scan domains.'.\PHP_EOL;
echo \PHP_EOL;

$serversStr = '$this->whoisServers = ['."\n".\implode("\n", $servers)."\n];";
$template = \str_replace('$this->whoisServers = [];', $serversStr, $template);

$write = \file_put_contents($writePath, $template);
if (false === $write) {
    throw new RuntimeException('Can\'t write to '.$writePath);
}

$endTime = \microtime(true);
$time = \round($endTime - $startTime, 2).' seconds';

echo 'WHOIS servers updated.'.\PHP_EOL;
echo 'Spent time: '.$time.\PHP_EOL;
echo 'Don\'t forget run php-cs-fixer'.\PHP_EOL;

function connect(string $url): CurlHandle
{
    $fp = \curl_init($url);
    \curl_setopt($fp, \CURLOPT_ENCODING, '');
    \curl_setopt($fp, \CURLOPT_RETURNTRANSFER, true);
    \curl_setopt($fp, \CURLOPT_HTTPHEADER, [
        'Connection: Keep-Alive',
    ]);

    return $fp;
}

function request(CurlHandle $fp, string $url): string
{
    \curl_setopt($fp, \CURLOPT_URL, $url);
    $result = \curl_exec($fp);
    if (false === $result) {
        throw new RuntimeException(\curl_error($fp));
    }

    return $result;
}

function disconnect(CurlHandle $fp): void
{
    \curl_close($fp);
}
