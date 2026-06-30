<?php

declare(strict_types=1);


namespace Ekvio\Integration\Skeleton\Health;


interface HealthChecker
{
    public function success(string $body = ''): void;
    public function failure(string $body = ''): void;
}