<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Controller;
use App\Models\Department;
use App\Models\KnowledgeBase;
use App\Services\AI\KBSearchService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;

class KnowledgeBaseController extends Controller
{
    public function __construct(
        private readonly KBSearchService $kbSearchService
    ) {}

    /**
     * List knowledge base articles with filtering and pagination.
     */
    public function index(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'search'         => 'nullable|string|max:255',
            'department_id'  => 'nullable|exists:departments,id',
            'category'       => 'nullable|string|max:100',
            'is_active'      => 'nullable|boolean',
        ]);

        $query = KnowledgeBase::query()
            ->with('department');

        if ($request->filled('search')) {
            $search = $validated['search'];
            $query->where(function ($q) use ($search) {
                $q->where('title_en', 'LIKE', "%{$search}%")
                  ->orWhere('title_bm', 'LIKE', "%{$search}%")
                  ->orWhere('content_en', 'LIKE', "%{$search}%")
                  ->orWhere('content_bm', 'LIKE', "%{$search}%");
            });
        }

        if ($request->filled('department_id')) {
            $query->where('department_id', $validated['department_id']);
        }

        if ($request->filled('category')) {
            $query->where('category', $validated['category']);
        }

        if ($request->filled('is_active')) {
            $query->where('is_active', $validated['is_active']);
        }

        $articles = $query->orderBy('priority', 'desc')
            ->orderBy('created_at', 'desc')
            ->paginate($request->integer('per_page', 15));

        return response()->json([
            'success' => true,
            'data'    => $articles->items(),
            'meta'    => [
                'current_page' => $articles->currentPage(),
                'last_page'    => $articles->lastPage(),
                'per_page'     => $articles->perPage(),
                'total'        => $articles->total(),
            ],
        ]);
    }

    /**
     * Create a new knowledge base article.
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'title_en'          => 'required|string|max:255',
            'title_bm'          => 'nullable|string|max:255',
            'content_en'        => 'required|string|max:10000',
            'content_bm'        => 'nullable|string|max:10000',
            'department_id'     => 'nullable|exists:departments,id',
            'category'          => 'nullable|string|max:100',
            'is_active'         => 'boolean',
            'priority'          => 'integer|min:0|max:100',
            'trigger_keywords'  => 'nullable|array',
            'trigger_keywords.*'=> 'string|max:100',
        ]);

        $validated['is_active'] = $validated['is_active'] ?? true;
        $validated['priority']  = $validated['priority'] ?? 0;

        $article = KnowledgeBase::create($validated);

        return response()->json([
            'success' => true,
            'data'    => $article->load('department'),
            'message' => 'Article created',
        ], 201);
    }

    /**
     * Get a single knowledge base article.
     */
    public function show(KnowledgeBase $knowledgeBase): JsonResponse
    {
        $knowledgeBase->load('department');

        return response()->json([
            'success' => true,
            'data'    => $knowledgeBase,
        ]);
    }

    /**
     * Update an existing knowledge base article.
     */
    public function update(Request $request, KnowledgeBase $knowledgeBase): JsonResponse
    {
        $validated = $request->validate([
            'title_en'          => 'sometimes|required|string|max:255',
            'title_bm'          => 'nullable|string|max:255',
            'content_en'        => 'sometimes|required|string|max:10000',
            'content_bm'        => 'nullable|string|max:10000',
            'department_id'     => 'nullable|exists:departments,id',
            'category'          => 'nullable|string|max:100',
            'is_active'         => 'boolean',
            'priority'          => 'integer|min:0|max:100',
            'trigger_keywords'  => 'nullable|array',
            'trigger_keywords.*'=> 'string|max:100',
        ]);

        $knowledgeBase->update($validated);

        return response()->json([
            'success' => true,
            'data'    => $knowledgeBase->fresh()->load('department'),
            'message' => 'Article updated',
        ]);
    }

    /**
     * Soft-delete a knowledge base article.
     */
    public function destroy(KnowledgeBase $knowledgeBase): JsonResponse
    {
        $knowledgeBase->delete();

        return response()->json([
            'success' => true,
            'message' => 'Article deleted',
        ]);
    }

    /**
     * Search knowledge base articles via the AI search service.
     */
    public function search(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'query'         => 'required|string|max:255',
            'department_id' => 'nullable|exists:departments,id',
            'language'      => 'nullable|in:en,bm',
        ]);

        $results = $this->kbSearchService->search(
            query: $validated['query'],
            departmentId: $validated['department_id'] ?? null,
            language: $validated['language'] ?? 'en'
        );

        return response()->json([
            'success' => true,
            'data'    => $results,
        ]);
    }

    /**
     * Bulk import knowledge base articles.
     */
    public function bulkImport(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'articles'               => 'required|array|min:1',
            'articles.*.title_en'    => 'required|string|max:255',
            'articles.*.title_bm'    => 'nullable|string|max:255',
            'articles.*.content_en'  => 'required|string|max:10000',
            'articles.*.content_bm'  => 'nullable|string|max:10000',
            'articles.*.department_id' => 'nullable|exists:departments,id',
            'articles.*.category'    => 'nullable|string|max:100',
            'articles.*.is_active'   => 'boolean',
            'articles.*.priority'    => 'integer|min:0|max:100',
            'articles.*.trigger_keywords'       => 'nullable|array',
            'articles.*.trigger_keywords.*'     => 'string|max:100',
        ]);

        $imported = 0;
        $errors   = [];

        foreach ($validated['articles'] as $index => $articleData) {
            try {
                $articleData['is_active'] = $articleData['is_active'] ?? true;
                $articleData['priority']  = $articleData['priority'] ?? 0;

                KnowledgeBase::create($articleData);
                $imported++;
            } catch (\Throwable $e) {
                $errors[] = [
                    'index'   => $index,
                    'message' => $e->getMessage(),
                ];
            }
        }

        return response()->json([
            'success' => true,
            'data'    => [
                'imported' => $imported,
                'errors'   => $errors,
            ],
            'message' => "Bulk import completed. {$imported} articles imported.",
        ], 201);
    }
}
