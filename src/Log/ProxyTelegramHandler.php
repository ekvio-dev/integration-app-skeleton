<?php

namespace Ekvio\Integration\Skeleton\Log;

use Monolog\Handler\AbstractProcessingHandler;
use Monolog\Logger;

/**
 * Class ProxyHandler
 * @package App
 */
class ProxyTelegramHandler extends AbstractProcessingHandler
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
     * ProxyTelegramHandler constructor.
     * @param string $proxy
     * @param string $botToken
     * @param string $chatId
     * @param array $options
     * @param int $level
     * @param bool $bubble
     */
    public function __construct(string $proxy, string $botToken, string $chatId, array $options = [], $level = self::DEFAULT_LEVEL, bool $bubble = true)
    {
        $this->proxy = $proxy;
        $this->botToken = $botToken;
        $this->chatId = $chatId;
        $this->options = $options;

        parent::__construct($level, $bubble);
    }

    /**
     * @return string
     */
    private function url(): string
    {
        return sprintf('%s/%s/%s', $this->proxy, $this->botToken, $this->chatId);
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

        $proxy = $this->options['proxy'] ?? null;
        if($proxy) {
            curl_setopt($ch, CURLOPT_PROXY, $proxy);
        }

        if($verify === false) {
            curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        }

        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, $timeout);
        curl_setopt($ch, CURLOPT_TIMEOUT, $timeout + 20);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode(['¯\\_(ツ)_/¯ ' => $record['formatted']], JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES));
        curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type:application/json']);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
        curl_setopt( $ch, CURLOPT_RETURNTRANSFER, true );
        curl_exec($ch);

        /*$error = null;
        if (!curl_errno($ch)) {
            $error = curl_getinfo($ch);
            $message = sprintf("Bad curl response. Code: %s, Url:%s\n", $error['http_code'], $error['url']);
            fwrite(STDOUT, $message);
        }*/
        curl_close($ch);
    }
}