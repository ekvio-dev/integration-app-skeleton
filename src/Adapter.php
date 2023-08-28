<?php
declare(strict_types=1);

namespace Ekvio\Integration\Skeleton;

use DI\ContainerBuilder;
use Ekvio\Integration\Contracts\Invoker;
use Ekvio\Integration\Contracts\Profiler;
use Ekvio\Integration\Skeleton\Health\HealthChecker;
use Exception;
use Psr\Container\ContainerInterface;
use Psr\Log\LoggerInterface;

/**
 * Class Application
 * @package Ekvio\Integration\Skeleton
 */
class Adapter implements Application
{
    private const UNNAMED = 'unnamed';
    /**
     * @var string error string format: [company][application][name][message][stacktrace]
     * [company] - company name context get from config
     * [application] - type application: application|adapter
     * [name] - application name, get from config
     * [message] - log message. If error message can consist from message:file:line
     * [stacktrace] - string stacktrace
     *
     * type log and timestamp append by logger
     */
    private const MESSAGE_FORMAT = '[%s][%s][%s][%s][%s]';
    private const APP_SUCCESSFUL_COMPLETE_MESSAGE = 'app successfully completed';
    private const APP_EMPTY_STACKTRACE = '-';
    /**
     * @var string
     */
    private $name = self::UNNAMED;
    /**
     * @var string
     */
    private $company = self::UNNAMED;

    /**
     * @var ContainerInterface
     */
    private $container;

    /**
     * @var null|LoggerInterface
     */
    private $logger;

    private $profiler;
    private $healthChecker;

    /**
     * Adapter constructor.
     * @param array $config
     * @throws Exception
     */
    public function __construct(array $config)
    {
        $this->registerErrorHandler();
        $this->registerComponents($config);
    }

    /**
     * Register application error handler
     */
    private function registerErrorHandler(): void
    {
        $errorHandler = new ErrorHandler($this);
        $errorHandler->register();
    }

    /**
     * Parsing configuration file
     * @param array $config
     * @throws Exception
     */
    private function registerComponents(array $config): void
    {
        $name = $config['name'] ?? null;
        if($name === null) {
            throw new Exception('Application must have name');
        }
        $this->name = $name;

        $company = $config['company'] ?? null;
        if($company === null) {
            throw new Exception('Application must have company');
        }
        $this->company = $company;

        $container = new ContainerBuilder();

        if(array_key_exists('services', $config)) {
            $container->addDefinitions($config['services']);
        }
        $this->container = $container->build();
        $this->logger = $this->container->get(LoggerInterface::class);
        $this->profiler = $this->container->get(Profiler::class);
        $this->healthChecker = $this->container->get(HealthChecker::class);
    }

    /**
     * @return string
     */
    public function name(): string
    {
        return $this->name;
    }

    /**
     * @return string
     */
    public function company(): string
    {
        return $this->company;
    }

    /**
     * @return string
     */
    public function type(): string
    {
        return Application::TYPE_ADAPTER;
    }

    /**
     * @return null|LoggerInterface
     */
    public function logger(): ?LoggerInterface
    {
        return $this->logger;
    }

    public function healthChecker(): HealthChecker
    {
        return $this->healthChecker;
    }

    /**
     * @param string $taskClassName
     * @param array $parameters
     * @throws Exception
     */
    public function run(string $taskClassName, array $parameters = []): void
    {
        if(!class_exists($taskClassName)) {
            throw new Exception(sprintf('Class %s not exist', $taskClassName));
        }
        $task = $this->container->get($taskClassName);

        if(!$task instanceof Invoker) {
            throw new Exception(sprintf('Task %s must implement Invokable interface', $taskClassName));
        }

        $this->profiler->profile(sprintf('Starting %s task...', $task->name()));
        call_user_func_array($task, [$parameters]);
        $this->profiler->profile(sprintf('Stopping %s task...', $task->name()));

        $this->logger()->info($this->format(
            self::APP_SUCCESSFUL_COMPLETE_MESSAGE,
            self::APP_EMPTY_STACKTRACE
        ));

        $this->healthChecker()->success();
        exit(0);
    }

    /**
     * Format adapter message
     *
     * @param string $message
     * @param string $exceptionStacktrace
     * @return string
     */
    public function format(string $message, ?string $exceptionStacktrace = null): string
    {
        $stackTrace = $exceptionStacktrace ?: self::APP_EMPTY_STACKTRACE;
        return  sprintf(self::MESSAGE_FORMAT,
            $this->trimSymbols($this->company()),
            $this->trimSymbols($this->type()),
            $this->trimSymbols($this->name()),
            $this->trimSymbols($message),
            $this->trimSymbols($stackTrace));
    }

    /**
     * @param string $value
     * @return string
     */
    private function trimSymbols(string $value): string
    {
        return strtr($value,  [
            '[' => '~',
            ']' => '~'
        ]);
    }
}