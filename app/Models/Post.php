<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Post extends Model
{
    protected $fillable = [
        'blog_id',
        'external_id',
        'title',
        'content',
        'rating',
        'reactions',
    ];

    protected $casts = [
        'reactions' => 'json',
    ];

    public function blog(): BelongsTo
    {
        return $this->belongsTo(Blog::class);
    }
}
