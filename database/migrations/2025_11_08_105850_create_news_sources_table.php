<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('news_sources', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('name', 255);
            $table->text('base_url');
            $table->string('source_type', 50)->nullable();
            $table->integer('crawl_interval_minutes')->default(60);
            $table->boolean('is_active')->default(true);
            $table->timestamp('last_crawled_at')->nullable();
            $table->timestamps();
        });

        // Add CHECK constraint for PostgreSQL
        DB::statement("ALTER TABLE news_sources ADD CONSTRAINT news_sources_source_type_check CHECK (source_type IN ('website', 'rss', 'api'))");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('news_sources');
    }
};
