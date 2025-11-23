<?php

namespace WordForLaravel\Facades;

use Illuminate\Support\Facades\Facade;

/**
 * @see \Anani Komlan Mawulom Hounkpati\WordForLaravel\WordForLaravel
 */
class WordForLaravel extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return 'word-for-laravel';
    }
}
