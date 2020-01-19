<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\Storage;

trait FakeXmlStorage
{
    /**
     * Fake the storage driver, then place
     * the XML import file into storage.
     */
    protected function storeXmlStubToFakeStorage(): void
    {
        Storage::persistentFake();

        Storage::put('imports/wordpress-import.xml', file_get_contents($this->xmlStubPath()));
    }

    protected function xml() : \SimpleXMLElement
    {
        return simplexml_load_file($this->xmlStubPath());
    }

    protected function xmlStubPath(): string
    {
        return base_path('tests/Stubs/wordpress-import.xml');
    }
}
