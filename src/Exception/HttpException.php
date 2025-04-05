<?php

declare(strict_types=1);

namespace WhoRdap\Exception;

final class HttpException extends NetworkException
{
    public function __construct(private readonly string $response, private readonly int $httpCode, private readonly string $url, string $message)
    {
        parent::__construct($message);
    }

    public static function create(int $httpCode, string $url, string $response): self
    {
        return new self($response, $httpCode, $url, 'HTTP Error. Code: '.$httpCode);
    }

    public function getResponse(): string
    {
        return $this->response;
    }

    public function getHttpCode(): int
    {
        return $this->httpCode;
    }

    public function getUrl(): string
    {
        return $this->url;
    }
}
