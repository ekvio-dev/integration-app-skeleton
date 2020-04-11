<?php
namespace Skeleton;

use ErrorException;
use Throwable;

/**
 * Class ErrorHandler
 * Simplified error handler from Yii2 framework
 * @see https://github.com/yiisoft/yii2/blob/master/framework/base/ErrorHandler.php
 * @package App
 */
class ErrorHandler
{
    private const APP_SUCCESSFUL_COMPLETE_MESSAGE = 'app successfully completed';
    private const APP_EMPTY_STACKTRACE = '-';
    /**
     * @var Application
     */
    private $app;

    /**
     * ErrorHandler constructor.
     * @param Application $app
     */
    public function __construct(Application $app)
    {
        $this->app = $app;
    }

    /**
     * @var int the size of the reserved memory. A portion of memory is pre-allocated so that
     * when an out-of-memory issue occurs, the error handler is able to handle the error with
     * the help of this reserved memory. If you set this value to be 0, no memory will be reserved.
     * Defaults to 256KB.
     */
    public $memoryReserveSize = 262144;
    /**
     * @var Throwable|null the exception that is being handled currently.
     */
    public $exception;

    /**
     * @var bool
     */
    private $exceptionHandled = false;

    /**
     * @var string error string format: [company][application][name][message][stacktrace]
     * [company] - company name context get from ENV
     * [application] - type application: application|adapter
     * [name] - application name, get from ENV
     * [message] - log message. If error message can consist from message:file:line
     * [stacktrace] - string stacktrace
     *
     * type log and timestamp append by logger
     */
    private $messageFormat = '[%s][%s][%s][%s][%s]';
    /**
     * @var string Used to reserve memory for fatal error handler.
     */
    private $_memoryReserve;
    /**
     * @var bool whether this instance has been registered using `register()`
     */
    private $_registered = false;

    /**
     * Register this error handler.
     */
    public function register(): void
    {
        if (!$this->_registered) {
            ini_set('display_errors', false);
            set_exception_handler([$this, 'handleException']);
            set_error_handler([$this, 'handleError']);

            if ($this->memoryReserveSize > 0) {
                $this->_memoryReserve = str_repeat('x', $this->memoryReserveSize);
            }
            register_shutdown_function([$this, 'handleFatalError']);
            $this->_registered = true;
        }
    }

    /**
     * Unregisters this error handler by restoring the PHP error and exception handlers.
     */
    public function unregister(): void
    {
        if ($this->_registered) {
            restore_error_handler();
            restore_exception_handler();
            $this->_registered = false;
        }
    }

    /**
     * Handles uncaught PHP exceptions.
     *
     * This method is implemented as a PHP exception handler.
     *
     * @param Throwable $exception the exception that is not caught
     */
    public function handleException(Throwable $exception): void
    {
        $this->exception = $exception;

        // disable error capturing to avoid recursive errors while handling exceptions
        $this->unregister();

        try {
            $this->logException($exception);
        } catch (Throwable $e) {
            // additional check for \Throwable introduced in PHP 7
            $this->handleFallbackExceptionMessage($e);
        }

        $this->exceptionHandled = true;
        $this->exception = null;
    }

    /**
     * Handles exception thrown during exception processing.
     * @param Throwable $exception Exception that was thrown during main exception processing.
     */
    protected function handleFallbackExceptionMessage($exception): void
    {
        error_log('Fatal exception over exception: '. $exception->getMessage());
        exit(1);
    }

    /**
     * Handles PHP execution errors such as warnings and notices.
     *
     * This method is used as a PHP error handler. It will simply raise an [[ErrorException]].
     *
     * @param int $code the level of the error raised.
     * @param string $message the error message.
     * @param string $file the filename that the error was raised in.
     * @param int $line the line number the error was raised at.
     * @return bool whether the normal error handler continues.
     *
     * @throws ErrorException
     */
    public function handleError($code, $message, $file, $line): bool
    {
        if (error_reporting() & $code) {
            throw new ErrorException($message, $code, $code, $file, $line);
        }
        return false;
    }

    /**
     * Handles fatal PHP errors.
     */
    public function handleFatalError()
    {
        unset($this->_memoryReserve);
        $error = error_get_last();

        if(is_array($error) && $this->isFatalError($error)) {
            $exception = new ErrorException($error['message'], $error['type'], $error['type'], $error['file'], $error['line']);
            $this->logException($exception);
            exit(1);
        }

        //after handled exception case
        if($this->exceptionHandled) {
            exit(1);
        }

        $this->app->logger()->info($this->format(
            $this->app->company(),
            $this->app->type(),
            $this->app->name(),
            self::APP_SUCCESSFUL_COMPLETE_MESSAGE,
            self::APP_EMPTY_STACKTRACE
        ));
        exit(0);
    }

    /**
     * @param array $error
     * @return bool
     */
    private function isFatalError(array $error)
    {
        return isset($error['type']) && in_array($error['type'], [E_ERROR, E_PARSE, E_CORE_ERROR, E_CORE_WARNING, E_COMPILE_ERROR, E_COMPILE_WARNING]);
    }

    /**
     * Logs the given exception.
     * @param Throwable $exception
     */
    public function logException(Throwable $exception)
    {
        $exceptionMessage = sprintf('%s:%s:%s', $exception->getMessage(), $exception->getFile(), $exception->getLine());
        $stacktrace = $exception->getTraceAsString() ?? '';

        $this->app->logger()->error($this->format(
            $this->app->company(),
            $this->app->type(),
            $this->app->name(),
            $exceptionMessage,
            $stacktrace
        ));
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

    /**
     * @param string $company
     * @param string $type
     * @param string $name
     * @param string $exceptionMessage
     * @param string $exceptionStacktrace
     * @return string
     */
    private function format(string $company, string $type, string $name, string $exceptionMessage, string $exceptionStacktrace): string
    {
        return  sprintf($this->messageFormat,
            $this->trimSymbols($company),
            $this->trimSymbols($type),
            $this->trimSymbols($name),
            $this->trimSymbols($exceptionMessage),
            $this->trimSymbols($exceptionStacktrace));
    }
}