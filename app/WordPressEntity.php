<?php

namespace App;

use Carbon\Carbon;
use DateTimeZone;

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

    public function __construct(\SimpleXMLElement $element)
    {
        $this->element = $element;
    }

    public function title(): string
    {
        return trim((string) $this->element->title);
    }

    public function wordpress()
    {
        return $this->element->children($this->element->getDocNamespaces()['wp']);
    }

    public function publishedAt()
    {
        return Carbon::createFromFormat('Y-m-d H:i:s', $this->wordpress()->post_date_gmt, new DateTimeZone('GMT'));
    }

    public static function from(\SimpleXMLElement $element)
    {
        return new WordPressEntity($element);
    }
}
