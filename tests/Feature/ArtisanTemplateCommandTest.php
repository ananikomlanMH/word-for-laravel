<?php

use Illuminate\Filesystem\Filesystem;

it('creates a new word blade template via artisan command', function (): void {
    $fs = new Filesystem;
    $viewDir = resource_path('views/word');
    $fs->ensureDirectoryExists($viewDir);

    // Ensure target file does not exist
    $name = 'artisan_generated_'.uniqid();
    $path = $viewDir.'/'.$name.'.blade.php';
    if ($fs->exists($path)) {
        $fs->delete($path);
    }

    // Run artisan command
    $this->artisan('make:word-template '.$name)
        ->assertSuccessful();

    expect($fs->exists($path))->toBeTrue();

    // Clean up
    $fs->delete($path);
});
