<?php

use WordForLaravel\Parsers\CssParser;

it('converts font styles correctly', function () {
    $p = new CssParser;

    $font = $p->convertToFontStyle([
        'font-size' => '16px',
        'color' => '#ff00ff',
        'font-family' => 'Times New Roman, serif',
        'font-weight' => '700',
        'font-style' => 'italic',
        'text-decoration' => 'underline',
        'background-color' => 'rgb(255,255,0)',
        'text-transform' => 'uppercase',
        'vertical-align' => 'sub',
    ]);

    expect($font['size'])->toBe(12) // 16px * 0.75
        ->and($font['color'])->toBe('FF00FF')
        ->and($font['name'])->toBe('Times New Roman')
        ->and($font['bold'])->toBeTrue()
        ->and($font['italic'])->toBeTrue()
        ->and($font['underline'])->toBe('single')
        ->and($font['bgColor'])->toBe('FFFF00')
        ->and($font['allCaps'])->toBeTrue()
        ->and($font['subScript'])->toBeTrue();
});

it('converts paragraph styles correctly', function () {
    $p = new CssParser;

    $para = $p->convertToParagraphStyle([
        'text-align' => 'justify',
        'line-height' => '1.5',
        'margin-left' => '24pt',
        'margin-top' => '10pt',
        'margin-bottom' => '8pt',
        'text-indent' => '12pt',
    ]);

    expect($para['alignment'])->toBe('both')
        ->and($para['lineHeight'])->toBe(1.5)
        ->and($para['indent'])->toBe(480) // 24pt * 20 twips
        ->and($para['spaceBefore'])->toBe(200) // 10pt * 20
        ->and($para['spaceAfter'])->toBe(160) // 8pt * 20
        ->and($para['indentation']['firstLine'])->toBe(240); // 12pt * 20
});

it('converts table and cell styles including border and width', function () {
    $p = new CssParser;

    $table = $p->convertToTableStyle([
        'border-color' => '#000',
        'border-width' => '2px',
        'width' => '100%',
        'background-color' => '#f0f0f0',
    ]);

    expect($table['borderColor'])->toBe('000000')
        ->and($table['borderSize'])->toBe(16) // 2px * 8
        ->and($table['width'])->toBe(5000) // 100 * 50
        ->and($table['bgColor'])->toBe('F0F0F0');

    $cell = $p->convertToCellStyle([
        'background-color' => '#fff',
        'border-color' => 'red',
        'border-width' => '1pt',
        'vertical-align' => 'middle',
        'padding' => '12pt',
    ]);

    expect($cell['bgColor'])->toBe('FFFFFF')
        ->and($cell['borderTopColor'])->toBe('FF0000')
        ->and($cell['borderTopSize'])->toBe(8) // 1pt * 8
        ->and($cell['valign'])->toBe('center')
        ->and($cell['cellMarginTop'])->toBe(240); // 12pt * 20
});
