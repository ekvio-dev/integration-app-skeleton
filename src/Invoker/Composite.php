<?php
declare(strict_types=1);

namespace Ekvio\Integration\Skeleton\Invoker;

use Ekvio\Integration\Contracts\Invoker;
use Ekvio\Integration\Contracts\Profiler;
use Exception;
use Psr\Container\ContainerInterface;
use Psr\Log\LoggerInterface;

/**
 * Class Composite
 * @package Ekvio\Integration\Skeleton\Invoker
 */
class Composite implements Invoker
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
     * @param array $arguments
     * @throws Exception
     */
    public function __invoke(array $arguments = [])
    {
        $tasks = [];
        foreach ($arguments as $key => $classname) {

            $taskParameters = [];
            if(is_array($classname)) {
                $taskParameters = $classname;
                $classname = $key;
            }

            if(!class_exists($classname)) {
                throw new Exception(sprintf('Class %s not exist', $classname));
            }

            $task = $this->container->get($classname);
            if(!$task instanceof Invoker) {
                throw new Exception(sprintf('Task %s must implement Invokable interface', $classname));
            }

            $tasks[] = ['name' => $task, 'arguments' => $taskParameters];
        }

        $prev = null;
        foreach ($tasks as $invoker) {
            /** @var Invoker $task */
            $task = $invoker['name'];
            $this->profiler->profile(sprintf('Starting %s task...', $task->name()));
            $prev = call_user_func_array($task, [[
                'prev' => $prev,
                'parameters' => $invoker['arguments']
            ]]);
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