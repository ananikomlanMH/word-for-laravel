<?php

namespace WordForLaravel\Commands;

use Illuminate\Console\Command;
use Illuminate\Filesystem\Filesystem;

class WordForLaravelCommand extends Command
{
    protected $signature = 'make:word-template {name : The name of the template}';

    protected $description = 'Create a new Blade template for Word document generation';

    public function handle(Filesystem $files): int
    {
        $name = $this->argument('name');
        $path = resource_path('views/word/'.$name.'.blade.php');

        if ($files->exists($path)) {
            $this->error(sprintf('Template [%s] already exists!', $name));

            return self::FAILURE;
        }

        $files->ensureDirectoryExists(dirname($path));

        $stub = $this->getStub();
        $files->put($path, $stub);

        $this->info(sprintf('Template [%s] created successfully at: %s', $name, $path));

        return self::SUCCESS;
    }

    protected function getStub(): string
    {
        return <<<'BLADE'
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Document Template</title>
    <style>
        body {
            font-family: Arial, sans-serif;
        }
        h1 {
            color: #2c3e50;
            font-size: 24pt;
        }
        h2 {
            color: #34495e;
            font-size: 18pt;
        }
        p {
            font-size: 12pt;
            line-height: 1.5;
        }
        table {
            width: 100%;
            border-collapse: collapse;
        }
        th, td {
            border: 1px solid #ddd;
            padding: 8px;
            text-align: left;
        }
        th {
            background-color: #f2f2f2;
        }
    </style>
</head>
<body>
    <h1>Document Title</h1>

    <p>This is a sample paragraph. Replace this content with your dynamic data.</p>

    <h2>Sample Section</h2>

    <p>You can use Blade syntax here:</p>
    <p><strong>Example:</strong> {{ $variable ?? 'Default value' }}</p>

    @if(isset($items))
    <h2>Sample Table</h2>
    <table>
        <thead>
            <tr>
                <th>Column 1</th>
                <th>Column 2</th>
            </tr>
        </thead>
        <tbody>
            @foreach($items as $item)
            <tr>
                <td>{{ $item['name'] }}</td>
                <td>{{ $item['value'] }}</td>
            </tr>
            @endforeach
        </tbody>
    </table>
    @endif
</body>
</html>
BLADE;
    }
}
