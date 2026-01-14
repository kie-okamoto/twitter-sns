<?php

namespace App\Http\Controllers;

use App\Models\Post;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Validation\ValidationException;

class PostController extends Controller
{
    /**
     * 投稿一覧（新しい順）+ likes_count / comments_count
     */
    public function index()
    {
        return Post::query()
            ->latestWithCounts()
            ->get()
            ->map(fn($p) => [
                'id' => $p->id,
                'content' => $p->content,
                'user_id' => $p->user_id,         // Firebase UID（文字列）
                'image_path' => $p->image_path,
                'likes_count' => $p->likes_count,
                'comments_count' => $p->comments_count,
                'created_at' => $p->created_at,
            ]);
    }

    /**
     * 投稿作成
     * body: { content: string (<=120), image_path?: string|null }
     */
    public function store(Request $request)
    {
        $uid = $request->attributes->get('firebase_uid'); // VerifyFirebaseToken で付与
        if (!$uid) {
            return response()->json(['message' => 'Unauthenticated'], Response::HTTP_UNAUTHORIZED);
        }

        $data = $request->validate([
            'content' => ['required', 'string', 'max:120'],
            'image_path' => ['nullable', 'string', 'max:255'],
        ]);

        $post = Post::create([
            'user_id' => $uid,
            'content' => $data['content'],
            'image_path' => $data['image_path'] ?? null,
        ]);

        return response()->json($post, Response::HTTP_CREATED);
    }

    /**
     * 投稿削除（作成者のみ）
     */
    public function destroy(Post $post, Request $request)
    {
        $uid = $request->attributes->get('firebase_uid');
        if (!$uid || $post->user_id !== $uid) {
            return response()->json(['message' => 'Forbidden'], Response::HTTP_FORBIDDEN);
        }

        $post->delete();
        return response()->noContent();
    }
}
