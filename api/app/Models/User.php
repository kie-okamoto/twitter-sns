<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class User extends Model
{
    use HasFactory;

    protected $fillable = [
        'firebase_uid',
        'name',
    ];

    // Firebase UID をキーに参照する想定
    // ※ primary key は id のままでOK

    public function posts(): HasMany
    {
        return $this->hasMany(Post::class, 'user_id', 'firebase_uid');
    }

    public function comments(): HasMany
    {
        return $this->hasMany(Comment::class, 'user_id', 'firebase_uid');
    }

    public function likes(): HasMany
    {
        return $this->hasMany(Like::class, 'user_id', 'firebase_uid');
    }
}
