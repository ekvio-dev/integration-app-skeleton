<?php

namespace Ekvio\Integration\Skeleton;

use Exception;
use Monolog\Logger;
use Psr\Log\LoggerInterface;

/**
 * Class AbstractAdapter
 * @package App
 */
abstract class AbstractAdapter implements Application
{
    /**
     * @var string
     */
    protected $name;
    /**
     * @var string
     */
    protected $company;
    /**
     * @var ErrorHandler
     */
    protected $errorHandler;
    /**
     * @var Logger
     */
    protected $logger;

    /**
     * @var bool
     */
    protected $debug = false;

    /**
     * AbstractAdapter constructor.
     * @param string $name
     * @param string $company
     * @param ApplicationConfiguration $configuration
     * @throws Exception
     */
    public function __construct(string $name, string $company, ApplicationConfiguration $configuration)
    {
        $this->name = $name;
        $this->company = $company;
        $this->logger = LoggerMonologFactory::createLogger($name);
        $this->errorHandler = new ErrorHandler($this);
        $this->errorHandler->register();

        $this->parseConfig($configuration->get());
    }

    /**
     * @param array $config
     * @throws Exception
     */
    private function parseConfig(array $config): void
    {
        if(array_key_exists('debug', $config)) {
            $this->debug = (bool) $config['debug'];
        }


        $loggerHandlers = $config['logger']['handlers'] ?? null;

        if(!$loggerHandlers || !is_string($loggerHandlers)) {
            return;
        }

        $loggerHandlers =  $handlers = explode(',', $loggerHandlers);
        if(count($loggerHandlers) === 0) {
            return;
        }

        $loggerHandlersConfig = $config['logger']['config'] ?? [];

        if(!$loggerHandlers || !is_array($loggerHandlersConfig)) {
            return;
        }

        foreach (LoggerMonologFactory::addHandlerFromConfig($loggerHandlers, $loggerHandlersConfig) as $handler) {
            $this->logger->pushHandler($handler);
        }
    }

    /**
     * @return LoggerInterface
     */
    public function logger(): LoggerInterface
    {
        return  $this->logger;
    }

    /**
     * @inheritDoc
     */
    public function name(): string
    {
        return $this->name;
    }

    /**
     * @inheritDoc
     */
    public function company(): string
    {
        return  $this->company;
    }

    /**
     * @inheritDoc
     */
    public function type(): string
    {
        return Application::TYPE_ADAPTER;
    }

    /**
     * @inheritDoc
     */
    abstract public function run(): void;
}