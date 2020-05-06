<?php
declare(strict_types=1);

namespace Ekvio\Integration\Skeleton\Invoker;

use Ekvio\Integration\Skeleton\Invokable;
use Ekvio\Integration\Skeleton\Profile\Profiler;
use Exception;
use Psr\Container\ContainerInterface;
use Psr\Log\LoggerInterface;

/**
 * Class Queue
 * @package Ekvio\Integration\Skeleton\Invoker
 */
class Composite implements Invokable
{
    private const NAME = 'Task aggregator';
    /**
     * @var ContainerInterface
     */
    private $container;
    /**
     * @var LoggerInterface
     */
    private $logger;
    /**
     * @var bool
     */
    private $profiler;

    /**
     * Queue constructor.
     * @param ContainerInterface $container
     * @param LoggerInterface $logger
     * @param Profiler $profiler
     */
    public function __construct(ContainerInterface $container, LoggerInterface $logger, Profiler $profiler)
    {
        $this->container = $container;
        $this->logger = $logger;
        $this->profiler = $profiler;
    }

    /**
     * @param array $parameters
     * @throws Exception
     */
    public function __invoke(array $parameters = []): void
    {
        $tasks = [];
        foreach ($parameters as $classname) {
            if(!class_exists($classname)) {
                throw new Exception(sprintf('Class %s not exist', $classname));
            }

            $task = $this->container->get($classname);
            if(!$task instanceof Invokable) {
                throw new Exception(sprintf('Task %s must implement Invokable interface', $classname));
            }

            $tasks[] = $task;
        }

        $parameters = [];
        foreach ($tasks as $task) {
            /** @var Invokable $task */
            $this->profiler->profile(sprintf('Starting %s task...', $task->name()));
            call_user_func_array($task, [$parameters]);
            $this->profiler->profile(sprintf('Stopping %s task...', $task->name()));
        }
    }

    /**
     * @return string
     */
    public function name(): string
    {
        return self::NAME;
    }
}