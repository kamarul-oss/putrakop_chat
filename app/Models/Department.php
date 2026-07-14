<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

final class Department extends Model
{
    use HasFactory;
    use SoftDeletes;

    /** @var list<string> */
    protected $fillable = [
        'name_en',
        'name_bm',
        'description_en',
        'description_bm',
        'color',
        'icon',
        'is_active',
        'priority',
        'max_queue_size',
        'max_agents',
        'business_hours',
        'ai_config',
    ];

    /** @var array<string, string> */
    protected $casts = [
        'is_active' => 'boolean',
        'priority' => 'integer',
        'max_queue_size' => 'integer',
        'max_agents' => 'integer',
        'business_hours' => 'array',
        'ai_config' => 'array',
    ];

    public function responses(): HasMany
    {
        return $this->hasMany(DepartmentResponse::class);
    }

    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }
}
