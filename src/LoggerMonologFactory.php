<?php

namespace App;

use Exception;
use Generator;
use Monolog\Formatter\LineFormatter;
use Monolog\Formatter\NormalizerFormatter;
use Monolog\Handler\AbstractProcessingHandler;
use Monolog\Handler\StreamHandler;
use Monolog\Logger;
use Psr\Log\LoggerInterface;
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
     * @var array
     */
    private static $handlers = [
        'telegram_proxy' => ProxyTelegramHandler::class
    ];

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
     * @param array $config
     * @return Generator
     * @throws Exception
     */
    public static function addHandlerFromConfig(array $config): Generator
    {
        if(isset($config['logger']['handlers'])) {
            $handlers = explode(',', $config['logger']['handlers']);
            foreach ($handlers as $handler) {

                if(!isset(self::$handlers[$handler])) {
                    throw new Exception(sprintf('For logger handler [%s] class not exist', $handler));
                }

                if(!isset($config['logger']['config'][$handler])) {
                    throw new Exception(sprintf('For logger handler [%s] configuration not exists', $handler));
                }

                yield self::createHandler($handler, $config['logger']['config'][$handler]);
            }
        }
    }

    /**
     * @param string $type
     * @param array $config
     * @return AbstractProcessingHandler
     * @throws Exception
     */
    private static function createHandler(string $type, array $config): AbstractProcessingHandler
    {
        if($type === 'telegram_proxy') {

            $proxy = $config['proxy'] ?? null;
            $bot = $config['bot'] ?? null;
            $chat = $config['chat'] ?? null;
            $options = $config['options'] ?? [];
            $level = isset($config['level']) ? (int) $config['level'] : Logger::DEBUG;

            $handler = new ProxyTelegramHandler($proxy, $bot, $chat, $options, $level);
            $handler->setFormatter(self::defaultFormatter());
            $handler->pushProcessor(self::withoutStacktrace());

            return $handler;
        }

        throw new Exception(sprintf('Create unknown handler [%s]', $type));
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
    private static function withoutStacktrace(): callable
    {
        return static function(array $record) {

            if(!array_key_exists('message', $record)) {
                return $record;
            }

            /** @noinspection RegExpRedundantEscape */
            $parts = preg_split('/(\[[^]]+\])/', $record['message'], -1, PREG_SPLIT_DELIM_CAPTURE);
            if(!is_array($parts)) {
                return $record;
            }

            // removes all NULL, FALSE and Empty Strings but leaves 0 (zero) values
            $parts = array_values(array_filter($parts, 'strlen'));
            if(count($parts) === 5) {
                unset($parts[4]);
            }
            $record['message'] = implode($parts);
            return $record;
        };
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