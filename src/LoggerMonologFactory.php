<?php

namespace Ekvio\Integration\Skeleton;

use Exception;
use Generator;
use Monolog\Formatter\LineFormatter;
use Monolog\Formatter\NormalizerFormatter;
use Monolog\Handler\AbstractProcessingHandler;
use Monolog\Handler\StreamHandler;
use Monolog\Logger;
use Psr\Log\LoggerInterface;
use ReflectionClass;
use ReflectionException;
use ReflectionMethod;
use Throwable;

/**
 * Class LoggerFactory
 * @package App
 */
class LoggerMonologFactory
{
    private const DEFAULT_LINE_FORMAT = "[%level_name%][%datetime%]%message%\n";
    private static $formatter;

    /**
     * @param string $name
     * @return LoggerInterface
     */
    public static function createLogger(string $name): LoggerInterface
    {
        $defaultHandler = new StreamHandler(STDOUT, Logger::DEBUG);
        $defaultHandler->setFormatter(self::defaultFormatter());
        //Add default handler
        $logger = new Logger($name);
        $logger->setExceptionHandler(self::exceptionHandler());
        $logger->pushHandler($defaultHandler);
        //Add global processor
        $logger->pushProcessor(static function (array $record) {
            return array_map(static function ($element) {
                return is_string($element) ? mb_strtolower($element) : $element;
            }, $record);
        });

        return $logger;
    }

    /**
     * @param array $handlers
     * @param array $handlerConfigs
     * @return Generator
     * @throws Exception
     */
    public static function addHandlerFromConfig(array $handlers, array $handlerConfigs): Generator
    {
        foreach ($handlerConfigs as $config) {
            if(!isset($config['__name__'])) {
                throw new Exception('Key "_name_" not exist in handler config');
            }

            if(!isset($config['__class__'])) {
                throw new Exception('Key "_class_" not exist in handler config');
            }

            if(!in_array($config['__name__'], $handlers, true)) { //skip handler config if not in handlers list
                continue;
            }

            yield self::createHandler($config)->setFormatter(self::defaultFormatter());
        }
    }

    /**
     * @param array $handlerConfig
     * @return AbstractProcessingHandler
     * @throws ReflectionException
     * @throws Exception
     */
    private static function createHandler(array $handlerConfig): AbstractProcessingHandler
    {
        /** @var AbstractProcessingHandler $instance */
       $instance = self::createObjectFromDefinition($handlerConfig);

       $processorDefinitions = $handlerConfig['__processors__'] ?? [];
       foreach ($processorDefinitions as $definition) {
           $instance->pushProcessor(self::createObjectFromDefinition($definition));
       }

       return $instance;
    }

    /**
     * @param array $definition
     * @return mixed
     * @throws ReflectionException
     * @throws Exception
     */
    private static function createObjectFromDefinition(array $definition)
    {
        $class = $definition['__class__'];
        if(!class_exists($class)) {
            throw new Exception(sprintf('Class %s not exists in config', $class));
        }

        $classReflection = new ReflectionClass($class);
        if(!$classReflection->getConstructor()) {
            return new $class;
        }

        $reflection = new ReflectionMethod($class, '__construct');

        $configHandlersParams = array_keys($definition);
        $args = [];
        foreach ($reflection->getParameters() as $parameter) {
            $parameterName = $parameter->getName();

            if($parameter->isOptional() === false) { //required parameters
                if(!in_array($parameterName, $configHandlersParams, true)) {
                    throw new Exception(sprintf('For class "%s" parameter "%s" not declared', $class, $parameterName));
                }

                $args[] = $definition[$parameterName];
                continue;
            }

            if($parameter->isOptional() === true) {
                $args[] = $definition[$parameterName] ?? $parameter->getDefaultValue();
                continue;
            }
        }

        return new $class(...$args);
    }

    /**
     * @return NormalizerFormatter
     */
    private static function defaultFormatter(): NormalizerFormatter
    {
        if(self::$formatter === null) {
            self::$formatter = new LineFormatter(self::DEFAULT_LINE_FORMAT);
        }

        return self::$formatter;
    }

    /**
     * @return callable
     */
    private static function exceptionHandler(): callable
    {
        return static function(Throwable $exception){
            $message = sprintf("%s:%s:%s\n", $exception->getMessage(), $exception->getFile(), $exception->getLine());
            fwrite(STDOUT, $message);
        };
    }
}