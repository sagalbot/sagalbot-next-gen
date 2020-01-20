<?php

namespace Tests\Feature;

use App\WordPressEntity;
use Carbon\Carbon;
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
        $entity = WordPressEntity::from($this->item());

        $this->assertEquals(\SimpleXMLElement::class, get_class($entity->element));
    }

    /**
     * @test
     */
    public function it_has_a_title()
    {
        $entity = WordPressEntity::from($this->item());

        $this->assertEquals('Introduction to jQuery', $entity->title());
    }

    /**
     * @test
     */
    public function it_will_trim_whitespace_from_a_title()
    {
        $xml = simplexml_load_string('<item><title> Hello World </title></item>');

        $entity = WordPressEntity::from($xml);

        $this->assertEquals('Hello World', $entity->title());
    }

    /**
     * @test
     */
    public function it_has_a_published_at_date()
    {
        $entity = WordPressEntity::from($this->item());

        //  2010-04-14 15:12:59
        $date = Carbon::create(2010, 4, 14, 15, 12, 59);

        $this->assertEquals($date, $entity->publishedAt());
    }

    /**
     * @test
     */
    public function it_has_post_content()
    {
        $entity = WordPressEntity::from($this->item());

        $this->assertStringContainsString('<a href="http://jquery.com">jQuery</a>', $entity->content());
    }

    /**
     * @test
     */
     public function it_has_post_excerpt()
     {
         $entity = WordPressEntity::from($this->item());

         $this->assertStringContainsString('<a href="http://jquery.com">jQuery</a>', $entity->excerpt());
     }
}
