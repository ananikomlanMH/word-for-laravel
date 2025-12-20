<?php

namespace WordForLaravel;

use Illuminate\Contracts\Filesystem\FileNotFoundException;
use Illuminate\Contracts\View\Factory as ViewFactory;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Storage;
use PhpOffice\PhpWord\Exception\Exception;
use PhpOffice\PhpWord\IOFactory;
use PhpOffice\PhpWord\PhpWord;
use WordForLaravel\Parsers\CssParser;
use WordForLaravel\Parsers\HtmlParser;
use WordForLaravel\Renderers\DocumentRenderer;

class WordForLaravel
{
    protected PhpWord $phpWord;

    protected array $config;

    protected CssParser $cssParser;

    protected HtmlParser $htmlParser;

    protected DocumentRenderer $renderer;

    protected string $defaultOrientation = 'portrait';

    public function __construct(
        protected ViewFactory $viewFactory,
        protected Filesystem $files,
        array $config = []
    ) {
        $this->phpWord = new PhpWord;
        $this->config = $config;
        $this->defaultOrientation = $config['default_orientation'] ?? 'portrait';

        $this->cssParser = new CssParser;
        $this->htmlParser = new HtmlParser($this->cssParser);
        $this->renderer = new DocumentRenderer($this->phpWord, $this->cssParser);
    }

    /**
     * Generate Word document from Blade view
     */
    public function load(string $view, array $data = []): self
    {
        $html = $this->viewFactory->make($view, $data)->render();

        $this->htmlParser->parse($html);
        $this->renderer->render($this->htmlParser->getSections(), $this->defaultOrientation);

        return $this;
    }

    /**
     * Set default page orientation
     */
    public function orientation(string $orientation): self
    {
        $this->defaultOrientation = in_array(strtolower($orientation), ['l', 'landscape'])
            ? 'landscape'
            : 'portrait';

        return $this;
    }

    /**
     * Save document to disk
     *
     * @throws Exception|FileNotFoundException
     */
    public function save(string $path, ?string $disk = null): string
    {
        $disk = $disk ?? $this->config['default_disk'] ?? 'local';

        $tempPath = $this->getTempPath();
        $writer = IOFactory::createWriter($this->phpWord);
        $writer->save($tempPath);

        $storage = Storage::disk($disk);
        $storage->put($path, $this->files->get($tempPath));

        $this->files->delete($tempPath);

        return $storage->path($path);
    }

    /**
     * Download document
     *
     * @throws Exception|FileNotFoundException
     */
    public function download(string $filename = 'document.docx'): Response
    {
        $tempPath = $this->getTempPath();
        $writer = IOFactory::createWriter($this->phpWord, 'Word2007');
        $writer->save($tempPath);

        $content = $this->files->get($tempPath);
        $this->files->delete($tempPath);

        return response($content, 200, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
            'Content-Disposition' => 'attachment; filename="'.$filename.'"',
            'Content-Length' => strlen($content),
        ]);
    }

    /**
     * Get document content as string
     */
    public function getContent(): string
    {
        $tempPath = $this->getTempPath();
        $writer = IOFactory::createWriter($this->phpWord, 'Word2007');
        $writer->save($tempPath);

        $content = $this->files->get($tempPath);
        $this->files->delete($tempPath);

        return $content;
    }

    /**
     * Set document properties
     */
    public function setProperties(array $properties): self
    {
        $docProperties = $this->phpWord->getDocInfo();

        if (isset($properties['title'])) {
            $docProperties->setTitle($properties['title']);
        }

        if (isset($properties['creator'])) {
            $docProperties->setCreator($properties['creator']);
        }

        if (isset($properties['company'])) {
            $docProperties->setCompany($properties['company']);
        }

        if (isset($properties['subject'])) {
            $docProperties->setSubject($properties['subject']);
        }

        if (isset($properties['description'])) {
            $docProperties->setDescription($properties['description']);
        }

        return $this;
    }

    /**
     * Create custom numbering style
     */
    public function addNumberingStyle(string $styleName, array $levels): self
    {
        $this->phpWord->addNumberingStyle($styleName, [
            'type' => 'multilevel',
            'levels' => $levels,
        ]);

        return $this;
    }

    /**
     * Get PhpWord instance for advanced usage
     */
    public function getPhpWord(): PhpWord
    {
        return $this->phpWord;
    }

    /**
     * Reset converter for new document
     */
    public function reset(): self
    {
        $this->phpWord = new PhpWord;
        $this->cssParser = new CssParser;
        $this->htmlParser = new HtmlParser($this->cssParser);
        $this->renderer = new DocumentRenderer($this->phpWord, $this->cssParser);

        return $this;
    }

    /**
     * Get temporary file path
     */
    protected function getTempPath(): string
    {
        $tempDir = $this->config['temp_dir'] ?? sys_get_temp_dir();

        return $tempDir.'/'.uniqid('blade-to-word-', true).'.docx';
    }
}
