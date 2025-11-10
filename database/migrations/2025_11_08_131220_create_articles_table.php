<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('articles', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('campaign_id')->nullable();
            $table->unsignedBigInteger('source_id')->nullable();
            $table->unsignedBigInteger('crawl_job_id')->nullable();
            $table->string('title');
            $table->text('content')->nullable();
            $table->text('url');
            $table->text('author')->nullable();
            $table->timestamp('published_at')->nullable();
            $table->text('summary')->nullable();
            $table->json('metadata')->nullable(); // For storing additional data like tags, categories, etc.
            $table->timestamps();

            $table->foreign('campaign_id')->references('id')->on('campaigns')->onDelete('set null');
            $table->foreign('source_id')->references('id')->on('news_sources')->onDelete('set null');
            $table->foreign('crawl_job_id')->references('id')->on('crawl_jobs')->onDelete('set null');
            $table->index(['campaign_id', 'source_id']);
            $table->index('published_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('articles');
    }
};
