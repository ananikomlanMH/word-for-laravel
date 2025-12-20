<?php

namespace WordForLaravel;

use Illuminate\Foundation\Application;
use Spatie\LaravelPackageTools\Commands\InstallCommand;
use Spatie\LaravelPackageTools\Package;
use Spatie\LaravelPackageTools\PackageServiceProvider;
use WordForLaravel\Commands\WordForLaravelCommand;

class WordForLaravelServiceProvider extends PackageServiceProvider
{
    public function register(): void
    {
        parent::register();

        $this->app->singleton(WordForLaravel::class, function (Application $app): WordForLaravel {
            return new WordForLaravel(
                $app['view'],
                $app['files'],
                $app['config']->get('word-for-laravel')
            );
        });

        $this->app->alias(WordForLaravel::class, 'word-for-laravel');
    }

    public function configurePackage(Package $package): void
    {
        $package
            ->name('word-for-laravel')
            ->hasConfigFile()
            ->hasCommand(WordForLaravelCommand::class)
            ->hasInstallCommand(function (InstallCommand $command): void {
                $command
                    ->publishConfigFile();
            });
    }
}
