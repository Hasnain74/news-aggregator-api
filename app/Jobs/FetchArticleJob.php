<?php

namespace App\Jobs;

use App\Models\Article;
use Carbon\Carbon;
use Illuminate\Bus\Queueable;
use Illuminate\Http\Client\Response;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Class FetchArticleJob
 * @package App\Jobs
 */
class FetchArticleJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected array $apis;

    public function __construct()
    {
        $this->apis = [
            [
                'name' => 'NewsAPI',
                'url' => 'https://newsapi.org/v2/top-headlines',
                'params' => [
                    'apiKey' => config('services.news_api.key'),
                    'country' => 'us',
                    'category' => 'technology',
                ],
            ],
            [
                'name' => 'BBC News',
                'url' => 'https://newsapi.org/v2/top-headlines',
                'params' => [
                    'apiKey' => config('services.news_api.key'),
                    'sources' => 'bbc-news',
                ],
            ],
            [
                'name' => 'New York Times',
                'url' => 'https://api.nytimes.com/svc/topstories/v2/technology.json',
                'params' => [
                    'api-key' => config('services.ny_times.key'),
                ],
            ],
        ];
    }
    /**
     * Execute the job.
     */
    public function handle(): void
    {
        foreach ($this->apis as $api) {
            $this->fetchAndStoreArticles($api);
        }
    }

    /**
     * Fetch articles from the API and store them.
     *
     * @param array $api
     */
    private function fetchAndStoreArticles(array $api): void
    {
        $response = $this->makeApiRequest($api['url'], $api['params']);

        if (!$response) {
            return;
        }

        $articles = $this->extractArticles($response, $api['name']);

        foreach ($articles as $article) {
            $this->storeArticle($article, $api['name']);
        }

        Log::info("Successfully fetched and stored articles from {$api['name']}.");
    }

    /**
     * Make an HTTP GET request to the API.
     *
     * @param string $url
     * @param array $params
     * @return Response|null
     */
    private function makeApiRequest(string $url, array $params): ?Response
    {
        try {
            Log::info("Fetching articles from URL: {$url}");

            $response = Http::timeout(30)->retry(3, 1000)->get($url, $params);

            if ($response->successful()) {
                return $response;
            }

            Log::error("Failed to fetch articles from {$url}. Response: " . $response->body());
        } catch (\Exception $e) {
            Log::error("Error fetching articles: " . $e->getMessage());
        }

        return null;
    }

    /**
     * Extract articles from the API response.
     *
     * @param Response $response
     * @param string $source
     * @return array
     */
    private function extractArticles(Response $response, string $source): array
    {
        if ($source === 'New York Times') {
            return $response->json()['results'];
        }

        return $response->json()['articles'] ?? [];
    }

    /**
     * Store an article in the database or update if it already exists.
     *
     * @param array $article
     * @param string $source
     */
    private function storeArticle(array $article, string $source): void
    {
        $description = $article['description'] ?? 'No Description';
        $author = $article['author'] ?? 'Unknown Author';

        if ($source === 'New York Times') {
            $description = $article['abstract'] ?? 'No Description';
            $author = $article['byline'] ?? 'No Author';
        }

        $publishedAt = $this->getPublishedAt($article);
        $keywords = $this->extractKeywords($article);
        $category = $this->inferCategory($article);

        Article::updateOrCreate(
            ['url' => $article['url']],
            [
                'title' => $article['title'] ?? 'No Title',
                'description' => $description,
                'author' => $author,
                'source' => $source,
                'keywords' => implode(', ', $keywords),
                'category' => $category,
                'published_at' => $publishedAt,
            ]
        );
    }

    /**
     * Get the published date from the article or use the current timestamp.
     *
     * @param array $article
     * @return string
     */
    private function getPublishedAt(array $article): string
    {
        return isset($article['publishedAt'])
            ? Carbon::parse($article['publishedAt'])->toDateTimeString()
            : now()->toDateTimeString();
    }

    /**
     * Extract keywords from an article.
     *
     * @param array $article
     * @return array
     */
    private function extractKeywords(array $article): array
    {
        $text = strtolower($article['title'] . ' ' . ($article['description'] ?? '') . ' ' . ($article['content'] ?? ''));
        $stopWords = ['the', 'is', 'in', 'and', 'a', 'of', 'to', 'for', 'with', 'on', 'this'];
        $words = preg_split('/\s+/', $text);
        return array_values(array_diff($words, $stopWords));
    }

    /**
     * Infer the category based on keywords found in the article.
     *
     * @param array $article
     * @return string
     */
    private function inferCategory(array $article): string
    {
        $keywordsMap = [
            'Technology' => ['iphone', 'tech', 'software', 'technology', 'gadgets'],
            'Sports' => ['football', 'soccer', 'basketball', 'sports'],
            'Politics' => ['election', 'politics', 'government', 'president', 'senate'],
        ];

        $text = strtolower($article['title'] . ' ' . ($article['description'] ?? '') . ' ' . ($article['content'] ?? ''));

        foreach ($keywordsMap as $category => $keywords) {
            foreach ($keywords as $keyword) {
                if (str_contains($text, strtolower($keyword))) {
                    return $category;
                }
            }
        }

        return 'General';
    }
}
