<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Enums\NewsCategory;

class News extends Model
{
    protected $fillable = [
        'title',
        'description',
        'url',
        'image',
        'source',
        'category',
        'published_at',
    ];

    protected $casts = [
        'category' => NewsCategory::class,
        'published_at' => 'datetime',
    ];
}