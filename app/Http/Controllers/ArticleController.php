<?php

namespace App\Http\Controllers;

use App\Http\Requests\ArticleFilterRequest;
use App\Http\Requests\ArticleIndexRequest;
use App\Models\Article;
use App\Repositories\ArticleRepository;
use Illuminate\Http\JsonResponse;

/**
 * Class ArticleController
 * @package App\Http\Controllers
 */
class ArticleController extends Controller
{
    /**
     * ArticleController constructor.
     * @param ArticleRepository $articleRepository
     */
    public function __construct(private readonly ArticleRepository $articleRepository)
    {
    }

    /**
     * Fetch articles with pagination.
     * @param ArticleIndexRequest $request
     * @return JsonResponse
     */
    public function index(ArticleIndexRequest $request): JsonResponse
    {
        $perPage = $request->get('per_page', 10);
        $articles = Article::orderBy('published_at', 'desc')->paginate($perPage);

        return $this->articleRepository->paginateResponse($articles);
    }

    /**
     * Fetch articles based on user preferences.
     * @param ArticleIndexRequest $request
     * @return JsonResponse
     */
    public function preferenceBasedArticles(ArticleIndexRequest $request): JsonResponse
    {
        $preferences = $request->all();
        $perPage = $request->get('per_page', 10);

        $articles = $this->articleRepository
            ->getArticlesByPreferences($preferences, $perPage);

        return $this->articleRepository->paginateResponse($articles);
    }

    /**
     * @param ArticleFilterRequest $request
     * @return JsonResponse
     */
    public function filterArticle(ArticleFilterRequest $request): JsonResponse
    {
        $articles = $this->articleRepository->filter($request->all());

        return response()->json($articles);
    }
}

