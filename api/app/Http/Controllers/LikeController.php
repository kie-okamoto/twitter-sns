<?php

namespace App\Http\Controllers;

use App\Models\Like;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class LikeController extends Controller
{
    /**
     * いいね追加
     * POST /likes  body: { post_id }
     */
    public function store(Request $request)
    {
        $uid = $request->attributes->get('firebase_uid');
        if (!$uid) {
            return response()->json(['message' => 'Unauthenticated'], Response::HTTP_UNAUTHORIZED);
        }

        $data = $request->validate([
            'post_id' => ['required', 'integer'],
        ]);

        // ✅ 二重いいね防止（DBにも unique 推奨）
        Like::firstOrCreate([
            'user_id' => $uid,
            'post_id' => $data['post_id'],
        ]);

        $count = Like::where('post_id', $data['post_id'])->count();

        return response()->json([
            'liked' => true,
            'likes_count' => $count,
        ], Response::HTTP_OK);
    }

    /**
     * いいね削除
     * DELETE /likes body: { post_id }
     */
    public function destroy(Request $request)
    {
        $uid = $request->attributes->get('firebase_uid');
        if (!$uid) {
            return response()->json(['message' => 'Unauthenticated'], Response::HTTP_UNAUTHORIZED);
        }

        $data = $request->validate([
            'post_id' => ['required', 'integer'],
        ]);

        Like::where('user_id', $uid)
            ->where('post_id', $data['post_id'])
            ->delete();

        $count = Like::where('post_id', $data['post_id'])->count();

        return response()->json([
            'liked' => false,
            'likes_count' => $count,
        ], Response::HTTP_OK);
    }
}
