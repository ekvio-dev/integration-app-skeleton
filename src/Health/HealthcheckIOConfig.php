<?php

declare(strict_types=1);


namespace Ekvio\Integration\Skeleton\Health;


use RuntimeException;

class HealthcheckIOConfig
{
    private $uid;
    private $host = 'https://check.e-queo.xyz';

    public function __construct(string $uid)
    {
        if (empty($uid)) {
            throw new RuntimeException(sprintf('Invalid HealthcheckIO UID: %s', $uid));
        }

        $this->uid = $uid;
    }

    public function addHost(string $host): void
    {
        $this->host = rtrim($host, '/');
    }

    public function uid(): string
    {
        return $this->uid;
    }

    public function host(): string
    {
        return $this->host;
    }
}