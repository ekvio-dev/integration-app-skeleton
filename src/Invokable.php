<?php
declare(strict_types=1);

namespace Ekvio\Integration\Skeleton;

/**
 * Interface Invokable
 * @package Ekvio\Integration\Skeleton
 */
interface Invokable
{
    /**
     * Invoke task
     * @param array $parameters
     */
    public function __invoke(array $parameters = []): void;

    /**
     * Get invoker name
     * @return string
     */
    public function name(): string;
}