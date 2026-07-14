<?php

declare(strict_types=1);

namespace App\Services\Chat;

use Illuminate\Support\Facades\Cache;

/**
 * Manages emoji data for the chat system.
 *
 * Provides:
 * - Categorized emoji list for the picker UI
 * - Frequently-used tracking per user (via cache)
 * - Shortcode-to-emoji conversion for inline text
 * - Search across all known emojis
 */
final class EmojiService
{
    /**
     * Cache key prefix for emoji-related data.
     */
    private const CACHE_PREFIX = 'chat:emoji:';

    /**
     * How long frequently-used data stays cached (30 days).
     */
    private const FREQ_CACHE_TTL = 60 * 24 * 30;

    /**
     * Maximum number of frequently-used emojis to return.
     */
    private const FREQ_LIMIT = 20;

    /**
     * Master emoji dataset, grouped by category.
     *
     * Each entry: ['emoji' => '😀', 'name' => 'grinning face', 'category' => 'smileys']
     *
     * @var array<string, list<array{emoji: string, name: string, category: string}>>
     */
    private static ?array $cache = null;

    // ---------------------------------------------------------------
    // Public API
    // ---------------------------------------------------------------

    /**
     * Return the full categorized emoji list for the picker.
     *
     * @return array<string, list<array{emoji: string, name: string, category: string}>>
     */
    public function getEmojis(): array
    {
        return self::$cache ??= $this->buildDataset();
    }

    /**
     * Return every emoji as a flat list (useful for search indexing).
     *
     * @return list<array{emoji: string, name: string, category: string}>
     */
    public function getFlatList(): array
    {
        $flat = [];

        foreach ($this->getEmojis() as $emojis) {
            array_push($flat, ...$emojis);
        }

        return $flat;
    }

    /**
     * Get a user's frequently used emojis.
     *
     * Results are cached in-memory via Cache facade.
     *
     * @return list<array{emoji: string, name: string, count: int}>
     */
    public function getFrequentlyUsed(int $userId): array
    {
        $key = self::CACHE_PREFIX . "freq:{$userId}";

        /** @var list<array{emoji: string, name: string, count: int}>|null $cached */
        $cached = Cache::get($key);

        if ($cached !== null) {
            return $cached;
        }

        // Bootstrap from empty — recordUsage will populate over time
        Cache::put($key, [], now()->addMinutes(self::FREQ_CACHE_TTL));

        return [];
    }

    /**
     * Record that a user used a particular emoji.
     *
     * Increments the usage count and re-sorts the list so the most-used
     * emojis always appear first.
     */
    public function recordUsage(int $userId, string $emoji): void
    {
        $key = self::CACHE_PREFIX . "freq:{$userId}";

        /** @var list<array{emoji: string, name: string, count: int}> $list */
        $list = Cache::get($key) ?? [];

        $found = false;

        foreach ($list as &$entry) {
            if ($entry['emoji'] === $emoji) {
                $entry['count']++;
                $found = true;
                break;
            }
        }
        unset($entry);

        if (! $found) {
            $name = $this->resolveEmojiName($emoji);

            $list[] = [
                'emoji' => $emoji,
                'name'  => $name,
                'count' => 1,
            ];
        }

        // Sort descending by count, then alphabetically by name for ties
        usort($list, static function (array $a, array $b): int {
            $cmp = $b['count'] <=> $a['count'];

            return $cmp !== 0 ? $cmp : strcmp($a['name'], $b['name']);
        });

        // Keep only the top N
        $list = array_slice($list, 0, self::FREQ_LIMIT, true);
        $list = array_values($list);

        Cache::put($key, $list, now()->addMinutes(self::FREQ_CACHE_TTL));
    }

    /**
     * Search emojis by name (case-insensitive, partial match).
     *
     * @return list<array{emoji: string, name: string, category: string}>
     */
    public function searchEmojis(string $query): array
    {
        $query = strtolower(trim($query));

        if ($query === '') {
            return [];
        }

        $results = [];

        foreach ($this->getFlatList() as $entry) {
            if (str_contains($entry['name'], $query)) {
                $results[] = $entry;
            }
        }

        return $results;
    }

    /**
     * Get a single emoji from its shortcode (e.g. "smile").
     *
     * @return string|null  The emoji character, or null if not found.
     */
    public function getEmojiByCode(string $code): ?string
    {
        $code = strtolower(trim($code));

        foreach ($this->getFlatList() as $entry) {
            if ($entry['name'] === $code) {
                return $entry['emoji'];
            }
        }

        return null;
    }

