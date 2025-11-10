<?php

namespace App\Http\Controllers;

use App\Jobs\IndexToElasticsearch;
use App\Jobs\ProcessData;
use App\Jobs\ProcessEmail;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class QueueController extends Controller
{
    /**
     * Dispatch a sample email job
     */
    public function dispatchEmail(Request $request): JsonResponse
    {
        $email = $request->input('email', 'test@example.com');
        $subject = $request->input('subject', 'Test Email');
        $message = $request->input('message', 'This is a test message');

        ProcessEmail::dispatch($email, $subject, $message);

        return response()->json([
            'message' => 'Email job dispatched successfully',
            'email' => $email,
        ]);
    }

    /**
     * Dispatch a sample data processing job
     */
    public function dispatchData(Request $request): JsonResponse
    {
        $data = $request->input('data', [
            ['id' => 1, 'name' => 'Item 1'],
            ['id' => 2, 'name' => 'Item 2'],
            ['id' => 3, 'name' => 'Item 3'],
        ]);

        ProcessData::dispatch($data);

        return response()->json([
            'message' => 'Data processing job dispatched successfully',
            'items_count' => count($data),
        ]);
    }

    /**
     * Dispatch a job to index data to Elasticsearch
     */
    public function dispatchElasticsearch(Request $request): JsonResponse
    {
        $index = $request->input('index', 'documents');
        $document = $request->input('document', [
            'title' => 'Sample Document',
            'content' => 'This is a sample document content',
            'timestamp' => now()->toIso8601String(),
        ]);

        IndexToElasticsearch::dispatch($index, $document);

        return response()->json([
            'message' => 'Elasticsearch indexing job dispatched successfully',
            'index' => $index,
        ]);
    }

    /**
     * Dispatch multiple jobs for testing
     */
    public function dispatchMultiple(): JsonResponse
    {
        // Dispatch email jobs
        for ($i = 1; $i <= 5; $i++) {
            ProcessEmail::dispatch(
                "user{$i}@example.com",
                "Test Email {$i}",
                "This is test email number {$i}"
            );
        }

        // Dispatch data processing jobs
        ProcessData::dispatch([
            ['id' => 1, 'name' => 'Item 1'],
            ['id' => 2, 'name' => 'Item 2'],
        ]);

        // Dispatch Elasticsearch indexing jobs
        for ($i = 1; $i <= 3; $i++) {
            IndexToElasticsearch::dispatch('documents', [
                'id' => $i,
                'title' => "Document {$i}",
                'content' => "Content for document {$i}",
                'timestamp' => now()->toIso8601String(),
            ]);
        }

        return response()->json([
            'message' => 'Multiple jobs dispatched successfully',
            'jobs_dispatched' => 9,
        ]);
    }
}

