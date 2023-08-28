<?php

declare(strict_types=1);


namespace Ekvio\Integration\Skeleton\Health;


class HealthCheckFactory
{
    public static function build(array $env): HealthChecker
    {
        if (isset($env['HEALTH_CHECK_IO_UID'])) {

            $config = new HealthcheckIOConfig($env['HEALTH_CHECK_IO_UID']);
            if (isset($env['HEALTH_CHECK_IO_HOST']) && mb_strlen($env['HEALTH_CHECK_IO_HOST']) > 0) {
                $config->addHost($env['HEALTH_CHECK_IO_HOST']);
            }

            return new HealthCheckIO($config);
        }

        return new NullHealthChecker();
    }
}