<?php

use WordForLaravel\Parsers\CssParser;
use WordForLaravel\Parsers\HtmlParser;

it('parses sections, header, footer and orientation via special tags', function () {
    $css = new CssParser;
    $parser = new HtmlParser($css);

    $html = <<<'HTML'
<!DOCTYPE html>
<html>
<body>
    <wordheader><p>Header One</p></wordheader>
    <h1>First</h1>
    <p>Content A</p>

    <pagebreak orientation="L" />

    <wordfooter><p>Footer Two</p></wordfooter>
    <h2>Second</h2>
    <p>Content B</p>
</body>
</html>
HTML;

    $parser->parse($html);

    $sections = $parser->getSections();
    expect($sections)->toBeArray()->and(count($sections))->toBe(2);

    // First section (no explicit orientation)
    expect($sections[0]['orientation'])->toBeNull();
    expect($sections[0]['header'])->not->toBeNull();
    expect($sections[0]['footer'])->toBeNull();
    expect($sections[0]['content'])->toBeArray()->and(count($sections[0]['content']))->toBeGreaterThan(0);

    // Second section (landscape from 'L')
    expect($sections[1]['orientation'])->toBe('landscape');
    expect($sections[1]['header'])->toBeNull();
    expect($sections[1]['footer'])->not->toBeNull();
    expect($sections[1]['content'])->toBeArray()->and(count($sections[1]['content']))->toBeGreaterThan(0);
});
