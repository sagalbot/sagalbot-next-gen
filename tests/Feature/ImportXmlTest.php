<?php

namespace Tests\Feature;

use Illuminate\Http\FileHelpers;
use Illuminate\Http\Testing\File;
use Tests\TestCase;
use App\Console\Commands\Import;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Foundation\Testing\RefreshDatabase;

class ImportXmlTest extends TestCase
{
    use FakeXmlStorage;

    /**
     * @var Import
     */
    protected $import;

    public function setUp(): void
    {
        parent::setUp();

        $this->import = resolve(Import::class);

        $this->storeXmlStubToFakeStorage();
    }

    /**
     * @test
     */
    public function it_can_load_a_list_of_files_from_storage()
    {
        $files = $this->import->files();

        $this->assertContains('imports/wordpress-import.xml', $files);
    }

    /**
     * @test
     */
    public function it_will_ask_what_file_you_want_to_import()
    {
        $this->artisan('wordpress:import')
             ->expectsQuestion('What file would you like to import?', 'imports/wordpress-import.xml');
    }
}
