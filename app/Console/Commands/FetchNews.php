<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;
use App\Models\News;
use App\Enums\NewsCategory;

class FetchNews extends Command
{
    protected $signature = 'app:fetch-news';
    protected $description = 'Fetch latest news from Finnhub API';

    public function handle()
    {
        $response = Http::get('https://finnhub.io/api/v1/news', [
            'category' => NewsCategory::GENERAL->value,
            'token' => config('services.finnhub.key'),
        ]);

        //  API call failed
        if ($response->failed()) {
            $this->error('Failed to fetch news from API');
            return;
        }

        $newsList = $response->json();

        foreach ($newsList as $item) {

            // Enum handling
            $category = NewsCategory::tryFrom($item['category']) 
                ?? NewsCategory::GENERAL;

            News::updateOrCreate(
                ['url' => $item['url']],
                [
                    'title' => $item['headline'],
                    'description' => $item['summary'],
                    'image' => $item['image'] ?? null,
                    'source' => $item['source'],
                    'category' => $category,
                    'published_at' => now()->setTimestamp($item['datetime']),
                ]
            );
        }

        $this->info('News imported successfully!');
    }
}