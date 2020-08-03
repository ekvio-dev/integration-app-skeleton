<?php

namespace Ekvio\Integration\Skeleton;

use Ekvio\Integration\Contracts\Profiler;
use Ekvio\Integration\Skeleton\Log\LoggerMonologFactory;
use Ekvio\Integration\Skeleton\Profile\LoggerProfiler;
use Psr\Container\ContainerInterface;
use Psr\Log\LoggerInterface;

/**
 * Class EnvironmentConfiguration
 * @package App
 */
class EnvironmentConfiguration
{
    /**
     * EnvironmentConfiguration constructor.
     */
    private function __construct()
    {
    }

    /**
     * @return array
     */
    public static function create(): array
    {
        $env = getenv();
        $debug = (bool) $env['APPLICATION_DEBUG'] ?? null;

        return  [
            'name' => $env['INTEGRATION_APP_NAME'] ?? null,
            'company' => $env['INTEGRATION_COMPANY_NAME'] ?? null,
            'debug' => $debug,
            'services' => [
                LoggerInterface::class => function () use ($env) {
                    return LoggerMonologFactory::createLogger('logger', $env);
                },
                Profiler::class => function(ContainerInterface $c) use ($debug) {
                    return new LoggerProfiler($c->get(LoggerInterface::class), $debug);
                },
            ]
        ];
    }
}