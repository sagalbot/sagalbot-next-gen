<?php

namespace App;

use Carbon\Carbon;
use DateTimeZone;
use Illuminate\Support\Str;

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

    public function excerpt(): string
    {
        $excerpt = $this->element->children($this->namespaces['excerpt']);
    }

    public function content(): string
    {
        $content = $this->element->children('http://purl.org/rss/1.0/modules/content/');

        return trim((string) Str::replaceFirst('<!--more-->', '', (string) $content->encoded));
    }
}
