<?php

namespace App\Console\Commands;

use App\WordPressPost;
use Carbon\Carbon;
use DateTimeZone;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Str;
use Laravie\Parser\Xml\Reader;
use Laravie\Parser\Xml\Document;
use Illuminate\Support\Facades\Storage;
use Illuminate\Contracts\Filesystem\Filesystem;

use League\HTMLToMarkdown\HtmlConverter;
use SimpleXMLElement;
use Statamic\Contracts\Entries\EntryRepository;
use Statamic\Facades\Entry;

class Import extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'wordpress:import';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Import a WP XML file';

    /**
     * @var string
     */
    protected $file;

    /**
     * @var \Statamic\Contracts\Entries\EntryRepository
     */
    protected $entries;

    /**
     * @var \Illuminate\Contracts\Filesystem\Filesystem
     */
    private $storage;

    /**
     * @var \League\HTMLToMarkdown\HtmlConverter
     */
    private $converter;

    /**
     * @var \Illuminate\Support\Collection|\Tightenco\Collect\Support\Collection
     */
    private $postTypes;

    /**
     * Create a new command instance.
     *
     * @param \Illuminate\Contracts\Filesystem\Filesystem $storage
     */
    public function __construct(Filesystem $storage)
    {
        parent::__construct();

        $this->storage = $storage;
        $this->postTypes = collect();
    }

    /**
     * Execute the console command.
     *
     * @param \League\HTMLToMarkdown\HtmlConverter $converter
     * @param \Statamic\Contracts\Entries\EntryRepository $entries
     * @return mixed
     */
    public function handle(HtmlConverter $converter, EntryRepository $entries)
    {
        $this->converter = $converter;
        $this->entries = $entries;
        $this->file = $this->choice('What file would you like to import?', $this->files());

        $posts = $this->parseXML();

        $posts->filter(function (WordPressPost $post) {
            return $post->isType('post');
        })->each(function ($post) {
            $this->createArticle($post);
        });
    }

    protected function createArticle(WordPressPost $post)
    {
        /** @var \Statamic\Entries\Entry $entry */
        $entry = $this->entries->make();
        $entry->collection('articles');
        $entry->locale('default');

        //$entry->date($post->publishedAt());
        $entry->slug($post->slug());

        $entry->set('title', $post->title());
        $entry->set('content', $post->content());
        $entry->set('excerpt', $post->excerpt());

        $entry->published($post->isPublished());

        $this->line($post->title());

        $entry->save();
    }

    /**
     * @return \Illuminate\Support\Collection|\Tightenco\Collect\Support\Collection
     */
    protected function parseXML()
    {
        $xml = simplexml_load_file('/Users/sagalbot/Sites/sagalbot-next-gen/storage/app/imports/sagalbot.com.2020-01-18.xml');

        return collect($xml->xpath('channel/item'))->map(function (SimpleXMLElement $item) {
            return WordPressPost::from($item);
        });
    }

    public function files()
    {
        return Storage::files('imports');
    }
}