    /**
     * Convert shortcodes in a text string to their emoji characters.
     *
     * Supported formats:
     *   :smile:  → 😀
     *   :smile   → (also supported, trailing colon optional)
     *
     * Shortcodes are resolved by matching the emoji name.
     */
    public function parseEmojis(string $text): string
    {
        // Build a lookup table once per request (static avoids rebuild per call)
        static $lookup = null;

        if ($lookup === null) {
            $lookup = [];

            foreach ($this->getFlatList() as $entry) {
                $lookup[$entry['name']] = $entry['emoji'];
            }
        }

        // Replace :shortcode: and :shortcode (without trailing colon)
        return preg_replace_callback(
            '/:([a-z0-9_+-]+):?(?=[^a-z0-9_+-]|$)/i',
            static function (array $matches) use ($lookup): string {
                $code = strtolower($matches[1]);

                return $lookup[$code] ?? $matches[0];
            },
            $text
        );
    }

    /**
     * Get the list of category labels in display order.
     *
     * @return list<string>
     */
    public function getCategoryLabels(): array
    {
        return array_keys($this->getEmojis());
    }

    // ---------------------------------------------------------------
    // Dataset
    // ---------------------------------------------------------------

    /**
     * Build the master emoji dataset.
     *
     * @return array<string, list<array{emoji: string, name: string, category: string}>>
     */
    private function buildDataset(): array
    {
        $categories = [
            'Smileys' => [
                ['😀', 'grinning face'],
                ['😃', 'grinning face with big eyes'],
                ['😄', 'grinning face with smiling eyes'],
                ['😁', 'beaming face with smiling eyes'],
                ['😆', 'grinning squinting face'],
                ['😅', 'grinning face with sweat'],
                ['🤣', 'rolling on the floor laughing'],
                ['😂', 'face with tears of joy'],
                ['🙂', 'slightly smiling face'],
                ['🙃', 'upside-down face'],
                ['😉', 'winking face'],
                ['😊', 'smiling face with smiling eyes'],
                ['😇', 'smiling face with halo'],
                ['🥰', 'smiling face with hearts'],
                ['😍', 'heart eyes'],
                ['🤩', 'star-struck'],
                ['😘', 'face blowing a kiss'],
                ['😗', 'kissing face'],
                ['😚', 'kissing face with closed eyes'],
                ['😙', 'kissing face with smiling eyes'],
                ['🥲', 'smiling face with tear'],
                ['😋', 'face savoring food'],
                ['😛', 'face with tongue'],
                ['😜', 'winking face with tongue'],
                ['🤪', 'zany face'],
                ['😝', 'squinting face with tongue'],
                ['🤑', 'money-mouth face'],
                ['🤗', 'hugging face'],
                ['🤭', 'face with hand over mouth'],
                ['🫢', 'face with open eyes and hand over mouth'],
                ['🤫', 'shushing face'],
                ['🤔', 'thinking face'],
                ['🫡', 'saluting face'],
                ['🤐', 'zipper-mouth face'],
                ['🤨', 'face with raised eyebrow'],
                ['😐', 'neutral face'],
                ['😑', 'expressionless face'],
                ['😶', 'face without mouth'],
                ['🫠', 'melting face'],
                ['😏', 'smirking face'],
                ['😒', 'unamused face'],
                ['🙄', 'face with rolling eyes'],
                ['😬', 'grimacing face'],
                ['🤥', 'lying face'],
                ['😌', 'relieved face'],
                ['😔', 'pensive face'],
                ['😪', 'sleepy face'],
                ['🤤', 'drooling face'],
                ['😴', 'sleeping face'],
                ['😷', 'face with medical mask'],
                ['🤒', 'face with thermometer'],
                ['🤕', 'face with head-bandage'],
                ['🤢', 'nauseated face'],
                ['🤮', 'face vomiting'],
                ['🤧', 'sneezing face'],
                ['🥵', 'hot face'],
                ['🥶', 'cold face'],
                ['🥴', 'woozy face'],
                ['😵', 'face with crossed-out eyes'],
                ['🤯', 'exploding head'],
                ['🤠', 'cowboy hat face'],
                ['🥳', 'partying face'],
                ['🥸', 'disguised face'],
                ['😎', 'smiling face with sunglasses'],
                ['🤓', 'nerd face'],
                ['🧐', 'face with monocle'],
                ['😕', 'confused face'],
                ['🫤', 'face with diagonal mouth'],
                ['😟', 'worried face'],
                ['🙁', 'slightly frowning face'],
                ['☹️', 'frowning face'],
                ['😮', 'face with open mouth'],
                ['😯', 'hushed face'],
                ['😲', 'astonished face'],
                ['😳', 'flushed face'],
                ['🥺', 'pleading face'],
                ['🥹', 'face holding back tears'],
                ['😦', 'frowning face with open mouth'],
                ['😧', 'anguished face'],
                ['😨', 'fearful face'],
                ['😰', 'anxious face with sweat'],
                ['😥', 'sad but relieved face'],
                ['😢', 'crying face'],
                ['😭', 'loudly crying face'],
                ['😱', 'face screaming in fear'],
                ['😖', 'confounded face'],
                ['😣', 'persevering face'],
                ['😞', 'disappointed face'],
                ['😓', 'downcast face with sweat'],
                ['😩', 'weary face'],
                ['😫', 'tired face'],
                ['🥱', 'yawning face'],
            ],
            'Gestures' => [
                ['👋', 'waving hand'],
                ['🤚', 'raised back of hand'],
                ['🖐️', 'hand with fingers splayed'],
                ['✋', 'raised hand'],
                ['🖖', 'vulcan salute'],
                ['🫱', 'rightwards hand'],
                ['🫲', 'leftwards hand'],
                ['🫳', 'palm down hand'],
                ['🫴', 'palm up hand'],
                ['👌', 'OK hand'],
                ['🤌', 'pinched fingers'],
                ['🤏', 'pinching hand'],
                ['✌️', 'victory hand'],
                ['🤞', 'crossed fingers'],
                ['🫰', 'hand with index finger and thumb crossed'],
                ['🤟', 'love-you gesture'],
                ['🤘', 'sign of the horns'],
                ['🤙', 'call me hand'],
                ['👈', 'backhand index pointing left'],
                ['👉', 'backhand index pointing right'],
                ['👆', 'backhand index pointing up'],
                ['🖕', 'middle finger'],
                ['👇', 'backhand index pointing down'],
                ['👍', 'thumbs up'],
                ['👎', 'thumbs down'],
                ['✊', 'raised fist'],
                ['👊', 'oncoming fist'],
                ['🤛', 'left-facing fist'],
                ['🤜', 'right-facing fist'],
                ['👏', 'clapping hands'],
                ['🙌', 'raising hands'],
                ['🫶', 'heart hands'],
                ['👐', 'open hands'],
                ['🤲', 'palms up together'],
                ['🤝', 'handshake'],
                ['🙏', 'folded hands'],
            ],
            'Hearts' => [
                ['❤️', 'red heart'],
                ['🧡', 'orange heart'],
                ['💛', 'yellow heart'],
                ['💚', 'green heart'],
                ['💙', 'blue heart'],
                ['💜', 'purple heart'],
                ['🖤', 'black heart'],
                ['🤍', 'white heart'],
                ['🤎', 'brown heart'],
                ['💔', 'broken heart'],
                ['❤️‍🔥', 'heart on fire'],
                ['❤️‍🩹', 'mending heart'],
                ['💕', 'two hearts'],
                ['💞', 'revolving hearts'],
                ['💓', 'beating heart'],
                ['💗', 'growing heart'],
                ['💖', 'sparkling heart'],
                ['💘', 'heart with arrow'],
                ['💝', 'heart with ribbon'],
                ['💟', 'heart decoration'],
                ['❣️', 'heart exclamation'],
                ['💔', 'broken heart'],
                ['💕', 'two hearts'],
            ],
            'Objects' => [
                ['💻', 'laptop'],
                ['🖥️', 'desktop computer'],
                ['📱', 'mobile phone'],
                ['📲', 'mobile phone with arrow'],
                ['⌨️', 'keyboard'],
                ['🖨️', 'printer'],
                ['🖱️', 'computer mouse'],
                ['💾', 'floppy disk'],
                ['💿', 'optical disk'],
                ['📀', 'DVD'],
                ['📷', 'camera'],
                ['📸', 'camera with flash'],
                ['📹', 'video camera'],
                ['🎥', 'movie camera'],
                ['📽️', 'film projector'],
                ['🎬', 'clapper board'],
                ['📺', 'television'],
                ['📻', 'radio'],
                ['🎙️', 'studio microphone'],
                ['🎚️', 'level slider'],
                ['🎛️', 'control knobs'],
                ['⏱️', 'stopwatch'],
                ['⏲️', 'timer clock'],
                ['⏰', 'alarm clock'],
                ['🕰️', 'mantelpiece clock'],
                ['📡', 'satellite antenna'],
                ['🔋', 'battery'],
                ['🔌', 'electric plug'],
                ['💡', 'light bulb'],
                ['🔦', 'flashlight'],
                ['🕯️', 'candle'],
                ['🗑️', 'wastebasket'],
                ['💰', 'money bag'],
                ['💳', 'credit card'],
                ['📦', 'package'],
                ['📫', 'mailbox'],
                ['✏️', 'pencil'],
                ['✒️', 'black nib'],
                ['🖊️', 'pen'],
                ['🖋️', 'fountain pen'],
                ['📝', 'memo'],
                ['📁', 'file folder'],
                ['📂', 'open file folder'],
                ['📅', 'calendar'],
                ['📋', 'clipboard'],
                ['📌', 'pushpin'],
                ['📎', 'paperclip'],
                ['🔒', 'locked'],
                ['🔓', 'unlocked'],
            ],
            'Symbols' => [
                ['❤️', 'red heart'],
                ['⭐', 'star'],
                ['🌟', 'glowing star'],
                ['✨', 'sparkles'],
                ['💫', 'dizzy'],
                ['⚡', 'high voltage'],
                ['🔥', 'fire'],
                ['💯', 'hundred points'],
                ['🎉', 'party popper'],
                ['🎊', 'confetti ball'],
                ['✅', 'check mark button'],
                ['❌', 'cross mark'],
                ['❎', 'cross mark button'],
                ['⭕', 'hollow red circle'],
                ['💢', 'anger symbol'],
                ['💥', 'collision'],
                ['💦', 'sweat droplets'],
                ['💨', 'dashing away'],
                ['🕳️', 'hole'],
                ['💬', 'speech balloon'],
                ['👁️‍🗨️', 'eye in speech bubble'],
                ['🗨️', 'left speech bubble'],
                ['🗯️', 'right anger bubble'],
                ['💭', 'thought balloon'],
                ['💤', 'zzz'],
                ['🔴', 'red circle'],
                ['🟠', 'orange circle'],
                ['🟡', 'yellow circle'],
                ['🟢', 'green circle'],
                ['🔵', 'blue circle'],
                ['🟣', 'purple circle'],
                ['⚫', 'black circle'],
                ['⚪', 'white circle'],
                ['🟤', 'brown circle'],
                ['🔺', 'red triangle pointed up'],
                ['🔻', 'red triangle pointed down'],
                ['🔶', 'orange diamond'],
                ['🔷', 'blue diamond'],
                ['🔸', 'small orange diamond'],
                ['🔹', 'small blue diamond'],
            ],
            'Flags' => [
                ['🏁', 'chequered flag'],
                ['🚩', 'triangular flag'],
                ['🎌', 'crossed flags'],
                ['🏴', 'black flag'],
                ['🏳️', 'white flag'],
                ['🏳️‍🌈', 'rainbow flag'],
                ['🏳️‍⚧️', 'transgender flag'],
                ['🏴‍☠️', 'pirate flag'],
                ['🇺🇸', 'flag United States'],
                ['🇬🇧', 'flag United Kingdom'],
                ['🇲🇾', 'flag Malaysia'],
                ['🇸🇬', 'flag Singapore'],
                ['🇮🇩', 'flag Indonesia'],
                ['🇹🇭', 'flag Thailand'],
                ['🇯🇵', 'flag Japan'],
                ['🇰🇷', 'flag South Korea'],
                ['🇨🇳', 'flag China'],
                ['🇮🇳', 'flag India'],
                ['🇦🇺', 'flag Australia'],
                ['🇩🇪', 'flag Germany'],
                ['🇫🇷', 'flag France'],
                ['🇪🇸', 'flag Spain'],
                ['🇮🇹', 'flag Italy'],
                ['🇧🇷', 'flag Brazil'],
                ['🇨🇦', 'flag Canada'],
                ['🇳🇿', 'flag New Zealand'],
            ],
        ];

        $dataset = [];

        foreach ($categories as $category => $emojis) {
            $dataset[$category] = array_map(
                static fn (array $pair) => [
                    'emoji'    => $pair[0],
                    'name'     => $pair[1],
                    'category' => $category,
                ],
                $emojis
            );
        }

        return $dataset;
    }

    // ---------------------------------------------------------------
    // Helpers
    // ---------------------------------------------------------------

    /**
     * Resolve the name for an emoji character by scanning the dataset.
     */
    private function resolveEmojiName(string $emoji): string
    {
        foreach ($this->getFlatList() as $entry) {
            if ($entry['emoji'] === $emoji) {
                return $entry['name'];
            }
        }

        return 'unknown';
    }
}
