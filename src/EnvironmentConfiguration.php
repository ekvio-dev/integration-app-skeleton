<?php

namespace Ekvio\Integration\Skeleton;

/**
 * Class EnvironmentConfiguration
 * @package App
 */
class EnvironmentConfiguration implements ApplicationConfiguration
{
    protected $configuration;

    /**
     * EnvironmentConfiguration constructor.
     */
    private function __construct()
    {
    }

    /**
     * @return static
     */
    public static function create(): self
    {
        $self = new self();
        $self->configuration = $self->defaultConfig();

        return $self;
    }

    /**
     * @inheritDoc
     */
    public function get(): array
    {
        return $this->configuration;
    }

    protected function defaultConfig(): array
    {
        return  [
            'logger' => [
                'handlers' => getenv('LOGGER_HANDLERS') ? getenv('LOGGER_HANDLERS') : null,
                'config' => [
                    'telegram_proxy' => [
                        'level' => getenv('LOGGER_TELEGRAM_PROXY_LEVEL') ? getenv('LOGGER_TELEGRAM_PROXY_LEVEL') : null,
                        'proxy' => getenv('LOGGER_TELEGRAM_PROXY_ADDRESS') ? getenv('LOGGER_TELEGRAM_PROXY_ADDRESS') : null,
                        'bot' => getenv('LOGGER_TELEGRAM_PROXY_BOT_ID') ? getenv('LOGGER_TELEGRAM_PROXY_BOT_ID') : null,
                        'chat' => getenv('LOGGER_TELEGRAM_PROXY_CHAT_ID') ? getenv('LOGGER_TELEGRAM_PROXY_CHAT_ID') : null,
                        'options' => [
                            'proxy' => getenv('CURL_PROXY') ? getenv('CURL_PROXY') : null,
                            'timeout' => getenv('CURL_CONNECT_TIMEOUT') ? getenv('CURL_CONNECT_TIMEOUT') : null,
                            'verify' => getenv('CURL_SSL_VERIFY') !== false ? getenv('CURL_SSL_VERIFY') : null,
                        ],
                    ],
                ],
            ],
        ];
    }
}