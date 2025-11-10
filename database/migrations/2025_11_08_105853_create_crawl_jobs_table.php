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
        Schema::create('crawl_jobs', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('campaign_id')->nullable();
            $table->unsignedBigInteger('source_id')->nullable();
            $table->timestamp('started_at')->useCurrent();
            $table->timestamp('finished_at')->nullable();
            $table->string('status', 50)->default('pending');
            $table->integer('total_articles')->default(0);
            $table->text('error_message')->nullable();
            $table->timestamps();

            $table->foreign('campaign_id')->references('id')->on('campaigns')->onDelete('set null');
            $table->foreign('source_id')->references('id')->on('news_sources')->onDelete('set null');
        });

        // Add CHECK constraint for PostgreSQL
        DB::statement("ALTER TABLE crawl_jobs ADD CONSTRAINT crawl_jobs_status_check CHECK (status IN ('pending', 'in_progress', 'success', 'failed'))");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('crawl_jobs');
    }
};
