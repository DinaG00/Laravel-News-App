<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ExchangeRate extends Model
{
    use HasFactory;

    protected $fillable = [
        'type', 'base_currency', 'target_currency', 'rate', 'rate_date',
    ];

    protected $casts = [
        'rate' => 'decimal:10',
        'rate_date' => 'date',
    ];
}
