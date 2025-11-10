<?php

namespace App\Jobs;

use App\Models\Article;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class IndexArticleToElasticsearch implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * The number of times the job may be attempted.
     *
     * @var int
     */
    public $tries = 3;

    /**
     * Create a new job instance.
     */
    public function __construct(
        public int $articleId
    ) {
        //
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $article = Article::with(['campaign', 'source'])->findOrFail($this->articleId);

        $elasticsearchHost = env('ELASTICSEARCH_HOST', 'elasticsearch');
        $elasticsearchPort = env('ELASTICSEARCH_PORT', 9200);
        $index = 'articles';

        // Prepare document for Elasticsearch
        $document = [
            'id' => $article->id,
            'campaign_id' => $article->campaign_id,
            'source_id' => $article->source_id,
            'crawl_job_id' => $article->crawl_job_id,
            'title' => $article->title,
            'content' => $article->content,
            'url' => $article->url,
            'author' => $article->author,
            'published_at' => $article->published_at?->toIso8601String(),
            'summary' => $article->summary,
            'metadata' => $article->metadata ?? [],
            'campaign_name' => $article->campaign?->name,
            'source_name' => $article->source?->name,
            'created_at' => $article->created_at->toIso8601String(),
            'updated_at' => $article->updated_at->toIso8601String(),
        ];

        $url = "http://{$elasticsearchHost}:{$elasticsearchPort}/{$index}/_doc/{$article->id}";

        Log::info('Indexing article to Elasticsearch', [
            'article_id' => $article->id,
            'index' => $index,
        ]);

        try {
            $response = Http::put($url, $document);

            if ($response->successful()) {
                Log::info('Article indexed successfully', [
                    'article_id' => $article->id,
                    'index' => $index,
                    'response' => $response->json(),
                ]);
            } else {
                Log::error('Failed to index article', [
                    'article_id' => $article->id,
                    'index' => $index,
                    'status' => $response->status(),
                    'response' => $response->body(),
                ]);
                throw new \Exception("Failed to index article: {$response->status()}");
            }
        } catch (\Exception $e) {
            Log::error('Exception while indexing article to Elasticsearch', [
                'article_id' => $article->id,
                'index' => $index,
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }
}
