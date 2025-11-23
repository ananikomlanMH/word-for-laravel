<?php

namespace WordForLaravel\Tests;

use Orchestra\Testbench\TestCase as Orchestra;
use WordForLaravel\Facades\WordForLaravel as WordForLaravelFacade;
use WordForLaravel\WordForLaravelServiceProvider;

abstract class TestCase extends Orchestra
{
    protected function getPackageProviders($app)
    {
        return [
            WordForLaravelServiceProvider::class,
        ];
    }

    protected function getPackageAliases($app)
    {
        return [
            'WordForLaravel' => WordForLaravelFacade::class,
        ];
    }
}
