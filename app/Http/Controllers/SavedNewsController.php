<?php

namespace App\Http\Controllers;

use App\Models\News;
use App\Models\SavedNews;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SavedNewsController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $saved = $request->user()->savedNews()
            ->with('news')
            ->latest()
            ->get()
            ->pluck('news');

        return response()->json($saved);
    }

    public function store(Request $request, News $news): JsonResponse
    {
        $request->user()->savedNews()->firstOrCreate([
            'news_id' => $news->id,
        ]);

        activity()
            ->causedBy($request->user())
            ->performedOn($news)
            ->withProperties(['news_id' => $news->id, 'title' => $news->title])
            ->log('Saved article');

        return response()->json(['message' => 'Saved']);
    }

    public function destroy(Request $request, News $news): JsonResponse
    {
        $request->user()->savedNews()->where('news_id', $news->id)->delete();

        activity()
            ->causedBy($request->user())
            ->performedOn($news)
            ->withProperties(['news_id' => $news->id, 'title' => $news->title])
            ->log('Unsaved article');

        return response()->json(['message' => 'Unsaved']);
    }
}
