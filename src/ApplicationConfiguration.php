<?php

namespace App;

/**
 * Interface ApplicationConfiguration
 * @package App
 */
interface ApplicationConfiguration
{
    /**
     * @return array
     */
    public function get(): array;
}