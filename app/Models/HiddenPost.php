<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Auth;

class HiddenPost extends Model
{
    use HasFactory;

    public $timestamps = false;

    protected $fillable = [
        'user_id',
        'post_id',
    ];

    protected static function boot(): void
    {
        parent::boot();

        self::creating(function (HiddenPost $hiddenPost) {
            if (!Auth::check()) {
                return;
            }

            if (isset($hiddenPost->user_id)) {
                return;
            }

            $hiddenPost->user_id = Auth::user()->id;
        });
    }

    public function hiddenBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id', 'id');
    }

    public function post(): BelongsTo
    {
        return $this->belongsTo(Post::class, 'post_id', 'id');
    }
}
