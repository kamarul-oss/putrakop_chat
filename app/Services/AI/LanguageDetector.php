<?php
declare(strict_types=1);

namespace App\Services\AI;

/**
 * Detects whether text is written in Bahasa Malaysia (BM) or English (EN).
 *
 * Uses keyword frequency analysis against common words in each language
 * to determine the predominant language of the given text.
 */
final class LanguageDetector
{
    /**
     * Common Bahasa Malaysia words used for detection.
     */
    private const BM_KEYWORDS = [
        'saya', 'anda', 'ini', 'itu', 'dengan', 'untuk', 'tidak',
        'ya', 'boleh', 'bagaimana', 'apa', 'di mana', 'bila',
        'mengapa', 'kerana', 'jika', 'tetapi', 'dan', 'atau',
        'adalah', 'akan', 'telah', 'sedang', 'supaya', 'agar',
        'seperti', 'hanya', 'juga', 'lagi', 'sudah', 'belum',
        'selamat', 'terima', 'kasih', 'masalah', 'tiada', 'semua',
        'orang', 'rumah', 'duit', 'wang', 'bayar', 'harga',
        'tolong', 'maaf', 'hai', 'mari', 'rosak', 'tidak berfungsi',
    ];

    /**
     * Common English words used for detection.
     */
    private const EN_KEYWORDS = [
        'the', 'is', 'are', 'was', 'were', 'have', 'has', 'can',
        'will', 'please', 'thank', 'you', 'how', 'what', 'where',
        'when', 'why', 'this', 'that', 'with', 'for', 'not',
        'but', 'and', 'or', 'if', 'so', 'just', 'also', 'very',
        'hello', 'hi', 'hey', 'yes', 'no', 'okay', 'sure',
        'help', 'need', 'want', 'problem', 'issue', 'error',
        'broken', 'working', 'good', 'morning', 'afternoon',
        'evening', 'welcome', 'sorry', 'money', 'pay', 'price',
    ];

    /**
     * Detect the language of the given text.
     *
     * @return string 'en' or 'bm'
     */
    public function detect(string $text): string
    {
        if (trim($text) === '') {
            return 'en'; // default to English for empty text
        }

        $lowerText = mb_strtolower($text);
        $words = preg_split('/\s+/', $lowerText);
        $totalWords = count($words);

        if ($totalWords === 0) {
            return 'en';
        }

        $bmScore = 0;
        $enScore = 0;

        foreach ($words as $word) {
            // Clean punctuation from word for matching
            $cleanWord = preg_replace('/[^\p{L}\p{N}]/u', '', $word);

            if ($cleanWord === '') {
                continue;
            }

            if (in_array($cleanWord, self::BM_KEYWORDS, true)) {
                $bmScore++;
            }

            if (in_array($cleanWord, self::EN_KEYWORDS, true)) {
                $enScore++;
            }
        }

        // Calculate percentages
        $bmPercentage = $totalWords > 0 ? ($bmScore / $totalWords) * 100 : 0;
        $enPercentage = $totalWords > 0 ? ($enScore / $totalWords) * 100 : 0;

        // If scores are equal, default to English
        if ($bmPercentage === $enPercentage) {
            return 'en';
        }

        return $bmPercentage > $enPercentage ? 'bm' : 'en';
    }

    /**
     * Check if text is predominantly Bahasa Malaysia.
     */
    public function isBM(string $text): bool
    {
        return $this->detect($text) === 'bm';
    }

    /**
     * Check if text is predominantly English.
     */
    public function isEN(string $text): bool
    {
        return $this->detect($text) === 'en';
    }
}
