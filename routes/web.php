<?php

use App\Models\News;
use Illuminate\Http\Request;

Route::get('/', function (Request $request) {

    $query = News::query();

    // CATEGORY FILTER (ignore empty / "all")
    if ($request->filled('category') && $request->category !== '') {
        $query->where('category', $request->category);
    }

    // SEARCH FILTER
    if ($request->filled('search')) {
        $query->where('title', 'like', '%' . $request->search . '%');
    }

    $news = $query->orderBy('published_at', 'desc')->get();

    return view('news', compact('news'));
});