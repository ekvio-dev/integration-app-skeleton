<?php

declare(strict_types=1);


namespace Ekvio\Integration\Skeleton\Health;


class NullHealthChecker implements HealthChecker
{
    public function success(string $body = ''): void
    {
        //do nothing
    }

    public function failure(string $body = ''): void
    {
        //do nothing
    }
}