<?php
declare(strict_types=1);

namespace Ekvio\Integration\Skeleton\Profile;

/**
 * Interface Profiler
 * @package Ekvio\Integration\Skeleton
 */
interface Profiler
{
    /**
     * @param string $message
     */
    public function profile(string $message): void;
}