<?php

declare(strict_types=1);


namespace Ekvio\Integration\Skeleton\Health;


class HealthCheckIO implements HealthChecker
{
    private $config;

    public function __construct(HealthcheckIOConfig $config)
    {
        $this->config = $config;
    }
    public function success(): void
    {
        $url = $this->buildUrl();
        $response = file_get_contents($url, false, $this->getHttpStreamContext());
        $this->processResponse($url, $response);
    }

    public function failure(): void
    {
        $url = $this->buildUrl() . '/fail';
        $response = file_get_contents($url, false, $this->getHttpStreamContext());
        $this->processResponse($url, $response);
    }

    private function buildUrl(): string
    {
        return sprintf('%s/ping/%s', $this->config->host(), $this->config->uid());
    }

    private function getHttpStreamContext()
    {
        return stream_context_create([
            'ssl' => [
                'verify_peer' => false,
                'verify_peer_name' => false
            ],
            'http' => [
                'ignore_errors' => true,
                'timeout' => 5.0
            ]
        ]);
    }

    private function processResponse($url, $response): void
    {
        if ($response === false) {
            $this->log(sprintf('Unable get %s', $url));
            return;
        }

        if (mb_strtolower($response) === 'ok') {
            return;
        }

        if (mb_strtolower($response) === 'not found') {
            $this->log(sprintf('UID %s not found in service. Check adapter UID in %s', $this->config->uid(), $this->config->host()));
            return;
        }

        $this->log(sprintf('Unable get %s', $url));
    }

    private function log(string $message): void
    {
        fwrite(STDOUT, $message . PHP_EOL);
    }
}