<?php

namespace App\Http\Controllers;

use App\Models\Like;
use App\Models\Post;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class LikeController extends Controller
{
    /**
     * いいねトグル（追加・削除両対応）
     * フロント：POST /posts/{post}/likes
     */
    public function toggle(Request $request, Post $post)
    {
        $uid = $request->attributes->get('firebase_uid');
        if (!$uid) {
            return response()->json(['message' => 'Unauthenticated'], Response::HTTP_UNAUTHORIZED);
        }

        // 既にいいねしているか確認
        $existing = Like::where('post_id', $post->id)
            ->where('user_id', $uid)
            ->first();

        if ($existing) {
            $existing->delete();
            $liked = false;
        } else {
            Like::create([
                'post_id' => $post->id,
                'user_id' => $uid,
            ]);
            $liked = true;
        }

        $count = Like::where('post_id', $post->id)->count();

        return response()->json([
            'liked' => $liked,
            'likes_count' => $count,
        ], Response::HTTP_OK);
    }
}
