<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create(config('redirect404.tables.redirects', 'redirects'), function (Blueprint $table) {
            $table->id();
            $table->string('from_url')->unique()->comment('Source path to match (e.g. /old-page)');
            $table->string('to_url')->comment('Destination URL or path');
            $table->unsignedSmallInteger('redirect_code')->default(301)->comment('HTTP redirect status code: 301 or 302');
            $table->unsignedBigInteger('hits')->default(0)->comment('Number of times this redirect was triggered');
            $table->boolean('is_active')->default(true)->comment('Whether this rule is currently active');
            $table->string('note')->nullable()->comment('Optional note for administrators');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists(config('redirect404.tables.redirects', 'redirects'));
    }
};
