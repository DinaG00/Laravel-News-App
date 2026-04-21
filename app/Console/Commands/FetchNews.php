<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;
use App\Models\News;

class FetchNews extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:fetch-news';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';

    /**
     * Execute the console command.
     */
    public function handle()
{
    $response = Http::get('https://finnhub.io/api/v1/news', [
        'category' => 'general',
        'token' => env('FINNHUB_API_KEY'),
    ]);

    $newsList = $response->json();

    foreach ($newsList as $item) {

        News::updateOrCreate(
            ['url' => $item['url']],
            [
                'title' => $item['headline'],
                'description' => $item['summary'],
                'image' => $item['image'] ?? null,
                'source' => $item['source'],
                'category' => $item['category'],
                'published_at' => date('Y-m-d H:i:s', $item['datetime']),
            ]
        );
    }

    $this->info('News imported successfully!');
}
}
