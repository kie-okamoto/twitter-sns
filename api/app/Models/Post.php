<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Post extends Model
{
    protected $fillable = [
        'user_id',     
        'content',
        'user_name',  
    ];

    public function user(): BelongsTo
    {
        // posts.user_id (Firebase UID) -> users.firebase_uid
        return $this->belongsTo(User::class, 'user_id', 'firebase_uid');
    }

    public function likes(): HasMany
    {
        return $this->hasMany(Like::class);
    }

    public function comments(): HasMany
    {
        return $this->hasMany(Comment::class)->latest();
    }

    // 一覧用：likes_count / comments_count をSQLで取得し新しい順に
    public function scopeLatestWithCounts($q)
    {
        return $q->withCount(['likes', 'comments'])->latest();
    }
}
