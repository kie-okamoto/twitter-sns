<?php

namespace App\Http\Controllers;

use App\Models\Like;
use App\Models\Post;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Database\QueryException;

class LikeController extends Controller
{
    /**
     * いいね追加
     * POST /likes
     * body: { post_id }
     */
    public function store(Request $request)
    {
        $uid = $request->attributes->get('firebase_uid');
        if (!$uid) {
            return response()->json(['message' => 'Unauthenticated'], Response::HTTP_UNAUTHORIZED);
        }

        $data = $request->validate([
            'post_id' => ['required', 'integer', 'exists:posts,id'],
        ]);

        try {
            Like::firstOrCreate([
                'user_id' => $uid,
                'post_id' => $data['post_id'],
            ]);
        } catch (QueryException $e) {
            // 重複キー（unique(post_id, user_id)）だけは無視
            $sqlState = $e->errorInfo[0] ?? null; // 例: 23000
            $driverCode = $e->errorInfo[1] ?? null; // 例: 1062 (MySQL)
            if (!($sqlState === '23000' && (int)$driverCode === 1062)) {
                throw $e; // ✅ それ以外は握りつぶさない
            }
        }

        $count = Like::where('post_id', $data['post_id'])->count();

        return response()->json([
            'liked' => true,
            'likes_count' => $count,
        ], Response::HTTP_OK);
    }

    /**
     * いいね削除
     * DELETE /likes/{post}
     */
    public function destroy(Request $request, Post $post)
    {
        $uid = $request->attributes->get('firebase_uid');
        if (!$uid) {
            return response()->json(['message' => 'Unauthenticated'], Response::HTTP_UNAUTHORIZED);
        }

        Like::where('user_id', $uid)
            ->where('post_id', $post->id)
            ->delete();

        $count = Like::where('post_id', $post->id)->count();

        return response()->json([
            'liked' => false,
            'likes_count' => $count,
        ], Response::HTTP_OK);
    }
}
