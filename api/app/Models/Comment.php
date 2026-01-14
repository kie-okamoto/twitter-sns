<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Comment extends Model
{
    protected $fillable = [
        'post_id',
        'user_id',
        'content',
    ];

    public $timestamps = true;

    // 投稿リレーション
    public function post(): BelongsTo
    {
        return $this->belongsTo(Post::class);
    }

    // ★ 追加ここ！ユーザー取得
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id', 'id');
    }
}
