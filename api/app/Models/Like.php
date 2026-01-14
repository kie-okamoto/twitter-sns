<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Like extends Model
{
    /**
     * 一括代入可能なカラム
     */
    protected $fillable = [
        'post_id',   // 対象の投稿ID
        'user_id',   // Firebase UID（文字列）
    ];

    /**
     * タイムスタンプを使用（必要に応じて無効化も可）
     */
    public $timestamps = true;

    /**
     * リレーション：この「いいね」が属する投稿
     */
    public function post(): BelongsTo
    {
        return $this->belongsTo(Post::class);
    }
}
