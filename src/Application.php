<?php
declare(strict_types=1);

namespace Ekvio\Integration\Skeleton;

use Psr\Log\LoggerInterface;

/**
 * Interface Application
 * @package App
 */
interface Application
{
    public const TYPE_ADAPTER = 'adapter';
    /**
     * Return application name
     * @return string
     */
    public function name(): string;
    /**
     * Return own company name
     * @return string
     */
    public function company(): string;

    /**
     * Return application type
     * @return string
     */
    public function type(): string;

    /**
     * @return null|LoggerInterface
     */
    public function logger(): ?LoggerInterface;

    /**
     * @param string $taskClassName
     * @param array $parameters
     */
    public function run(string $taskClassName, array $parameters = []): void;
}