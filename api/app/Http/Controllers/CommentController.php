<?php

namespace App\Http\Controllers;

use App\Models\Comment;
use App\Models\Post;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class CommentController extends Controller
{
    /**
     * 投稿に紐づくコメント一覧（新しい順）
     * GET /posts/{post}/comments
     */
    public function index(Post $post)
    {
        return $post->comments()
            ->orderByDesc('created_at')
            ->orderByDesc('id')
            ->get(['id', 'post_id', 'user_id', 'user_name', 'content', 'created_at']);
    }


    /**
     * コメント作成（120文字）
     * POST /comments
     * body: { post_id, content }
     */
    public function store(Request $request)
    {
        $uid = $request->attributes->get('firebase_uid');
        if (!$uid) {
            return response()->json(['message' => 'Unauthenticated'], Response::HTTP_UNAUTHORIZED);
        }

        $data = $request->validate([
            'post_id' => ['required', 'integer', 'exists:posts,id'],
            'content' => ['required', 'string', 'max:120'],
        ]);

        // user_name は users から取得（偽装防止）
        $userName = User::where('firebase_uid', $uid)->value('name'); // null可

        $comment = Comment::create([
            'post_id' => $data['post_id'],
            'user_id' => $uid,
            'user_name' => $userName,
            'content' => $data['content'],
        ]);

        return response()->json(
            $comment->only(['id', 'post_id', 'user_id', 'user_name', 'content', 'created_at']),
            Response::HTTP_CREATED
        );
    }
}
