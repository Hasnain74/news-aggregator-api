<?php
namespace App\Repositories;

use App\Models\Article;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Arr;

/**
 * Class ArticleRepository
 * @package App\Repositories
 */
class ArticleRepository
{
    /**
     * Paginate the articles.
     *
     * @param $articles
     * @return JsonResponse
     */
    public function paginateResponse($articles): JsonResponse
    {
        return response()->json([
            'data' => $articles->items(),
            'links' => [
                'first' => $articles->url(1),
                'last' => $articles->url($articles->lastPage()),
                'prev' => $articles->previousPageUrl(),
                'next' => $articles->nextPageUrl(),
            ],
            'meta' => [
                'current_page' => $articles->currentPage(),
                'from' => $articles->firstItem(),
                'last_page' => $articles->lastPage(),
                'path' => $articles->path(),
                'per_page' => $articles->perPage(),
                'to' => $articles->lastItem(),
                'total' => $articles->total(),
            ],
        ]);
    }

    /**
     * Fetch articles based on user preferences.
     *
     * @param array $preferences
     * @param int $perPage
     * @return LengthAwarePaginator
     */
    public function getArticlesByPreferences(array $preferences, int $perPage): LengthAwarePaginator
    {
        $query = Article::query();
        $this->applyPreference($query, 'source', $preferences['source']);
        $this->applyPreference($query, 'category', $preferences['category']);
        $this->applyPreference($query, 'author', $preferences['author']);

        return $query->orderByDesc('published_at')->paginate($perPage);
    }

    /**
     * Apply filter to the query based on the preference.
     *
     * @param Builder $query
     * @param string $column
     * @param mixed $value
     */
    private function applyPreference(Builder $query, string $column, mixed $value): void
    {
        if (empty($value)) {
            return;
        }

        $values = is_array($value) ? array_map('trim', $value) : array_map('trim', explode(',', $value));

        if (count($values) >= 1) {
            $query->where(function ($subQuery) use ($column, $values) {
                foreach ($values as $item) {
                    $subQuery->orWhere($column, 'like', '%' . $item . '%');
                }
            });
        }
    }

    /**
     * Filter articles based on the given data.
     *
     * @param array $data
     * @return LengthAwarePaginator
     */
    public function filter(array $data): LengthAwarePaginator
    {
        return Article::query()
            ->when(isset($data['keyword']), function ($q) use ($data) {
                return $q->where('keywords', 'like', "%{$data['keyword']}%");
            })
            ->when(isset($data['date']), function ($q) use ($data) {
                return $q->whereDate('published_at', $data['date']);
            })
            ->when(isset($data['category']), function ($q) use ($data) {
                return $q->where(function($q) use ($data) {
                    $q->where('category', 'like', "%{$data['category']}%")
                        ->orWhere('category', $data['category']);
                });
            })
            ->when(isset($data['source']), function ($q) use ($data) {
                return $q->where(function($q) use ($data) {
                    $q->where('source', 'like', "%{$data['source']}%")
                        ->orWhere('source', $data['source']);
                });
            })
            ->orderByDesc('published_at')
            ->paginate(Arr::get($data, 'per_page', 15));
    }
}
