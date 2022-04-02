<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Post extends Model
{
    use HasFactory;

    protected $fillable = [
        'content',
        'images',
        'author_id'
    ];

    protected $casts = [
        'images' => 'array'
    ];

    public function author(): BelongsTo
    {
        return $this->belongsTo(User::class, 'author_id', 'id');
    }

    public function likes(): HasMany
    {
        return $this->hasMany(Like::class, 'post_id', 'id');
    }
}
