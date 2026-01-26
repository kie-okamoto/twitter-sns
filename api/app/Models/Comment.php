<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Comment extends Model
{
    protected $fillable = [
        'post_id',
        'user_id',    // Firebase UID
        'user_name',  // 表示名スナップショット
        'content',
    ];

    public $timestamps = true;

    public function post(): BelongsTo
    {
        return $this->belongsTo(Post::class);
    }

    public function user(): BelongsTo
    {
        // comments.user_id (Firebase UID) -> users.firebase_uid
        return $this->belongsTo(User::class, 'user_id', 'firebase_uid');
    }
}
