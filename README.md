# WhoRdap

[![License](https://poser.pugx.org/gemorroj/whordap/license)](https://packagist.org/packages/gemorroj/whordap)
[![Latest Stable Version](https://poser.pugx.org/gemorroj/whordap/v/stable)](https://packagist.org/packages/gemorroj/whordap)
[![Continuous Integration](https://github.com/Gemorroj/whordap/workflows/Continuous%20Integration/badge.svg)](https://github.com/Gemorroj/whordap/actions?query=workflow%3A%22Continuous+Integration%22)

### Features:
- WHOIS/RDAP info for domains, IPv4/IPv6, CIDR, ASN
- Support national domains (президент.рф for example)
- Follow to registrar WHOIS/RDAP servers (whois.crsnic.net -> whois.nic.ru for example)
- Force custom WHOIS/RDAP server

### Requirements:
- PHP >= 8.4
- ext-curl

### Installation:
```bash
composer require gemorroj/whordap
```

### Example:

```php
<?php
use WhoRdap\NetworkClient\NetworkClient;
use WhoRdap\WhoRdap;
use WhoRdap\Response\WhoisDomainRegistrarResponse;
use WhoRdap\Response\WhoisDomainResponse;
use Psr\Log\NullLogger;
use Symfony\Component\Cache\Adapter\FilesystemAdapter;

$logger = new NullLogger();
$cache = new FilesystemAdapter('whordap', 60); // install symfony/cache

$networkClient = new NetworkClient(cache: $cache, logger: $logger);
$whois = new WhoRdap($networkClient);
// $data = $whois->processWhois('ru');
// $data = $whois->processRdap('ru');
// $data = $whois->processWhois('127.0.0.1');
// $data = $whois->processWhois('192.168.0.0/24'); // CIDR
// $data = $whois->processWhois('2001:0db8:85a3:0000:0000:8a2e:0370:7334');
// $data = $whois->processWhois('AS220');
// $data = $whois->processWhois('sirus.su', 'whois.tcinet.ru'); // custom WHOIS server
// $data = $whois->processRdap('sirus.su', 'https://www.nic.ru/rdap'); // custom RDAP server
$data = $whois->processWhois('vk.com');
$actualResponse = $data instanceof WhoisDomainResponse && $data->registrarResponse instanceof WhoisDomainRegistrarResponse ? $data->registrarResponse : $data;
// echo $actualResponse->response;

print_r($data);
/*
WhoRdap\Response\WhoisDomainResponse Object
(
    [response] => Domain Name: VK.COM
   Registry Domain ID: 3206186_DOMAIN_COM-VRSN
   Registrar WHOIS Server: whois.nic.ru
...
    [server] => whois.verisign-grs.com
    [registrarResponse] => WhoRdap\Response\WhoisDomainRegistrarResponse Object
        (
            [response] => Domain Name: VK.COM
Registry Domain ID: 3206186_DOMAIN_COM-VRSN
Registrar WHOIS Server: whois.nic.ru
...
            [server] => whois.nic.ru
        )
)
 */
```

### Notes:
- update WHOIS/RDAP servers:
  - ```shell
    php bin/rdap-asn-servers-updater.php
    php bin/rdap-ip-servers-updater.php
    php bin/rdap-tld-servers-updater.php
    php bin/whois-asn-servers-updater.php
    php bin/whois-ip-servers-updater.php
    php bin/whois-tld-servers-updater.php
    ```
- https://github.com/weppos/whois/tree/main/data
- https://habr.com/ru/articles/165869/
- https://lookup.icann.org/ru/lookup research:
    - domain: 1 request to https://data.iana.org/rdap/dns.json, 2 request to rdap server, 3 request to registrar rdap server
        - if registrar rdap server fails (vk.com for example), if shows notice and info from prev rdap server
    - ipv4: 1 request https://data.iana.org/rdap/ipv4.json, 2 request to rdap server
    - ipv6: 1 request https://data.iana.org/rdap/ipv6.json, 2 request to rdap server
    - asn: 1 request https://data.iana.org/rdap/asn.json, 2 request to rdap server
