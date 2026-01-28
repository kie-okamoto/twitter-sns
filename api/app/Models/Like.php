<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Like extends Model
{
    protected $fillable = [
        'post_id',
        'user_id', 
    ];

    public $timestamps = true;

    public function post(): BelongsTo
    {
        return $this->belongsTo(Post::class);
    }

    public function user(): BelongsTo
    {
        // likes.user_id (Firebase UID) -> users.firebase_uid
        return $this->belongsTo(User::class, 'user_id', 'firebase_uid');
    }
}
