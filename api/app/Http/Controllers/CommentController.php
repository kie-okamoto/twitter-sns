<?php

namespace App\Http\Controllers;

use App\Models\Comment;
use App\Models\Post;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class CommentController extends Controller
{
    /**
     * コメント一覧（新しい順）
     */
    public function index(Post $post)
    {
        return $post->comments()
            ->with('user:id,name')  // ★ ユーザー名も欲しければ残してOK
            ->latest()
            ->get(['id', 'post_id', 'user_id', 'content', 'created_at']);
    }

    /**
     * コメント追加
     * body: { post_id: int, content: string (<=120) }
     */
    public function store(Request $request)
    {
        $uid = $request->attributes->get('firebase_uid');
        if (!$uid) {
            return response()->json(
                ['message' => 'Unauthenticated'],
                Response::HTTP_UNAUTHORIZED
            );
        }

        $data = $request->validate([
            'post_id' => ['required', 'integer', 'exists:posts,id'],
            'content' => ['required', 'string', 'max:120'],
        ]);

        $comment = Comment::create([
            'post_id' => $data['post_id'],
            'user_id' => $uid,
            'content' => $data['content'],
        ]);

        return response()->json($comment, Response::HTTP_CREATED);
    }
}
