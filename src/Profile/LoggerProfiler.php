<?php
declare(strict_types=1);

namespace Ekvio\Integration\Skeleton\Profile;

use Ekvio\Integration\Contracts\Profiler;
use Psr\Log\LoggerInterface;

/**
 * Class LoggerProfiler
 * @package Ekvio\Integration\Skeleton\Profile
 */
class LoggerProfiler implements Profiler
{
    /**
     * @var LoggerInterface
     */
    private $logger;
    /**
     * @var bool
     */
    private $debug;

    /**
     * EkvioProfiler constructor.
     * @param LoggerInterface $logger
     * @param bool $debug
     */
    public function __construct(LoggerInterface $logger, bool $debug = false)
    {
        $this->logger = $logger;
        $this->debug = $debug;
    }

    /**
     * @param string $message
     */
    public function profile(string $message): void
    {
        if($this->debug) {
            $this->logger->debug($message);
        }
    }
}