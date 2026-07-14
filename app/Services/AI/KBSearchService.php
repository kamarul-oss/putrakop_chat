<?php
declare(strict_types=1);

namespace App\Services\AI;

use App\Models\KnowledgeBase;
use Illuminate\Support\Collection;

/**
 * Service for searching and retrieving Knowledge Base articles.
 *
 * Provides keyword-based and department-scoped search functionality
 * to find relevant KB entries for AI-assisted responses.
 */
final class KBSearchService
{
    public function __construct(
        private readonly KnowledgeBase $knowledgeBase,
    ) {}

    /**
     * Search knowledge base articles by query string.
     *
     * Search strategy:
     * 1. Match by trigger_keywords (exact keyword match, highest priority)
     * 2. Match by title/content (LIKE query, lower priority)
     * 3. Filter by department (if specified) or all departments
     * 4. Only return active articles
     * 5. Order by priority DESC, then relevance
     *
     * @param string      $query        The search query
     * @param int|null    $departmentId Optional department filter
     * @param string      $language     Language code (en/bm)
     * @param int         $limit        Maximum number of results
     *
     * @return Collection<int, KnowledgeBase>
     */
    public function search(
        string $query,
        ?int $departmentId,
        string $language = 'en',
        int $limit = 5,
    ): Collection {
        $keywords = $this->extractKeywords($query);

        // Phase 1: Search by trigger keywords (exact match)
        $keywordResults = $this->getByKeywords($keywords, $departmentId);

        if ($keywordResults->isNotEmpty()) {
            return $keywordResults->take($limit);
        }

        // Phase 2: Search by title and content (LIKE match)
        $likeResults = $this->searchByContent($query, $departmentId, $language);

        return $likeResults->take($limit);
    }

    /**
     * Find articles matching any of the given keywords via trigger_keywords field.
     *
     * The trigger_keywords column stores a JSON array of keywords.
     * This method checks if any of the provided keywords appear in that array.
     *
     * @param array<int, string> $keywords    Keywords to search for
     * @param int|null           $departmentId Optional department filter
     *
     * @return Collection<int, KnowledgeBase>
     */
    public function getByKeywords(array $keywords, ?int $departmentId): Collection
    {
        if (empty($keywords)) {
            return collect();
        }

        $query = KnowledgeBase::query()
            ->where('is_active', true);

        if ($departmentId !== null) {
            $query->where(function ($q) use ($departmentId) {
                $q->where('department_id', $departmentId)
                    ->orWhereNull('department_id');
            });
        }

        // Search trigger_keywords JSON array
        $query->where(function ($q) use ($keywords) {
            foreach ($keywords as $keyword) {
                $q->orWhereRaw(
                    'JSON_CONTAINS(trigger_keywords, ?)',
                    [json_encode($keyword)]
                );
            }
        });

        return $query->orderByDesc('priority')
            ->get();
    }

    /**
     * Get all active articles for a specific department.
     *
     * @param int $departmentId The department ID
     *
     * @return Collection<int, KnowledgeBase>
     */
    public function getByDepartment(int $departmentId): Collection
    {
        return KnowledgeBase::query()
            ->where('is_active', true)
            ->where(function ($q) use ($departmentId) {
                $q->where('department_id', $departmentId)
                    ->orWhereNull('department_id');
            })
            ->orderByDesc('priority')
            ->get();
    }

    /**
     * Search by title and content using LIKE queries.
     *
     * @param string   $query        The search query
     * @param int|null $departmentId Optional department filter
     * @param string   $language     Language code (en/bm)
     *
     * @return Collection<int, KnowledgeBase>
     */
    private function searchByContent(string $query, ?int $departmentId, string $language): Collection
    {
        $keywords = $this->extractKeywords($query);

        $kbQuery = KnowledgeBase::query()
            ->where('is_active', true);

        if ($departmentId !== null) {
            $kbQuery->where(function ($q) use ($departmentId) {
                $q->where('department_id', $departmentId)
                    ->orWhereNull('department_id');
            });
        }

        // Search in title and content fields
        $kbQuery->where(function ($q) use ($keywords, $language) {
            foreach ($keywords as $keyword) {
                $likePattern = '%' . $keyword . '%';

                $q->orWhere('title', 'LIKE', $likePattern);

                // Search in the content field for the given language
                $contentColumn = $language === 'bm' ? 'content_bm' : 'content_en';
                $q->orWhere($contentColumn, 'LIKE', $likePattern);

                // Also search the other language column as fallback
                $fallbackColumn = $language === 'bm' ? 'content_en' : 'content_bm';
                $q->orWhere($fallbackColumn, 'LIKE', $likePattern);
            }
        });

        return $kbQuery->orderByDesc('priority')
            ->get();
    }

    /**
     * Extract meaningful keywords from a query string.
     *
     * Removes common stop words and short words, then returns
     * the remaining significant terms.
     *
     * @return array<int, string>
     */
    private function extractKeywords(string $query): array
    {
        $stopWords = [
            // English stop words
            'the', 'is', 'are', 'was', 'were', 'have', 'has', 'had',
            'do', 'does', 'did', 'will', 'would', 'could', 'should',
            'may', 'might', 'can', 'shall', 'to', 'of', 'in', 'for',
            'on', 'with', 'at', 'by', 'from', 'as', 'into', 'about',
            'a', 'an', 'and', 'or', 'but', 'not', 'no', 'nor',
            'i', 'me', 'my', 'we', 'our', 'you', 'your', 'he', 'she',
            'it', 'they', 'them', 'their', 'this', 'that', 'these',
            'what', 'which', 'who', 'whom', 'how', 'when', 'where',
            'why', 'if', 'then', 'so', 'than', 'too', 'very',
            'just', 'also', 'only', 'even', 'still', 'already',
            // Malay stop words
            'yang', 'dan', 'di', 'ini', 'itu', 'untuk', 'dengan',
            'pada', 'adalah', 'akan', 'telah', 'tidak', 'dari',
            'juga', 'sudah', 'lebih', 'boleh', 'hanya', 'ada',
            'ia', 'mereka', 'kami', 'kita', 'anda', 'saya',
            'bagaimana', 'apabila', 'kerana', 'jika', 'maka',
            'oleh', 'kepada', 'seperti', 'serta', 'antara',
        ];

        $words = preg_split('/\s+/', mb_strtolower($query));
        $keywords = [];

        foreach ($words as $word) {
            // Remove punctuation
            $cleanWord = preg_replace('/[^\p{L}\p{N}]/u', '', $word);

            if ($cleanWord === '' || mb_strlen($cleanWord) < 2) {
                continue;
            }

            if (! in_array($cleanWord, $stopWords, true)) {
                $keywords[] = $cleanWord;
            }
        }

        return array_values(array_unique($keywords));
    }
}
