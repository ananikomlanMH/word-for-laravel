<p align="center"><a href="https://github.com/ananikomlanMH/word-for-laravel" target="_blank"><img src="/art/logo.svg" width="400"></a></p>

<p>
    <a href="https://github.com/ananikomlanmh/word-for-laravel/actions"><img src="https://github.com/ananikomlanmh/word-for-laravel/actions/workflows/tests.yml/badge.svg" alt="Build Status"></a>
    <a href="https://github.com/ananikomlanmh/word-for-laravel/actions/workflows/quality.yml"><img src="https://github.com/ananikomlanmh/word-for-laravel/actions/workflows/quality.yml/badge.svg" alt="Coding Standards" /></a>
    <a href="https://packagist.org/packages/ananikomlanmh/word-for-laravel"><img src="https://img.shields.io/packagist/dt/ananikomlanmh/word-for-laravel" alt="Total Downloads"></a>
    <a href="https://packagist.org/packages/ananikomlanmh/word-for-laravel"><img src="https://img.shields.io/packagist/v/ananikomlanmh/word-for-laravel" alt="Latest Stable Version"></a>
    <a href="https://packagist.org/packages/ananikomlanmh/word-for-laravel"><img src="https://img.shields.io/packagist/l/ananikomlanmh/word-for-laravel" alt="License"></a>
</p>

Generate Word documents (.docx) from Laravel Blade templates using PHPWord under the hood. This package provides an elegant API for creating professional Word documents with the full power of Blade templating.

## Table of Contents

