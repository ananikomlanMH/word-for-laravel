<?php

use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\Facades\Storage;
use WordForLaravel\Facades\WordForLaravel;

it('can load a simple blade view and get content', function () {
    // Arrange: create a minimal blade view under resources/views/word/
    $fs = new Filesystem;
    $viewDir = resource_path('views/word');
    $fs->ensureDirectoryExists($viewDir);
    $viewPath = $viewDir.'/sample.blade.php';

    $html = <<<'BLADE'
<!DOCTYPE html>
<html>
<body>
    <wordheader>
        <p style="text-align:center"><strong>HEADER</strong></p>
    </wordheader>

    <h1 style="color:#2c3e50">Hello</h1>
    <p><strong>World</strong> with <em>inline</em> <u>formatting</u>.</p>

    <pagebreak orientation="landscape" />

    <wordfooter>
        <p style="text-align:center">Page Footer</p>
    </wordfooter>
</body>
</html>
BLADE;

    $fs->put($viewPath, $html);

    // Act
    $content = WordForLaravel::load('word.sample', [])->getContent();

    // Assert
    expect($content)->toBeString()->not->toBe('')->and(strlen($content))->toBeGreaterThan(100);

    // Cleanup
    $fs->delete($viewPath);
});

it('can save a generated document to storage', function () {
    // Arrange
    Storage::fake('local');

    $fs = new Filesystem;
    $viewDir = resource_path('views/word');
    $fs->ensureDirectoryExists($viewDir);
    $viewPath = $viewDir.'/saveable.blade.php';
    $fs->put($viewPath, '<p>Save me</p>');

    // Act
    WordForLaravel::load('word.saveable', [])->save('reports/test.docx', 'local');

    // Assert
    Storage::disk('local')->assertExists('reports/test.docx');

    // Cleanup
    $fs->delete($viewPath);
});

it('can download a generated document', function () {
    // Arrange
    $fs = new Filesystem;
    $viewDir = resource_path('views/word');
    $fs->ensureDirectoryExists($viewDir);
    $viewPath = $viewDir.'/downloadable.blade.php';
    $fs->put($viewPath, '<p>Download me</p>');

    // Act
    $response = WordForLaravel::load('word.downloadable', [])->download('file.docx');

    // Assert
    expect($response->getStatusCode())->toBe(200);
    expect($response->headers->get('Content-Type'))
        ->toBe('application/vnd.openxmlformats-officedocument.wordprocessingml.document');
    expect($response->headers->get('Content-Disposition'))
        ->toBe('attachment; filename="file.docx"');

    // Cleanup
    $fs->delete($viewPath);
});

it('can set document properties and access PhpWord instance', function () {
    // Arrange
    $fs = new Filesystem;
    $viewDir = resource_path('views/word');
    $fs->ensureDirectoryExists($viewDir);
    $viewPath = $viewDir.'/props.blade.php';
    $fs->put($viewPath, '<p>Props</p>');

    // Act
    WordForLaravel::load('word.props', [])
        ->setProperties([
            'title' => 'My Doc',
            'creator' => 'Unit',
            'company' => 'Acme',
            'subject' => 'Test',
            'description' => 'Description',
        ]);

    $phpWord = WordForLaravel::getPhpWord();
    $info = $phpWord->getDocInfo();

    // Assert
    expect($info->getTitle())->toBe('My Doc');
    expect($info->getCreator())->toBe('Unit');
    expect($info->getCompany())->toBe('Acme');
    expect($info->getSubject())->toBe('Test');
    expect($info->getDescription())->toBe('Description');

    // Reset should yield a fresh PhpWord instance
    $prev = $phpWord;
    WordForLaravel::reset();
    $now = WordForLaravel::getPhpWord();
    expect(spl_object_id($now))->not->toBe(spl_object_id($prev));

    // Cleanup
    $fs->delete($viewPath);
});
