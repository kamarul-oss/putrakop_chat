<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

final class UserDevice extends Model
{
    use HasFactory;
    use SoftDeletes;

    /** @var list<string> */
    protected $fillable = [
        'user_id',
        'device_fingerprint',
        'device_name',
        'device_type',
        'browser',
        'operating_system',
        'ip_address',
        'user_agent',
        'is_trusted',
        'last_active_at',
    ];

    /** @var array<string, string> */
    protected $casts = [
        'is_trusted' => 'boolean',
        'last_active_at' => 'datetime',
    ];

    /**
     * Get the user that owns the device.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Hash a device fingerprint using SHA-256.
     */
    public static function hashFingerprint(string $fingerprint): string
    {
        return hash('sha256', $fingerprint);
    }

    /**
     * Mark the device as active now.
     */
    public function touchActive(): self
    {
        $this->update(['last_active_at' => now()]);

        return $this;
    }

    /**
     * Trust this device.
     */
    public function trust(): self
    {
        $this->update(['is_trusted' => true]);

        return $this;
    }
}
