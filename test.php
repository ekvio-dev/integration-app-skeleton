<?php

declare(strict_types=1);

require_once __DIR__ . '/vendor/autoload.php';

class Step implements \Ekvio\Integration\Contracts\Invoker
{

    public function __invoke(array $arguments = [])
    {
        throw new Exception('Failed');
    }

    public function name(): string
    {
        return 'Step invoker';
    }
}

$config = \Ekvio\Integration\Skeleton\EnvironmentConfiguration::create();
$adapter = new \Ekvio\Integration\Skeleton\Adapter($config);
$adapter->run(Step::class);