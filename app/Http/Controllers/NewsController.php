<?php

namespace App\Http\Controllers;

use App\Models\News;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Cache;
use App\Enums\NewsCategory;
use OpenAI\Laravel\Facades\OpenAI;

class NewsController extends Controller
{
    public function index(Request $request)
    {
        return view('news');
    }

    public function apiIndex(Request $request)
    {
        $query = $this->applyFilters(News::query(), $request)
            ->orderBy('published_at', 'desc');

        $news = $query->paginate(4);

        $categories = News::query()
            ->select('category')
            ->distinct()
            ->orderBy('category')
            ->pluck('category');

        return response()->json([
            'data' => $news->items(),
            'categories' => $categories,
            'pagination' => [
                'current_page' => $news->currentPage(),
                'last_page' => $news->lastPage(),
                'per_page' => $news->perPage(),
                'total' => $news->total(),
            ],
        ]);
    }

    public function summarize(Request $request, News $news): JsonResponse
    {
        $cacheKey = 'news_summary_' . $news->id;

        $cached = Cache::get($cacheKey);
        if ($cached) {
            return response()->json(['summary' => $cached]);
        }

        $content = trim($news->title . "\n\n" . ($news->description ?? ''));

        if (empty($content)) {
            return response()->json(['error' => 'Article content is too short to summarize.'], 422);
        }

        $prompt = "Summarize the following news article in 2-3 concise bullet points. Keep the tone professional and factual.\n\nArticle:\n" . $content;

        try {
            $result = OpenAI::chat()->create([
                'model' => 'gpt-4o-mini',
                'messages' => [
                    ['role' => 'system', 'content' => 'You are a concise news summarizer.'],
                    ['role' => 'user', 'content' => $prompt],
                ],
                'max_tokens' => 300,
                'temperature' => 0.5,
            ]);

            $summary = trim($result->choices[0]->message->content ?? '');
        } catch (\Throwable $e) {
            report($e);
            return response()->json(['error' => 'AI summary service is temporarily unavailable.'], 503);
        }

        if (empty($summary)) {
            return response()->json(['error' => 'Could not generate summary.'], 503);
        }

        Cache::put($cacheKey, $summary, now()->addDay());

        return response()->json(['summary' => $summary]);
    }

    private function applyFilters($query, Request $request)
    {
        if ($request->filled('category')) {
            $category = NewsCategory::tryFrom($request->category);

            if ($category) {
                $query->where('category', $category);
            }
        }

        if ($request->filled('search')) {
            $query->where('title', 'like', '%' . $request->search . '%');
        }

        return $query;
    }
}
