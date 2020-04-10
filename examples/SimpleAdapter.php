<?php

namespace App\Examples;

use App\AbstractAdapter;
use Exception;

/**
 * Class Adapter
 * @package App
 */
class SimpleAdapter extends AbstractAdapter
{
    /**
     * @inheritDoc
     * @throws Exception
     */
    public function run(): void
    {
        throw new Exception('Alert!');
    }
}