# PHPWhois Lite

[![License](https://poser.pugx.org/gemorroj/phpwhois-lite/license)](https://packagist.org/packages/gemorroj/phpwhois-lite)
[![Latest Stable Version](https://poser.pugx.org/gemorroj/phpwhois-lite/v/stable)](https://packagist.org/packages/gemorroj/phpwhois-lite)
[![Continuous Integration](https://github.com/Gemorroj/phpwhois-lite/workflows/Continuous%20Integration/badge.svg)](https://github.com/Gemorroj/phpwhois-lite/actions?query=workflow%3A%22Continuous+Integration%22)

### TODO:
- GTLD servers - https://stackoverflow.com/questions/18270575/the-list-of-all-com-and-net-whois-servers
- convert NON UTF-8 responses
- research whois.lacnic.net server (see https://github.com/Gemorroj/phpwhois-lite/actions/runs/14203970378/job/39797218684#step:6:3292)



### Features:
- WHOIS info for domains, IPv4/IPv6, AS
- Support national domains (президент.рф for example)
- Follow to registrar WHOIS servers (whois.crsnic.net -> whois.nic.ru for example)

### Requirements:
- PHP >= 8.2
- ext-curl

### Installation:
```bash
composer require gemorroj/phpwhois-lite
```

### Example:
```php
<?php
use PHPWhoisLite\Whois;
use PHPWhoisLite\WhoisClient;
use Psr\Log\NullLogger;
use Symfony\Component\Cache\Adapter\FilesystemAdapter;

$logger = new NullLogger();
$cache = new FilesystemAdapter('phpwhois-lite', 60); // install symfony/cache

$whoisClient = new WhoisClient(cache: $cache, logger: $logger);
$whois = new Whois($whoisClient);
// $data = $whois->process('127.0.0.1'); // throws IpReservedRangeException
$data = $whois->process('vk.com');

print_r($data);
/*
PHPWhoisLite\Data Object
(
    [raw] => Domain Name: VK.COM
Registry Domain ID: 3206186_DOMAIN_COM-VRSN
...

    [server] => whois.nic.ru:43
    [type] => PHPWhoisLite\QueryTypeEnum Enum:string
        (
            [name] => DOMAIN
            [value] => domain
        )

)
 */
```
