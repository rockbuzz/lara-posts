<?php

namespace Tests;

use Tests\Stubs\Author;
use Spatie\Sluggable\HasSlug;
use Rockbuzz\LaraUuid\Traits\Uuid;
use Rockbuzz\LaraPosts\Models\Post;
use Illuminate\Support\Facades\Config;
use Spatie\EloquentSortable\SortableTrait;
use Rockbuzz\LaraPosts\Enums\{Status, Type};
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PostTest extends TestCase
{
    /**
     * @var Post
     */
    private $post;

    public function setUp(): void
    {
        parent::setUp();

        $this->withFactories(__DIR__ . '/database/factories');

        $this->post = new Post();
    }

    public function testPostFillable()
    {
        $expected = [
            'title',
            'slug',
            'description',
            'body',
            'view',
            'status',
            'type', 
            'metadata', 
            'order_column',
            'author_id',
            'published_at'
        ];

        $this->assertEquals($expected, $this->post->getFillable());
    }

    public function testIfUsesTraits()
    {
        $this->assertEquals(
            [
                Uuid::class,
                HasSlug::class,
                SortableTrait::class,
                SoftDeletes::class
            ],
            array_values(class_uses(Post::class))
        );
    }

    public function testCasts()
    {
        $this->assertEquals([
            'id' => 'string',
            'metadata' => 'array',
            'status' => 'int',
            'type' => 'int'
        ], $this->post->getCasts());
    }

    public function testDates()
    {
        $this->assertEquals(
            array_values([
                'published_at', 
                'deleted_at',
                'created_at',
                'updated_at', 
            ]),
            array_values($this->post->getDates())
        );
    }

    public function testAPostShouldHaveASlugAttribute()
    {
        $post = factory(Post::class)->create(['title' => 'title test x', 'slug' => null]);

        $this->assertEquals('title-test-x', $post->slug);
    }

    public function testAPostHasAuthor()
    {
        $post = factory(Post::class)->create();

        Config::set('posts.models.author', Author::class);

        $this->assertInstanceOf(BelongsTo::class, $post->author());
    }

    public function testItShouldBeIsDraft()
    {
        $post = factory(Post::class)->create(['status' => Status::DRAFT]);

        $this->assertTrue($post->isDraft());
    }

    public function testItShouldBeIsSalved()
    {
        $post = factory(Post::class)->create(['status' => Status::MODERATE]);

        $this->assertTrue($post->isModerate());
    }

    public function testItShouldBeIsPublished()
    {
        $post = factory(Post::class)->create(['status' => Status::PUBLISHED]);

        $this->assertTrue($post->isPublished());
    }

    public function testItShouldHaveTwoItems()
    {
        factory(Post::class, 2)->create(['status' => Status::DRAFT]);
        factory(Post::class, 3)->create(['status' => Status::MODERATE]);
        factory(Post::class, 4)->create(['status' => Status::PUBLISHED]);

        $this->assertEquals(2, Post::drafts()->count());
    }

    public function testItShouldHaveTreeItems()
    {
        factory(Post::class, 2)->create(['status' => Status::DRAFT]);
        factory(Post::class, 3)->create(['status' => Status::MODERATE]);
        factory(Post::class, 4)->create(['status' => Status::PUBLISHED]);

        $this->assertEquals(3, Post::moderate()->count());
    }

    public function testItShouldHaveFourItems()
    {
        factory(Post::class, 2)->create(['status' => Status::DRAFT]);
        factory(Post::class, 3)->create(['status' => Status::MODERATE]);
        factory(Post::class, 4)->create(['status' => Status::PUBLISHED]);

        $this->assertEquals(4, Post::published()->count());
    }

    public function testItShouldBeIsArticle()
    {
        $post = factory(Post::class)->create(['type' => Type::ARTICLE]);

        $this->assertTrue($post->isArticle());
    }

    public function testItShouldBeIsPodcast()
    {
        $post = factory(Post::class)->create(['type' => Type::PODCAST]);

        $this->assertTrue($post->isPodcast());
    }

    public function testItShouldBeIsVideo()
    {
        $post = factory(Post::class)->create(['type' => Type::VIDEO]);

        $this->assertTrue($post->isVideo());
    }

    public function testItShouldHaveTwoItemsArticles()
    {
        factory(Post::class, 2)->create(['type' => Type::ARTICLE]);
        factory(Post::class, 3)->create(['type' => Type::PODCAST]);
        factory(Post::class, 4)->create(['type' => Type::VIDEO]);

        $this->assertEquals(2, Post::articles()->count());
    }

    public function testItShouldHaveTreeItemsPodcasts()
    {
        factory(Post::class, 2)->create(['type' => Type::ARTICLE]);
        factory(Post::class, 3)->create(['type' => Type::PODCAST]);
        factory(Post::class, 4)->create(['type' => Type::VIDEO]);

        $this->assertEquals(3, Post::podcasts()->count());
    }

    public function testItShouldHaveFourItemsVideos()
    {
        factory(Post::class, 2)->create(['type' => Type::ARTICLE]);
        factory(Post::class, 3)->create(['type' => Type::PODCAST]);
        factory(Post::class, 4)->create(['type' => Type::VIDEO]);

        $this->assertEquals(4, Post::videos()->count());
    }

    public function testItShouldHaveLatestPublishedItems()
    {
        $post1 = factory(Post::class)->create([
            'status' => Status::PUBLISHED,
            'published_at' => now()->subHours(1)
        ]);
        $post2 = factory(Post::class)->create([
            'status' => Status::PUBLISHED,
            'published_at' => now()->subHours(2)
        ]);
        $post3 = factory(Post::class)->create([
            'status' => Status::PUBLISHED,
            'published_at' => now()->subHours(3)
        ]);
        factory(Post::class, 2)->create([
            'status' => Status::DRAFT,
            'published_at' => now()->subHours(1)
        ]);
        factory(Post::class, 3)->create([
            'status' => Status::MODERATE,
            'published_at' => now()->subHours(2)
        ]);

        $data = [
            $post3->title,
            $post2->title,
            $post1->title,
        ];

        $this->assertEquals($data, Post::latestPublished()->get()->pluck('title')->toArray());
    }
}
