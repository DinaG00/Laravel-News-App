<?php

namespace App\Http\Controllers;

use App\Models\News;
use Illuminate\Http\Request;
use App\Enums\NewsCategory;

class NewsController extends Controller
{
    public function index(Request $request)
    {
        return view('news');
    }

    public function apiIndex(Request $request)
    {
        $news = $this->applyFilters(News::query(), $request)
            ->orderBy('published_at', 'desc')
            ->get();

        $categories = News::query()
            ->select('category')
            ->distinct()
            ->orderBy('category')
            ->pluck('category');

        return response()->json([
            'data' => $news,
            'categories' => $categories,
        ]);
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