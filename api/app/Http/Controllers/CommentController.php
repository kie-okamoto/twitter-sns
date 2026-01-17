<?php

namespace App\Http\Controllers;

use App\Models\Comment;
use App\Models\Post;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class CommentController extends Controller
{
    public function index(Post $post)
    {
        return $post->comments()
            ->latest()
            ->get(['id', 'post_id', 'user_id', 'user_name', 'content', 'created_at']);
    }

    public function store(Request $request)
    {
        $uid = $request->attributes->get('firebase_uid');
        if (!$uid) {
            return response()->json(['message' => 'Unauthenticated'], Response::HTTP_UNAUTHORIZED);
        }

        $data = $request->validate([
            'post_id' => ['required', 'integer', 'exists:posts,id'],
            'content' => ['required', 'string', 'max:120'],
            'user_name' => ['required', 'string', 'max:20'],
        ]);

        $comment = Comment::create([
            'post_id' => $data['post_id'],
            'user_id' => $uid,
            'user_name' => $data['user_name'],
            'content' => $data['content'],
        ]);

        return response()->json($comment, Response::HTTP_CREATED);
    }
}
