<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Builder;

class Blog extends Model
{
    protected $fillable = [
        'resource',
        'external_id',
        'title',
        'author',
        'cat_name',
        'rating',
        'monitoring_interval',
        'last_checked_at',
        'reactions',
        'content',
        'is_cheking_active',
    ];

    public function posts(): HasMany
    {
        return $this->hasMany(Post::class);
    }

    public function notifications(): HasMany
    {
        return $this->hasMany(Notification::class);
    }

    /**
     * Scope: Get blogs that need monitoring
     */
    public function scopeNeedsMonitoring(Builder $query): Builder
    {
        return $query
            ->where('is_active', true)
            ->where(function ($q) {
                $q->whereNull('last_checked_at')
                    ->orWhereRaw(
                        'DATE_ADD(last_checked_at, INTERVAL monitoring_interval HOUR) <= NOW()'
                    );
            });
    }
}