- **[Requirements](#requirements)**
- **[Installation](#installation)**
- **[Basic Usage](#basic-usage)**
  - [Simple Document Generation](#simple-document-generation)
  - [Saving to Disk](#saving-to-disk)
  - [Setting Document Properties](#setting-document-properties)
  - [Controller Example](#controller-example)
- **[Creating Word Templates](#creating-word-templates)**
  - [Using Artisan Command](#using-artisan-command)
  - [Template Structure](#template-structure)
  - [Supported HTML Elements](#supported-html-elements)
- **[Advanced Usage](#advanced-usage)**
  - [Getting Document Content](#getting-document-content)
  - [Accessing PHPWord Instance](#accessing-phpword-instance)
  - [Resetting the Converter](#resetting-the-converter)
- **[Configuration](#configuration)**
  - [Orientation](#orientation)
- **[Testing](#testing)**
- **[Examples](#examples)**
  - [Invoice Example](#invoice-example)
  - [Report with Tables](#report-with-tables)
- **[CSS Support](#css-support)**
- **[Images](#images)**
- **[Changelog](#changelog)**
- **[Contributing](#contributing)**
- **[Security](#security)**
- **[Credits](#credits)**
- **[License](#license)**

## Requirements

- PHP 8.1 or higher
- Laravel 10.x or higher

## Installation

Install the package via Composer:

```bash
composer require ananikomlanmh/word-for-laravel
```

Publish the configuration file (optional):

```bash
php artisan vendor:publish --tag="word-for-laravel-config"
```

## Basic Usage

### Simple Document Generation

```php
use WordForLaravel\Facades\WordForLaravel;

// Generate and download a document
return WordForLaravel::load('word.invoice', [
        'invoiceNumber' => 'INV-001',
        'client' => 'John Doe',
        'total' => 1500.00
    ])
    ->download('invoice.docx');
```

### Saving to Disk

```php
use Illuminate\Support\Facades\Storage;
use WordForLaravel\Facades\WordForLaravel;

// Save to the default disk
WordForLaravel::load('word.report', $data)
    ->save('reports/monthly-report.docx');

// Save to a specific disk
WordForLaravel::load('word.report', $data)
    ->save('reports/monthly-report.docx', 's3');

// Get the full path
$path = Storage::disk('local')->path('reports/monthly-report.docx');
```

### Setting Document Properties

```php
WordForLaravel::load('word.contract', $data)
    ->setProperties([
        'title' => 'Employment Contract',
        'creator' => 'HR Department',
        'company' => 'ACME Corporation',
        'subject' => 'Contract Agreement',
        'description' => 'Employment contract for new hire'
    ])
    ->download('contract.docx');
```

### Controller Example

```php
<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use WordForLaravel\Facades\WordForLaravel;

class DocumentController extends Controller
{
    public function downloadInvoice($id)
    {
        $invoice = Invoice::findOrFail($id);
        
        return WordForLaravel::load('word.invoice', [
                'invoice' => $invoice,
                'client' => $invoice->client,
                'items' => $invoice->items
            ])
            ->setProperties([
                'title' => "Invoice #{$invoice->number}",
                'creator' => config('app.name'),
            ])
            ->download("invoice-{$invoice->number}.docx");
    }
}
```

## Creating Word Templates

### Using Artisan Command

Generate a new Blade template specifically for Word documents:

```bash
php artisan make:word-template invoice
```

This creates a file at `resources/views/word/invoice.blade.php` with a basic structure.

### Template Structure

Create a Blade view with HTML structure. The package converts HTML to Word format:

```blade
<!-- resources/views/word/invoice.blade.php -->
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Invoice</title>
    <style>
        body {
            font-family: Arial, sans-serif;
        }
        h1 {
            color: #2c3e50;
            font-size: 24pt;
        }
        table {
            width: 100%;
            border-collapse: collapse;
        }
        th, td {
            border: 1px solid #ddd;
            padding: 8px;
        }
        th {
            background-color: #f2f2f2;
        }
    </style>
</head>
<body>
    <h1>INVOICE #{{ $invoiceNumber }}</h1>
    
    <p><strong>Date:</strong> {{ now()->format('d/m/Y') }}</p>
    <p><strong>Client:</strong> {{ $client }}</p>
    
    <h2>Items</h2>
    <table>
        <thead>
            <tr>
                <th>Description</th>
                <th>Quantity</th>
                <th>Price</th>
                <th>Total</th>
            </tr>
        </thead>
        <tbody>
            @foreach($items as $item)
            <tr>
                <td>{{ $item['description'] }}</td>
                <td>{{ $item['quantity'] }}</td>
                <td>{{ number_format($item['price'], 2) }} €</td>
                <td>{{ number_format($item['total'], 2) }} €</td>
            </tr>
            @endforeach
        </tbody>
    </table>
    
    <p><strong>Total: {{ number_format($total, 2) }} €</strong></p>
</body>
</html>
```

### Supported HTML Elements

The package supports standard HTML elements that are converted to Word format:

- **Headings**: `<h1>`, `<h2>`, `<h3>`, `<h4>`, `<h5>`, `<h6>`
- **Paragraphs**: `<p>` with inline formatting
- **Inline text**: `<strong>`, `<b>`, `<em>`, `<i>`, `<u>`, `<del>`, `<s>`, `<strike>`, `<mark>`, `<code>`, `<small>`, `<sub>`, `<sup>`, `<a>`
- **Lists**: `<ul>`, `<ol>`, `<li>` (nested lists supported)
- **Tables**: `<table>`, `<thead>`, `<tbody>`, `<tr>`, `<th>`, `<td>` (colspan/rowspan basic support)
- **Line breaks and rules**: `<br>`, `<hr>`

Special utility tags handled by the parser:

- **`<pagebreak orientation="..." />`**: splits the document into sections. Orientation can be `landscape`, `portrait`, `L`, or `P`.
- **`<page-number format="..." restart="..." />`**: renders dynamic page numbers. Supported variables: `{PAGE}`, `{NUMPAGES}`, `{SECTIONPAGES}`.
- **`<wordheader>...</wordheader>`**: content rendered in the section header.
- **`<wordfooter>...</wordfooter>`**: content rendered in the section footer.

## Advanced Usage

### Getting Document Content

```php
// Get raw content as string (useful for storage or email attachments)
$content = WordForLaravel::load('word.report', $data)
    ->getContent();

// Store in database or send via email
Mail::send('emails.report', [], function ($message) use ($content) {
    $message->attachData($content, 'report.docx', [
        'mime' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
    ]);
});
```

### Accessing PHPWord Instance

For advanced customization, access the underlying PHPWord object:

```php
WordForLaravel::load('word.custom', $data);

$phpWord = WordForLaravel::getPhpWord();

// Add custom section
$section = $phpWord->addSection();
$section->addText('Custom content added directly via PHPWord');

// Download the customized document
return WordForLaravel::download('custom.docx');
```

### Resetting the Converter

When generating multiple documents in the same request:

```php
// First document
WordForLaravel::load('document1', $data1)
    ->save('doc1.docx');

// Reset for new document
WordForLaravel::reset();

// Second document
WordForLaravel::load('document2', $data2)
    ->save('doc2.docx');
```

## Configuration

The configuration file (`config/word-for-laravel.php`) allows you to customize:

```php
return [
    // Default disk for saving documents
    'default_disk' => env('BLADE_TO_WORD_DISK', 'local'),

    // Temporary directory for document generation
    'temp_dir' => env('BLADE_TO_WORD_TEMP_DIR', sys_get_temp_dir()),

    // Default orientation for sections that don't specify one
    'default_orientation' => env('WORD_DEFAULT_ORIENTATION', 'portrait'),

    // Default section styling (margins in twips: 1000 ≈ 1.76cm)
    'section_style' => [
        'marginTop' => 1000,
        'marginBottom' => 1000,
        'marginLeft' => 1000,
        'marginRight' => 1000,
        'headerHeight' => 720,
        'footerHeight' => 720,
    ],

    // Default document properties
    'default_properties' => [
        'creator' => env('APP_NAME', 'Laravel'),
        'company' => env('APP_NAME', 'Laravel'),
    ],
];
```

### Orientation

Set default page orientation for sections that do not specify one via `<pagebreak>`:

```php
WordForLaravel::orientation('landscape')
    ->load('word.report', $data)
    ->download('report.docx');
```

You can also control orientation per section using `<pagebreak orientation="landscape" />` in your Blade HTML.

## Testing

Run the tests with:

```bash
composer test
```

Run tests with coverage:

```bash
composer test-coverage
```

## Examples

### Invoice Example

```php
// Controller
public function generateInvoice(Invoice $invoice)
{
    return WordForLaravel::load('word.invoice', [
            'invoice' => $invoice,
            'company' => [
                'name' => 'ACME Corp',
                'address' => '123 Business St',
                'city' => 'Paris',
            ],
            'items' => $invoice->items->map(fn($item) => [
                'description' => $item->description,
                'quantity' => $item->quantity,
                'price' => $item->price,
                'total' => $item->quantity * $item->price,
            ]),
            'total' => $invoice->total,
        ])
        ->setProperties([
            'title' => "Invoice {$invoice->number}",
            'subject' => 'Sales Invoice',
        ])
        ->download("invoice-{$invoice->number}.docx");
}
```

### Report with Tables

```blade
<!-- resources/views/word/sales-report.blade.php -->
<!DOCTYPE html>
<html>
<body>
    <h1>Sales Report - {{ $period }}</h1>
    
    <h2>Summary</h2>
    <p>Total Sales: <strong>{{ number_format($totalSales, 2) }} €</strong></p>
    <p>Total Orders: <strong>{{ $totalOrders }}</strong></p>
    
    <h2>Top Products</h2>
    <table>
        <thead>
            <tr>
                <th>Product</th>
                <th>Units Sold</th>
                <th>Revenue</th>
            </tr>
        </thead>
        <tbody>
            @foreach($topProducts as $product)
            <tr>
                <td>{{ $product['name'] }}</td>
                <td>{{ $product['units'] }}</td>
                <td>{{ number_format($product['revenue'], 2) }} €</td>
            </tr>
            @endforeach
        </tbody>
    </table>
</body>
</html>
```

### CSS Support

The converter supports a useful subset of CSS on tags, classes, and inline styles. Examples:

- **Font**: `font-size`, `color`, `font-family`, `font-weight`, `font-style`, `text-decoration`, `background-color`, `text-transform`
- **Paragraph**: `text-align`, `line-height`, `margin-left` (indent), `margin-top`/`margin-bottom` (spacing), `text-indent`
- **Tables/Cells**: `border-color`, `border-width`, `vertical-align`, `width`, `background-color`
- **Images**: `width`, `height`, `text-align`, `float`, margins

Units supported include `px`, `pt`, `cm`, `mm`, `in`. Colors support hex (`#RRGGBB`), `rgb(...)`, and named colors.

### Images

Images can be referenced by:

- **Local path** (absolute or relative)
- **Remote URL** (downloaded at runtime)
- **Data URI** (`data:image/png;base64,...`)

Alignment and sizing can be controlled via CSS or HTML attributes (`width`, `height`).

## Changelog

Please see [CHANGELOG](CHANGELOG.md) for more information on what has changed recently.

## Contributing

Please see [CONTRIBUTING](CONTRIBUTING.md) for details.

## Security

If you discover any security-related issues, please email inanakomlan@gmail.com instead of using the issue tracker.

## Credits

- [Anani Komlan Mawulom H](https://github.com/ananikomlanMH)
- [PHPWord](https://github.com/PHPOffice/PHPWord)

## License

The MIT License (MIT). Please see [License File](LICENSE.md) for more information.
