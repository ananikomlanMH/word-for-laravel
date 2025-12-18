<?php

namespace WordForLaravel\Facades;

use Illuminate\Support\Facades\Facade;

/**
 * @see \WordForLaravel\WordForLaravel
 */
class WordForLaravel extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return 'word-for-laravel';
    }
}
