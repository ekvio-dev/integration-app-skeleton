<?php

namespace Ekvio\Integration\Skeleton;

use Monolog\Processor\ProcessorInterface;

/**
 * Class StacktracelessProcessor
 * @package Ekvio\Integration\Skeleton
 */
class StacktracelessProcessor implements ProcessorInterface
{
    public function __construct()
    {
    }

    /**
     * @inheritDoc
     */
    public function __invoke(array $record)
    {
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
    }
}