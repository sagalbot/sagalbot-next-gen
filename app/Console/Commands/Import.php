<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Laravie\Parser\Xml\Reader;
use Laravie\Parser\Xml\Document;
use Illuminate\Support\Facades\Storage;
use Illuminate\Contracts\Filesystem\Filesystem;

use League\HTMLToMarkdown\HtmlConverter;
use SimpleXMLElement;

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
     * @var \Illuminate\Contracts\Filesystem\Filesystem
     */
    private $storage;

    /**
     * @var \League\HTMLToMarkdown\HtmlConverter
     */
    private $converter;

    /**
     * Create a new command instance.
     *
     * @param \Illuminate\Contracts\Filesystem\Filesystem $storage
     */
    public function __construct(Filesystem $storage)
    {
        parent::__construct();

        $this->storage = $storage;
    }

    /**
     * Execute the console command.
     *
     * @param \League\HTMLToMarkdown\HtmlConverter $converter
     * @return mixed
     */
    public function handle(HtmlConverter $converter)
    {
        $this->converter = $converter;

        //$this->file = $this->choice('What file would you like to import?', $this->files());

        $posts = $this->importXML();

        dd($posts);
    }

    protected function importXML()
    {
        $xml = simplexml_load_file('/Users/sagalbot/Sites/sagalbot-next-gen/storage/app/imports/code.sagalbot.com.2020-01-18.xml');

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
                'title' => (string) $item->title,
            ];

            $dc = $item->children('http://purl.org/dc/elements/1.1/');
            $post['author'] = (string) $dc->creator;

            $content = $item->children('http://purl.org/rss/1.0/modules/content/');
            $excerpt = $item->children($namespaces['excerpt']);

            $post['content'] = (string) $content->encoded;
            $post['excerpt'] = (string) $excerpt->encoded;
            $post['markdown'] = $this->converter->convert((string) $content->encoded);

            $wp = $item->children($namespaces['wp']);
            $post['post_id'] = (int) $wp->post_id;
            $post['post_parent'] = (int) $wp->post_parent;
            $post['post_date_gmt'] = (string) $wp->post_date_gmt;
            $post['post_name'] = trim((string) $wp->post_name);

            if (isset($wp->attachment_url)) {
                $post['attachment_url'] = (string) $wp->attachment_url;
            }

            foreach ($item->category as $c) {
                $att = $c->attributes();

                if (isset($att['nicename'])) {
                    $post[(string) $att['domain']][] = [
                        'title' => trim((string) $c),
                        'slug'  => trim((string) $att['nicename']),
                    ];
                }
            }

            foreach ($wp->postmeta as $meta) {
                $post['postmeta'][] = [
                    'key'   => (string) $meta->meta_key,
                    'value' => (string) $meta->meta_value,
                ];
            }

            foreach ($wp->comment as $comment) {
                $meta = [];
                if (isset($comment->commentmeta)) {
                    foreach ($comment->commentmeta as $m) {
                        $meta[] = [
                            'key'   => (string) $m->meta_key,
                            'value' => (string) $m->meta_value,
                        ];
                    }
                }

                $post['comments'][] = [
                    'comment_id'           => (int) $comment->comment_id,
                    'comment_author'       => (string) $comment->comment_author,
                    'comment_author_email' => (string) $comment->comment_author_email,
                    'comment_author_IP'    => (string) $comment->comment_author_IP,
                    'comment_author_url'   => (string) $comment->comment_author_url,
                    'comment_date'         => (string) $comment->comment_date,
                    'comment_date_gmt'     => (string) $comment->comment_date_gmt,
                    'comment_content'      => (string) $comment->comment_content,
                    'comment_markdown'     => $this->converter->convert((string) $comment->comment_content),
                    'comment_approved'     => (string) $comment->comment_approved,
                    'comment_type'         => (string) $comment->comment_type,
                    'comment_parent'       => (string) $comment->comment_parent,
                    'comment_user_id'      => (int) $comment->comment_user_id,
                    'commentmeta'          => $meta,
                ];
            }

            return (object) $post;
        });
    }

    public function files()
    {
        return Storage::files('imports');
    }
}
