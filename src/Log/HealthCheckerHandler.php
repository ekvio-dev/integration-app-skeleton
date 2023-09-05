<?php
declare(strict_types=1);

namespace Ekvio\Integration\Skeleton\Log;

use Monolog\Handler\AbstractProcessingHandler;
use Monolog\Logger;

/**
 * Class HealthCheckerHandler
 * @package App
 */
class HealthCheckerHandler extends AbstractProcessingHandler
{
    private const CURL_DEFAULT_TIMEOUT = 10;
    private const CURL_SSL_VERIFY = true;
    private const DEFAULT_LEVEL = Logger::INFO;
    /**
     * @var string
     */
    private $proxy;
    /**
     * @var string
     */
    private $botToken;
    /**
     * @var string
     */
    private $chatId;
    /**
     * @var array
     */
    private $options;

    /**
     * HealthCheckerHandler constructor.
     * @param string $proxy
     * @param string $botToken
     * @param string $chatId
     * @param array $options
     * @param int $level
     * @param bool $bubble
     */
    public function __construct(string $baseDomain, string $adapterID, array $options = [], $level = self::DEFAULT_LEVEL, bool $bubble = true)
    {
        $this->baseDomain = $baseDomain;
        $this->adapterID = $adapterID;

        parent::__construct($level, $bubble);
    }

    /**
     * @return string
     */
    private function url(): string
    {
        return sprintf('%s/ping/%s', $this->baseDomain, $this->adapterID);
    }

    /**
     * @inheritDoc
     */
    protected function write(array $record): void
    {
        $timeout = $this->options['timeout'] ?? self::CURL_DEFAULT_TIMEOUT;

        $verify = self::CURL_SSL_VERIFY;
        if(isset($this->options['verify']) && $this->options['verify'] !== null) {
            $verify = (bool) $this->options['verify'];
        }

        $ch = curl_init($this->url());

        if($verify === false) {
            curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        }

        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, $timeout);
        curl_setopt($ch, CURLOPT_TIMEOUT, $timeout + 20);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
        curl_setopt( $ch, CURLOPT_RETURNTRANSFER, true );
        curl_exec($ch);
        curl_close($ch);
    }
}