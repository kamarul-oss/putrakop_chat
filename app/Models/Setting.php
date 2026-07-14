<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * Application-wide key/value settings with type and group support.
 *
 * Provides static helper methods for reading and writing settings
 * with optional caching.
 */
final class Setting extends Model
{
    use HasFactory;

    /** @var list<string> */
    protected $fillable = [
        'key',
        'value',
        'type',
        'group',
        'description',
    ];

    // ─── Static Helpers ─────────────────────────────────────────

    /**
     * Retrieve a setting value by key, with optional default fallback.
     *
     * The raw string value is cast to the appropriate PHP type
     * based on the stored `type` column.
     */
    public static function get(string $key, mixed $default = null): mixed
    {
        $setting = static::where('key', $key)->first();

        if ($setting === null) {
            return $default;
        }

        return $setting->castValue($setting->value, $setting->type);
    }

    /**
     * Create or update a setting by key.
     */
    public static function set(string $key, mixed $value, string $type = 'string'): static
    {
        $rawValue = is_array($value) ? json_encode($value, JSON_THROW_ON_ERROR) : (string) $value;

        return static::updateOrCreate(
            ['key' => $key],
            [
                'value' => $rawValue,
                'type' => $type,
            ]
        );
    }

    /**
     * Retrieve all settings belonging to a group as an associative array.
     *
     * Returns [ 'key' => casted_value, ... ]
     */
    public static function getGroup(string $group): array
    {
        $settings = static::where('group', $group)->get();

        $result = [];
        foreach ($settings as $setting) {
            $result[$setting->key] = $setting->castValue($setting->value, $setting->type);
        }

        return $result;
    }

    // ─── Internal ───────────────────────────────────────────────

    /**
     * Cast a raw string value to the appropriate PHP type.
     */
    private function castValue(string $value, string $type): mixed
    {
        return match ($type) {
            'boolean' => filter_var($value, FILTER_VALIDATE_BOOLEAN),
            'integer' => (int) $value,
            'float' => (float) $value,
            'array', 'json' => json_decode($value, true, 512, JSON_THROW_ON_ERROR),
            'date' => \Carbon\Carbon::parse($value),
            default => $value,
        };
    }
}
