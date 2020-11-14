<?php

namespace Tests\Feature;

use App\WordPressPost;
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
        $entity = WordPressPost::from($this->item());

        $this->assertEquals(\SimpleXMLElement::class, get_class($entity->element));
    }

    /**
     * @test
     */
    public function it_has_a_title()
    {
        $entity = WordPressPost::from($this->item());

        $this->assertEquals('Introduction to jQuery', $entity->title());
    }

    /**
     * @test
     */
    public function it_will_trim_whitespace_from_a_title()
    {
        $xml = simplexml_load_string('<item><title> Hello World </title></item>');

        $entity = WordPressPost::from($xml);

        $this->assertEquals('Hello World', $entity->title());
    }

    /**
     * @test
     */
    public function it_has_a_published_at_date()
    {
        $entity = WordPressPost::from($this->item());

        //  2010-04-14 15:12:59
        $date = Carbon::create(2010, 4, 14, 15, 12, 59);

        $this->assertEquals($date, $entity->publishedAt());
    }

    /**
     * @test
     */
    public function it_has_post_content()
    {
        $entity = WordPressPost::from($this->item());

        $this->assertStringContainsString('<a href="http://jquery.com">jQuery</a>', $entity->content());
    }

    /**
     * @test
     */
    public function it_has_post_excerpt()
    {
        $entity = WordPressPost::from($this->item());

        $this->assertStringContainsString('<a href="http://jquery.com">jQuery</a>', $entity->excerpt());
    }

    /**
     * @test
     */
    public function it_has_a_creator()
    {
        $entity = WordPressPost::from($this->item());

        $this->assertEquals('sagalbot', $entity->creator());
    }

    /**
     * @test
     */
    public function it_has_a_link()
    {
        $entity = WordPressPost::from($this->item());

        $this->assertEquals('http://code.sagalbot.com/jquery-2/introduction-to-jquery/', $entity->url());
    }

    /**
     * @test
     */
    public function it_has_wordpress_data()
    {
        $entity = WordPressPost::from($this->item());

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
        $entity = WordPressPost::from($this->item());

        $this->assertEquals(collect([
            '_edit_last'                 => '1',
            '_syntaxhighlighter_encoded' => '1',
            '_yoast_wpseo_linkdex'       => '0',
        ]), $entity->meta());
    }

    /**
     * @test
     */
    public function it_has_comments()
    {
        $entity = WordPressPost::from($this->item());

        $this->assertCount(3, $entity->comments());
        $this->assertEquals(collect([
            'comment_id'           => '4',
            'comment_author'       => 'Jeff',
            'comment_author_email' => 'sagalbot@gmail.com',
            'comment_author_url'   => 'http://www.sagalbot.com',
            'comment_author_IP'    => '142.110.23.222',
            'comment_date'         => '2010-04-16 14:15:48',
            'comment_date_gmt'     => '2010-04-16 20:15:48',
            'comment_content'      => 'Thanks for the suggestions. I\'ve made some changes to the entry.',
            'comment_approved'     => '1',
            'comment_type'         => '',
            'comment_parent'       => '3',
            'comment_user_id'      => '1',
        ]), $entity->comments()[1]);
    }

    /**
     * @test
     */
    public function it_has_an_array_representation()
    {
        $entity = WordPressPost::from($this->item());

        $this->assertEquals([
            'title'     => 'Introduction to jQuery',
            'author'    => 'sagalbot',
            'excerpt'   => $entity->excerpt(),
            'content'   => $entity->markdown(),
            'wordpress' => [
                'meta' => $entity->meta(),
                'data' => $entity->data(),
            ],
        ], $entity->toArray());
    }

    /**
     * @test
     */
    public function it_has_a_markdown_version_of_the_content()
    {
        $entity = WordPressPost::from($this->item());

        $this->assertStringContainsString('[Firefox](http://www.mozilla.com/en-US/firefox/upgrade.html)', $entity->markdown());
    }

    /**
     * @test
     */
    public function it_has_a_slug()
    {
        $entity = WordPressPost::from($this->item());

        $this->assertEquals('introduction-to-jquery', $entity->slug());
    }

    /**
     * @test
     */
     public function it_has_a_status()
     {
         $entity = WordPressPost::from($this->item());

         $this->assertEquals('publish', $entity->status());
     }

    /**
     * @test
     */
     public function it_has_a_published_status()
     {
         $entity = WordPressPost::from($this->item());

         $this->assertTrue($entity->isPublished());
     }
}
