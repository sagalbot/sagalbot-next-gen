<?php

namespace App;

use Carbon\Carbon;
use DateTimeZone;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use League\HTMLToMarkdown\HtmlConverter;
use phpDocumentor\Reflection\Types\Boolean;

class WordPressPost
{
    use ParsesWordPressDates;

    /**
     * @var \SimpleXMLElement
     */
    public $element;

    /**
     * @var array
     */
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

    public static function from(\SimpleXMLElement $element)
    {
        return new WordPressPost($element);
    }

    public function title(): string
    {
        return trim((string) $this->element->title);
    }

    public function wordpress(): \SimpleXMLElement
    {
        return $this->element->children($this->namespaces['wp']);
    }

    public function publishedAt(): Carbon
    {
        return $this->parseWordPressDate($this->wordpress()->post_date_gmt);
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

    public function markdown()
    {
        /** @var HtmlConverter $converter */
        $converter = resolve(HtmlConverter::class);

        return $converter->convert($this->content());
    }

    public function content(): string
    {
        return trim(Str::replaceFirst('<!--more-->', '', $this->rawContent()));
    }

    protected function rawContent(): string
    {
        return (string) $this->element->children($this->namespaces['content'])->encoded;
    }

    public function creator(): string
    {
        return (string) $this->element->children('http://purl.org/dc/elements/1.1/')->creator;
    }

    public function url(): string
    {
        return (string) $this->element->link;
    }

    public function data(): Collection
    {
        return collect($this->wordpress())->filter(function (\SimpleXMLElement $element) {
            return $element->count() === 0;
        })->map(function (\SimpleXMLElement $element) {
            //  TODO: this might be cleaner with a $casts property
            switch ($element->getName()) {
                case 'post_id':
                case 'post_parent':
                case 'menu_order':
                    return (int) $element;
                case 'post_date':
                case 'post_date_gmt':
                    return $this->parseWordPressDate($element->__toString());
                case 'is_sticky':
                    return (boolean) (string) $element;
                default:
                    return (string) $element;
            }
        });
    }

    public function meta(): Collection
    {
        $meta = [];

        foreach ($this->wordpress()->postmeta as $element) {
            $meta[(string) $element->meta_key] = (string) $element->meta_value;
        }

        return collect($meta);
    }

    public function comments(): Collection
    {
        $comments = [];

        foreach ($this->wordpress()->comment as $element) {
            $comments[] = collect($element)->map(function (\SimpleXMLElement $element) {
                return trim((string) $element);
            });
        }

        return collect($comments);
    }

    public function toArray(): array
    {
        return [
            'title'     => $this->title(),
            'author'    => $this->creator(),
            'excerpt'   => $this->excerpt(),
            'content'   => $this->markdown(),
            'wordpress' => [
                'meta' => $this->meta(),
                'data' => $this->data(),
            ],
        ];
    }

    public function slug(): string
    {
        return Str::slug($this->title());
    }

    public function status(): string
    {
        return $this->data()->get('status');
    }

    public function isPublished(): bool
    {
        return $this->status() === 'publish' ? true : false;
    }

    public function isType(string $type): bool
    {
        return $this->data()->get('post_type') === $type;
    }
}
