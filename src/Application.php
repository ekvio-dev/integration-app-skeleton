<?php

namespace Skeleton;

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
     * Main application handler
     */
    public function run(): void;

    /**
     * @return LoggerInterface
     */
    public function logger(): LoggerInterface;
}