<?php

namespace App\Console\Commands;

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

        $posts->where('post_type', '=', 'post')->each(function ($post) {
            //$this->createArticle($post);
        });
    }

    protected function createArticle($post)
    {
        /** @var \Statamic\Entries\Entry $entry */
        $entry = $this->entries->make();

        //  Sun, 11 Apr 2010 00:54:00 +0000
        //  2015-12-09 20:32:51
        //  2014-02-16 23:51:42
        $date = Carbon::createFromFormat('Y-m-d H:i:s', $post->post_date_gmt, new DateTimeZone('GMT'))
                      ->format('Y-m-d-Hi');

        $entry->date($date);

        $entry->collection('articles');
        $entry->slug(Str::slug($post->title));
        $entry->locale('default');

        $entry->set('title', $post->title);
        $entry->set('pubDate', $post->pubDate);
        $entry->set('post_id', $post->post_id);
        $entry->set('post_date', $post->post_date);
        $entry->set('post_parent', $post->post_parent);
        $entry->set('post_password', $post->post_password);

        $entry->set('content', $this->converter->convert($post->content));
        $entry->set('excerpt', $this->converter->convert($post->excerpt));

        $entry->published($post->status === 'draft' ? false : true);

        $entry->save();
    }

    /**
     * @return \Illuminate\Support\Collection|\Tightenco\Collect\Support\Collection
     */
    protected function parseXML()
    {
        $xml = simplexml_load_file('/Users/sagalbot/Sites/sagalbot-next-gen/storage/app/imports/sagalbot.com.2020-01-18.xml');

        $namespaces = $xml->getDocNamespaces();

        if (! isset($namespaces['wp'])) {
            $namespaces['wp'] = 'http://wordpress.org/export/1.1/';
        }

        if (! isset($namespaces['excerpt'])) {
            $namespaces['excerpt'] = 'http://wordpress.org/export/1.1/excerpt/';
        }

        return collect($xml->xpath('channel/item'))->map(function (SimpleXMLElement $item) use (
            $namespaces
        ) {
            $post = [
                'title'   => trim((string) $item->title),
                'pubDate' => trim((string) $item->pubDate),
            ];

            $dc = $item->children('http://purl.org/dc/elements/1.1/');
            $post['author'] = (string) $dc->creator;

            $content = $item->children('http://purl.org/rss/1.0/modules/content/');
            $excerpt = $item->children($namespaces['excerpt']);

            $post['content'] = trim((string) $content->encoded);
            $post['excerpt'] = trim((string) $excerpt->encoded);

            if ($post['excerpt'] === "" && Str::contains($post['content'], '<!--more-->')) {
                $post['excerpt'] = Str::before((string) $content->encoded, '<!--more-->');
                $post['content'] = Str::replaceFirst('<!--more-->', '', (string) $content->encoded);
            }

            collect($item->children($namespaces['wp']))->each(function ($value, $key) use (&$post) {
                if ((string) $key === 'postmeta') {
                    $post['postmeta'][] = [
                        'key'   => (string) $value->meta_key,
                        'value' => (string) $value->meta_value,
                    ];
                } elseif ((string) $key === 'postmeta') {
                    $meta = [];

                    if (isset($value->commentmeta)) {
                        foreach ($value->commentmeta as $m) {
                            $meta[] = [
                                'key'   => (string) $m->meta_key,
                                'value' => (string) $m->meta_value,
                            ];
                        }
                    }

                    $post['comments'][] = [
                        'comment_id'           => (int) $value->comment_id,
                        'comment_author'       => (string) $value->comment_author,
                        'comment_author_email' => (string) $value->comment_author_email,
                        'comment_author_IP'    => (string) $value->comment_author_IP,
                        'comment_author_url'   => (string) $value->comment_author_url,
                        'comment_date'         => (string) $value->comment_date,
                        'comment_date_gmt'     => (string) $value->comment_date_gmt,
                        'comment_content'      => (string) $value->comment_content,
                        'comment_markdown'     => $this->converter->convert((string) $value->comment_content),
                        'comment_approved'     => (string) $value->comment_approved,
                        'comment_type'         => (string) $value->comment_type,
                        'comment_parent'       => (string) $value->comment_parent,
                        'comment_user_id'      => (int) $value->comment_user_id,
                        'commentmeta'          => $meta,
                    ];
                } else {
                    $post[(string) $key] = trim((string) $value);
                }
            });

            in_array($post['post_type'], $this->postTypes->toArray()) ?: $this->postTypes->push($post['post_type']);

            foreach ($item->category as $c) {
                $att = $c->attributes();

                if (isset($att['nicename'])) {
                    $post[(string) $att['domain']][] = [
                        'title' => trim((string) $c),
                        'slug'  => trim((string) $att['nicename']),
                    ];
                }
            }

            return (object) $post;
        })->sortBy(function ($post) {
            return $post->post_date;
        });
    }

    public function files()
    {
        return Storage::files('imports');
    }
}
