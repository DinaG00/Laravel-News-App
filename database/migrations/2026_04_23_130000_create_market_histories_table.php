<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('market_histories', function (Blueprint $table) {
            $table->id();
            $table->string('symbol', 20)->index();
            $table->date('date');
            $table->decimal('open', 20, 6)->nullable();
            $table->decimal('high', 20, 6)->nullable();
            $table->decimal('low', 20, 6)->nullable();
            $table->decimal('close', 20, 6)->nullable();
            $table->bigInteger('volume')->nullable();
            $table->timestamps();

            $table->unique(['symbol', 'date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('market_histories');
    }
};
