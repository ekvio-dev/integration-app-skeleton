<?php
declare(strict_types=1);

namespace Ekvio\Integration\Skeleton\Log;

use Monolog\DateTimeImmutable;
use Monolog\Handler\AbstractProcessingHandler;
use Monolog\Logger;
use Throwable;

/**
 * Class ElasticHandler
 * @package Ekvio\Integration\Skeleton\Log
 */
class ElasticHandler extends AbstractProcessingHandler
{
    private const CURL_DEFAULT_TIMEOUT = 15;
    private const CURL_SSL_VERIFY = false;

    /**
     * @var string
     */
    private $host;
    /**
     * @var string
     */
    private $user;
    /**
     * @var string
     */
    private $password;
    /**
     * @var array
     */
    private $options;

    /**
     * ElasticHandler constructor.
     * @param string $host
     * @param string $user
     * @param string $password
     * @param array $options
     * @param int $level
     * @param bool $bubble
     */
    public function __construct(string $host, string $user, string $password, array $options = [], $level = Logger::DEBUG, bool $bubble = true)
    {
        parent::__construct($level, $bubble);

        $this->host = $host;
        $this->user = $user;
        $this->password = $password;
        $this->options = $options;
    }

    /**
     * @param array $record
     */
    protected function write(array $record): void
    {
        $timeout = $this->options['timeout'] ?? self::CURL_DEFAULT_TIMEOUT;

        $verify = self::CURL_SSL_VERIFY;
        if(isset($this->options['verify']) && $this->options['verify'] !== null) {
            $verify = (bool) $this->options['verify'];
        }

        $ch = curl_init($this->host);

        $proxy = $this->options['proxy'] ?? null;
        if($proxy) {
            curl_setopt($ch, CURLOPT_PROXY, $proxy);
        }

        if($verify === false) {
            curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        }

        curl_setopt($ch, CURLOPT_USERPWD, $this->user . ':' . $this->password);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, $timeout);
        curl_setopt($ch, CURLOPT_TIMEOUT, $timeout);

        $message = json_encode([
            '@timestamp' => new DateTimeImmutable(true),
            'status' => $record['level_name'] === 'error' ? 'error' : 'success',
            'company' => $this->options['company'],
            'adapter' => $this->options['adapter'],
            'message' => $record['message']
        ], JSON_THROW_ON_ERROR);

        curl_setopt($ch, CURLOPT_POSTFIELDS, $message);
        curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type:application/json']);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
        curl_setopt( $ch, CURLOPT_RETURNTRANSFER, true );
        $result = curl_exec($ch);

        if($result) {
            try {
                $response = json_decode($result, true, 512, JSON_THROW_ON_ERROR);
                if(isset($response['error'])) {
                    fwrite(STDOUT, sprintf("Error from Elastic: Code: %s, Message: %s\n", $response['status'], $response['error']['reason']));
                }
            } catch (Throwable $exception) {
                fwrite(STDOUT, sprintf("Can not parse elastic response: %s\n", $result));
            }
        }

        if (curl_errno($ch)) {
            $error = curl_getinfo($ch);
            $message = sprintf("Bad curl response. Code: %s, Url:%s\n", $error['http_code'], $error['url']);
            fwrite(STDOUT, $message);
        }

        curl_close($ch);
    }

    /**
     * @param array $records
     */
    public function handleBatch(array $records): void
    {
        $aggregate['message'] = '';
        foreach ($records as $record) {
            $message = sprintf("[%s][%s]%s\n", $record['level_name'], $record['datetime'], $record['message']);
            $aggregate['message'] = sprintf("%s%s", $aggregate['message'], $message);
            $aggregate['context'] = $record['context'];
            $aggregate['level'] = $record['level'];
            $aggregate['level_name'] = $record['level_name'];
            $aggregate['datetime'] = $record['datetime'];
            $aggregate['extra'] = $record['extra'];
        }
        $this->handle($aggregate);
    }
}