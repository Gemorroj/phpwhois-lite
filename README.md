# PHPWhois Lite

[![License](https://poser.pugx.org/gemorroj/phpwhois-lite/license)](https://packagist.org/packages/gemorroj/phpwhois-lite)
[![Latest Stable Version](https://poser.pugx.org/gemorroj/phpwhois-lite/v/stable)](https://packagist.org/packages/gemorroj/phpwhois-lite)
[![Continuous Integration](https://github.com/Gemorroj/phpwhois-lite/workflows/Continuous%20Integration/badge.svg)](https://github.com/Gemorroj/phpwhois-lite/actions?query=workflow%3A%22Continuous+Integration%22)


### Requirements:
- PHP >= 8.2

### Installation:
```bash
composer require gemorroj/phpwhois-lite
```

### Example:
```php
<?php
use PHPWhoisLite\Client\WhoisClient;
use PHPWhoisLite\Whois;
use Symfony\Component\Cache\Adapter\FilesystemAdapter;
use Psr\Log\NullLogger;

$logger = new NullLogger();
$cache = new FilesystemAdapter('phpwhois-lite', 60); // install symfony/cache

$whoisClient = new WhoisClient(cache: $cache, logger: $logger);
$whois = new Whois($whoisClient);
$data = $whois->process('127.0.0.1');

print_r($data);
```
