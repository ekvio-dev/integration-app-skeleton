<?php

namespace Ekvio\Integration\Skeleton\Log;

use Exception;
use Monolog\Formatter\LineFormatter;
use Monolog\Formatter\NormalizerFormatter;
use Monolog\Handler\StreamHandler;
use Monolog\Logger;
use Psr\Log\LoggerInterface;
use Throwable;

/**
 * Class Ekvio specific monolog logger factory
 * @package App
 */
class LoggerMonologFactory
{
    private const DEFAULT_LINE_FORMAT = "[%level_name%][%datetime%]%message%\n";
    /** @var NormalizerFormatter */
    private static $formatter;

    /**
     * @param string $name
     * @param array $env
     * @return LoggerInterface
     * @throws Exception
     */
    public static function createLogger(string $name, array $env = []): LoggerInterface
    {
        $defaultHandler = new StreamHandler(STDOUT, Logger::DEBUG);
        $defaultHandler->setFormatter(self::defaultFormatter());
        //Add default handler
        $logger = new Logger($name);
        $logger->setExceptionHandler(self::exceptionHandler());
        $logger->pushHandler($defaultHandler);
        //Add global processor
        $logger->pushProcessor(static function (array $record) {
            $record['level_name'] = mb_strtolower($record['level_name']);
            return $record;
        });

        $logger = self::registerCustomHandlerFromEnv($logger, $env);

        return $logger;
    }

    /**
     * @param LoggerInterface $logger
     * @param array $env
     * @return LoggerInterface
     * @throws Exception
     */
    private static function registerCustomHandlerFromEnv(LoggerInterface $logger, array $env): LoggerInterface
    {
        /** @var Logger Logger */
        if($env === []) {
            return $logger;
        }

        $handlerDefinitions = $env['LOGGER_HANDLERS'] ?? null;
        if(!$handlerDefinitions) {
            return $logger;
        }

        $handlerDefinitions = explode(',', $handlerDefinitions);
        foreach ($handlerDefinitions as $definition) {
            if($definition === 'telegram_proxy') {

                $host = self::getEnv($env, 'LOGGER_TELEGRAM_PROXY_ADDRESS');
                $botId = self::getEnv($env, 'LOGGER_TELEGRAM_PROXY_BOT_ID');
                $chatId = self::getEnv($env, 'LOGGER_TELEGRAM_PROXY_CHAT_ID');
                $options =  [
                    'proxy' => getenv('LOGGER_TELEGRAM_PROXY_CURL_PROXY') ?: null,
                    'timeout' => (int) getenv('LOGGER_TELEGRAM_PROXY_CURL_CONNECT_TIMEOUT') ?: null,
                    'verify' => getenv('LOGGER_TELEGRAM_PROXY_CURL_SSL_VERIFY') !== false ? getenv('CURL_SSL_VERIFY') : null,
                ];
                $level = (int) getenv('LOGGER_TELEGRAM_PROXY_LEVEL') ?? Logger::INFO;

                $handler = new ProxyTelegramHandler($host, $botId, $chatId, $options, $level);
                $handler->setFormatter(self::defaultFormatter())->pushProcessor(new StacktracelessProcessor());
                $logger->pushHandler($handler);
            }
        }

        return $logger;
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

    /**
     * @param array $env
     * @param string $name
     * @return string
     * @throws Exception
     */
    private static function getEnv(array $env, string $name): string
    {
        $value = $env[$name] ?? null;
        if($value === null) {
            throw new Exception(sprintf('Environment %s not exist.', $name));
        }

        return $env[$name];
    }
}