<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Contracts\Filesystem\Filesystem;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Storage;
use Orchestra\Parser\Xml\Facade;
use Orchestra\Parser\Xml\Reader;
use Orchestra\Parser\Xml\Facade as XmlParser;

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
     * @return mixed
     */
    public function handle()
    {
        $this->file = $this->choice('What file would you like to import?', $this->files());
    }

    public function files()
    {
        return Storage::files('imports');
    }
}
