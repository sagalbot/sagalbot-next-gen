<?php

namespace Tests\Feature;

use App\WordPressEntity;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;

class WordPressEntityTest extends TestCase
{
    use FakeXmlStorage;

    /**
     * @test
     */
     public function it_accepts_a_simple_xml_element()
     {
        $entity = WordPressEntity::from($this->xml());

        $this->assertEquals(\SimpleXMLElement::class, get_class($entity->element));
     }
}
