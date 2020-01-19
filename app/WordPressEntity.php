<?php

namespace App;

class WordPressEntity
{
    /**
     * @var \SimpleXMLElement
     */
    public $element;

    public function __construct(\SimpleXMLElement $element)
    {

        $this->element = $element;
    }

    public static function from(\SimpleXMLElement $element)
    {
        return new WordPressEntity($element);
    }
}
