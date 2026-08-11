<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create(config('redirect404.tables.missing_urls', 'missing_urls'), function (Blueprint $table) {
            $table->id();
            $table->string('url')->unique()->comment('The 404 path that was requested');
            $table->string('referer')->nullable()->comment('HTTP Referer header — where the user came from');
            $table->string('user_agent')->nullable()->comment('Browser / bot user-agent string');
            $table->string('ip_address', 45)->nullable()->comment('IPv4 or IPv6 address of the requester');
            $table->unsignedBigInteger('hit_count')->default(1)->comment('How many times this URL returned 404');
            $table->timestamp('last_seen_at')->nullable()->comment('When this URL was last requested');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists(config('redirect404.tables.missing_urls', 'missing_urls'));
    }
};
