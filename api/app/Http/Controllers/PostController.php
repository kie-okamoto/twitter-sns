<?php

namespace App\Http\Controllers;

use App\Models\Post;
use App\Models\Like; // ✅ 追加
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Schema;

class PostController extends Controller
{
    /**
     * 投稿一覧（新しい順）
     * - likes_count / comments_count
     * - comments（配列）も一緒に返す
     * - ✅ is_liked（自分がいいね済みか）を返す
     */
    public function index(Request $request)
    {
        $uid = $request->attributes->get('firebase_uid'); // ✅ 追加（未ログインなら null）
        $hasPostUserName = Schema::hasColumn('posts', 'user_name');

        // ✅ uid が取れるなら「自分がいいねした post_id 一覧」を作る
        $likedPostIds = [];
        if ($uid) {
            $likedPostIds = Like::where('user_id', $uid)->pluck('post_id')->all();
        }

        $posts = Post::query()
            ->latestWithCounts()
            ->with(['comments' => function ($q) {
                $q->latest()
                    ->select(['id', 'post_id', 'user_id', 'user_name', 'content', 'created_at']);
            }])
            ->get();

        return $posts->map(function ($p) use ($hasPostUserName, $likedPostIds) {
            return [
                'id' => $p->id,
                'content' => $p->content,
                'user_id' => $p->user_id, // Firebase UID（文字列）

                // postsテーブルに user_name がある場合のみ返す（無い場合は null）
                'user_name' => $hasPostUserName ? $p->user_name : null,

                'image_path' => $p->image_path,
                'likes_count' => $p->likes_count ?? 0,
                'comments_count' => $p->comments_count ?? 0,

                // ✅ 追加：自分がいいねしてるか
                'is_liked' => in_array($p->id, $likedPostIds, true),

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
        })->values();
    }

    /**
     * 投稿作成
     * body: { content: string (<=120), image_path?: string|null, user_name?: string }
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
            'user_name' => ['nullable', 'string', 'max:20'],
        ]);

        $create = [
            'user_id' => $uid,
            'content' => $data['content'],
            'image_path' => $data['image_path'] ?? null,
        ];

        if (Schema::hasColumn('posts', 'user_name')) {
            $name = isset($data['user_name']) ? trim((string)$data['user_name']) : '';
            // ✅ 空白だけは保存しない
            $create['user_name'] = $name !== '' ? $name : null;
        }

        $post = Post::create($create);


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
