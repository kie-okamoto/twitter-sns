<?php

namespace App\Http\Controllers;

use App\Models\Like;
use App\Models\Post;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class PostController extends Controller
{
    /**
     * 投稿一覧（新しい順）
     * - likes_count / comments_count
     * - comments（新しい順）
     * - is_liked（自分がいいね済みか）
     */
    public function index(Request $request)
    {
        $uid = $request->attributes->get('firebase_uid');

        // 自分がいいねした post_id を set 化
        $likedSet = [];
        if ($uid) {
            $likedSet = Like::where('user_id', $uid)->pluck('post_id')->flip()->all();
        }

        $posts = Post::query()
            ->latestWithCounts()
            ->with(['comments' => function ($q) {
                $q->select(['id', 'post_id', 'user_id', 'user_name', 'content', 'created_at'])
                    ->latest();
            }])
            ->get();

        return $posts->map(fn($p) => $this->shapePost($p, $likedSet))->values();
    }

    /**
     * 投稿1件取得（コメント画面用）
     * GET /posts/{post}
     */
    public function show(Request $request, Post $post)
    {
        $uid = $request->attributes->get('firebase_uid');

        $likedSet = [];
        if ($uid) {
            $likedSet = Like::where('user_id', $uid)->pluck('post_id')->flip()->all();
        }

        $post->loadCount(['likes', 'comments'])
            ->load(['comments' => function ($q) {
                $q->select(['id', 'post_id', 'user_id', 'user_name', 'content', 'created_at'])
                    ->latest();
            }]);

        return response()->json($this->shapePost($post, $likedSet), Response::HTTP_OK);
    }

    /**
     * 投稿作成（120文字）
     * POST /posts
     * body: { content }
     */
    public function store(Request $request)
    {
        $uid = $request->attributes->get('firebase_uid');
        if (!$uid) {
            return response()->json(['message' => 'Unauthenticated'], Response::HTTP_UNAUTHORIZED);
        }

        $data = $request->validate([
            'content' => ['required', 'string', 'max:120'],
        ]);

        // user_name は users から取得（偽装防止）
        $userName = User::where('firebase_uid', $uid)->value('name'); // null 可

        $post = Post::create([
            'user_id' => $uid,
            'user_name' => $userName,
            'content' => $data['content'],
        ]);

        // index と同じ形で返す
        $post->loadCount(['likes', 'comments'])->load(['comments' => fn($q) => $q->latest()]);
        return response()->json($this->shapePost($post, []), Response::HTTP_CREATED);
    }

    /**
     * 投稿削除（作成者のみ）
     * DELETE /posts/{post}
     */
    public function destroy(Request $request, Post $post)
    {
        $uid = $request->attributes->get('firebase_uid');
        if (!$uid || $post->user_id !== $uid) {
            return response()->json(['message' => 'Forbidden'], Response::HTTP_FORBIDDEN);
        }

        $post->delete();
        return response()->noContent();
    }

    /**
     * API返却整形（index/show/storeで共通化）
     */
    private function shapePost(Post $p, array $likedSet): array
    {
        return [
            'id' => $p->id,
            'content' => $p->content,
            'user_id' => $p->user_id,
            'user_name' => $p->user_name,

            'likes_count' => (int)($p->likes_count ?? 0),
            'comments_count' => (int)($p->comments_count ?? 0),
            'is_liked' => isset($likedSet[$p->id]),

            'created_at' => $p->created_at,

            'comments' => $p->comments->map(fn($c) => [
                'id' => $c->id,
                'post_id' => $c->post_id,
                'user_id' => $c->user_id,
                'user_name' => $c->user_name,
                'content' => $c->content,
                'created_at' => $c->created_at,
            ])->values(),
        ];
    }
}
