<?php

namespace Tests\Unit;

use Tests\TestCase;
use App\Models\Article;
use Illuminate\Foundation\Testing\RefreshDatabase;

class ArticleControllerTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function it_fetches_articles_with_pagination(): void
    {
        Article::factory()->count(5)->create();
        $response = $this->getJson('/api/articles?per_page=5');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    '*' => ['id', 'title', 'published_at', 'category', 'source'],
                ],
                'links' => ['first', 'last', 'prev', 'next'],
                'meta'  => ['current_page', 'from', 'last_page', 'path', 'per_page', 'to', 'total'],
            ]);
    }

    /** @test */
    public function it_fetches_preference_based_articles(): void
    {
        Article::factory()->create([
            'title'    => 'Tech Article',
            'category' => 'Technology',
            'source'   => 'Tech News',
            'author'   => 'John Doe',
        ]);
        Article::factory()->create([
            'title'    => 'Science Article',
            'category' => 'Science',
            'source'   => 'Science Daily',
            'author'   => 'John Doe',
        ]);
        Article::factory()->create([
            'title'    => 'Health Article',
            'category' => 'Health',
            'source'   => 'Health Times',
            'author'   => 'Jane Smith',
        ]);

        $query = http_build_query([
            'sources'    => 'Tech News,Science Daily',
            'categories' => 'Technology,Science',
            'authors'    => 'John Doe',
            'per_page'   => 5,
        ]);

        $response = $this->getJson("/api/preference-articles?{$query}");

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    '*' => ['id', 'title', 'published_at', 'category', 'source'],
                ],
            ])
            ->assertJsonCount(2, 'data');
    }

    /** @test */
    public function it_filters_articles_based_on_query_parameters(): void
    {
        Article::factory()->create([
            'title'    => 'Tech Article',
            'category' => 'Technology',
            'source'   => 'Tech News',
        ]);
        Article::factory()->create([
            'title'    => 'Science Article',
            'category' => 'Science',
            'source'   => 'Science Daily',
        ]);

        $response = $this->getJson('/api/filter-articles?category=Technology');
        $response->assertStatus(200)
            ->assertJsonCount(1, 'data')
            ->assertJsonFragment(['title' => 'Tech Article']);
        $response = $this->getJson('/api/filter-articles?source=Science Daily');
        $response->assertStatus(200)
            ->assertJsonCount(1, 'data')
            ->assertJsonFragment(['title' => 'Science Article']);
    }
}
