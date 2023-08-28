<?php

declare(strict_types=1);


namespace Ekvio\Integration\Skeleton\Health;


interface HealthChecker
{
    public function success(): void;
    public function failure(): void;
}