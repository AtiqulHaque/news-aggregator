<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Http;

class IndexToElasticsearch implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Create a new job instance.
     */
    public function __construct(
        public string $index,
        public array $document
    ) {
        //
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $elasticsearchHost = env('ELASTICSEARCH_HOST', 'elasticsearch');
        $elasticsearchPort = env('ELASTICSEARCH_PORT', 9200);
        $url = "http://{$elasticsearchHost}:{$elasticsearchPort}/{$this->index}/_doc";

        Log::info('Indexing document to Elasticsearch', [
            'index' => $this->index,
            'document' => $this->document,
        ]);

        try {
            $response = Http::post($url, $this->document);

            if ($response->successful()) {
                Log::info('Document indexed successfully', [
                    'index' => $this->index,
                    'response' => $response->json(),
                ]);
            } else {
                Log::error('Failed to index document', [
                    'index' => $this->index,
                    'status' => $response->status(),
                    'response' => $response->body(),
                ]);
            }
        } catch (\Exception $e) {
            Log::error('Exception while indexing to Elasticsearch', [
                'index' => $this->index,
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }
}

