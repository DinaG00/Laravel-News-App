<?php

use App\Models\News;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;

uses(RefreshDatabase::class);

it('filters news by category and search and keeps newest first', function () {
    News::query()->create([
        'title' => 'AI market rally today',
        'description' => 'Business growth story',
        'url' => 'https://example.com/ai-rally',
        'image' => null,
        'source' => 'Example',
        'category' => 'business',
        'published_at' => Carbon::parse('2026-04-22 10:00:00'),
    ]);

    News::query()->create([
        'title' => 'AI regulation update',
        'description' => 'Policy and macro impact',
        'url' => 'https://example.com/ai-regulation',
        'image' => null,
        'source' => 'Example',
        'category' => 'business',
        'published_at' => Carbon::parse('2026-04-22 12:00:00'),
    ]);

    News::query()->create([
        'title' => 'Sports weekly round-up',
        'description' => 'Not a business article',
        'url' => 'https://example.com/sports',
        'image' => null,
        'source' => 'Example',
        'category' => 'general',
        'published_at' => Carbon::parse('2026-04-22 09:00:00'),
    ]);

    $response = $this->getJson('/api/news?category=business&search=AI');

    $response->assertOk();
    $response->assertJsonCount(2, 'data');
    $response->assertJsonPath('data.0.title', 'AI regulation update');
    $response->assertJsonPath('data.1.title', 'AI market rally today');
    $response->assertJsonMissingPath('data.2');
});
