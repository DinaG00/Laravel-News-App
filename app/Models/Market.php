<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Market extends Model
{
    protected $fillable = [
        'name',
        'symbol',
        'open',
        'high',
        'low',
        'close',
        'volume',
        'date',
        'exchange',
        'currency',
    ];

    protected $casts = [
        'open' => 'decimal:6',
        'high' => 'decimal:6',
        'low' => 'decimal:6',
        'close' => 'decimal:6',
        'volume' => 'integer',
        'date' => 'date',
    ];
}
