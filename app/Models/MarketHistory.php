<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MarketHistory extends Model
{
    protected $fillable = [
        'symbol',
        'date',
        'open',
        'high',
        'low',
        'close',
        'volume',
    ];

    protected $casts = [
        'date' => 'date',
        'open' => 'decimal:6',
        'high' => 'decimal:6',
        'low' => 'decimal:6',
        'close' => 'decimal:6',
        'volume' => 'integer',
    ];
}
