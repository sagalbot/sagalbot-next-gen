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

    /**
     * @test
     */
    public function it_has_a_creator()
    {
        $entity = WordPressEntity::from($this->item());

        $this->assertEquals('sagalbot', $entity->creator());
    }

    /**
     * @test
     */
    public function it_has_a_link()
    {
        $entity = WordPressEntity::from($this->item());

        $this->assertEquals('http://code.sagalbot.com/jquery-2/introduction-to-jquery/', $entity->url());
    }

    /**
     * @test
     */
    public function it_has_wordpress_data()
    {
        $entity = WordPressEntity::from($this->item());

        $this->assertEquals(collect([
            'post_id'        => 107,
            'post_date'      => Carbon::create(2010, 4, 14, 9, 12, 59),
            'post_date_gmt'  => Carbon::create(2010, 4, 14, 15, 12, 59),
            'comment_status' => "open",
            'ping_status'    => "open",
            'post_name'      => "introduction-to-jquery",
            'status'         => "publish",
            'post_parent'    => 0,
            'menu_order'     => 0,
            'post_type'      => "post",
            'post_password'  => "",
            'is_sticky'      => false,
        ]), $entity->data());
    }

    /**
     * @test
     */
    public function it_has_wordpress_post_meta()
    {
        $entity = WordPressEntity::from($this->item());

        $this->assertEquals(collect([
            '_edit_last'                 => '1',
            '_syntaxhighlighter_encoded' => '1',
            '_yoast_wpseo_linkdex'       => '0',
        ]), $entity->meta());
    }
}
