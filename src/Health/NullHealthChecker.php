<?php

declare(strict_types=1);


namespace Ekvio\Integration\Skeleton\Health;


class NullHealthChecker implements HealthChecker
{
    public function success(): void
    {
        //do nothing
    }

    public function failure(): void
    {
        //do nothing
    }
}