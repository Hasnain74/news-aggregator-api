<?php

namespace Tests\Unit;

use Tests\TestCase;
use App\Models\Article;
use Illuminate\Foundation\Testing\RefreshDatabase;

class SanctumRoutesTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @test
     */
    public function retrieve_articles_with_pagination(): void
    {
        Article::factory()->count(20)->create();
        $response = $this->getJson('/api/articles?per_page=10');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    '*' => [
                        'id',
                        'title',
                        'description',
                        'author',
                        'source',
                        'published_at',
                        'url',
                    ],
                ],
                'links' => [
                    'first',
                    'last',
                    'prev',
                    'next',
                ],
                'meta' => [
                    'current_page',
                    'from',
                    'last_page',
                    'path',
                    'per_page',
                    'to',
                    'total',
                ],
            ]);
    }

    /**
     * @test
     */
    public function retrieve_articles_without_authentication_is_allowed(): void
    {
        Article::factory()->count(5)->create();
        $response = $this->getJson('/api/articles?per_page=5');

        $response->assertStatus(200);
    }
}
