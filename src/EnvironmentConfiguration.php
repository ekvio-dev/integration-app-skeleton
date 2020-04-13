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

    /**
     * @return array
     */
    protected function defaultConfig(): array
    {
        return  [
            'debug' => getenv('APPLICATION_DEBUG') ?: null,
            'logger' => [
                'handlers' => getenv('LOGGER_HANDLERS') ?: null,
                'config' => [
                    [
                        '__name__' => 'telegram_proxy',
                        '__class__' => ProxyTelegramHandler::class,
                        '__processors__' => [
                            ['__class__' => StacktracelessProcessor::class]
                        ],
                        'level' => getenv('LOGGER_TELEGRAM_PROXY_LEVEL') ?: null,
                        'proxy' => getenv('LOGGER_TELEGRAM_PROXY_ADDRESS') ?: null,
                        'botToken' => getenv('LOGGER_TELEGRAM_PROXY_BOT_ID') ?: null,
                        'chatId' => getenv('LOGGER_TELEGRAM_PROXY_CHAT_ID') ?: null,
                        'options' => [
                            'proxy' => getenv('CURL_PROXY') ?: null,
                            'timeout' => getenv('CURL_CONNECT_TIMEOUT') ?: null,
                            'verify' => getenv('CURL_SSL_VERIFY') !== false ? getenv('CURL_SSL_VERIFY') : null,
                        ],
                    ],
                ],
            ],
        ];
    }
}