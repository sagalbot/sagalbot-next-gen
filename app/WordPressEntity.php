<?php

namespace App;

use Carbon\Carbon;
use DateTimeZone;
use Illuminate\Support\Str;
use phpDocumentor\Reflection\Types\Self_;

class WordPressEntity
{
    /**
     * @var \SimpleXMLElement
     */
    public $element;

    /**
     * @var \Illuminate\Support\Collection
     */
    protected $properties;

    protected $namespaces;

    /**
     * @var string
     */
    protected static $MORE = '<!--more-->';

    public function __construct(\SimpleXMLElement $element)
    {
        $this->element = $element;
        $this->namespaces = $this->element->getDocNamespaces();
    }

    public function title(): string
    {
        return trim((string) $this->element->title);
    }

    public function wordpress()
    {
        return $this->element->children($this->namespaces['wp']);
    }

    public function publishedAt()
    {
        return Carbon::createFromFormat('Y-m-d H:i:s', $this->wordpress()->post_date_gmt, new DateTimeZone('GMT'));
    }

    public static function from(\SimpleXMLElement $element)
    {
        return new WordPressEntity($element);
    }

    /**
     * If there's no excerpt defined, check if there is
     * a <!--more--> used in the content.
     */
    public function excerpt(): string
    {
        $excerpt = trim($this->rawExcerpt());

        if ($excerpt === '' && Str::contains($this->rawContent(), self::$MORE)) {
            $excerpt = Str::before($this->rawContent(), self::$MORE);
        }

        return $excerpt;
    }

    public function rawExcerpt(): string
    {
        return (string) $this->element->children($this->namespaces['excerpt'])->encoded;
    }

    public function content(): string
    {
        return trim(Str::replaceFirst('<!--more-->', '', $this->rawContent()));
    }

    protected function rawContent(): string
    {
        return (string) $this->element->children($this->namespaces['content'])->encoded;
    }
}
