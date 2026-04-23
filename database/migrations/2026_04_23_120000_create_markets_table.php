<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('markets', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('symbol', 20)->unique();
            $table->decimal('open', 20, 6)->nullable();
            $table->decimal('high', 20, 6)->nullable();
            $table->decimal('low', 20, 6)->nullable();
            $table->decimal('close', 20, 6)->nullable();
            $table->bigInteger('volume')->nullable();
            $table->date('date');
            $table->string('exchange', 50)->nullable();
            $table->string('currency', 10)->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('markets');
    }
};
