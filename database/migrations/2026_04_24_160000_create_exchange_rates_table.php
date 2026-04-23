<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('exchange_rates', function (Blueprint $table) {
            $table->id();
            $table->string('type', 20);          // 'fiat' or 'crypto'
            $table->string('base_currency', 10);
            $table->string('target_currency', 10);
            $table->decimal('rate', 20, 10);
            $table->date('rate_date');
            $table->timestamps();

            $table->unique(['type', 'base_currency', 'target_currency', 'rate_date'], 'exr_unique_pair_date');
            $table->index(['type', 'base_currency']);
            $table->index(['rate_date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('exchange_rates');
    }
};
