<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\PostController;
use App\Http\Controllers\LikeController;
use App\Http\Controllers\CommentController;

Route::middleware('firebase')->group(function () {
  // ===== 投稿 =====
  Route::get('/posts', [PostController::class, 'index']);
  Route::get('/posts/{post}', [PostController::class, 'show']); // ✅ 追加（推奨）
  Route::post('/posts', [PostController::class, 'store']);
  Route::delete('/posts/{post}', [PostController::class, 'destroy']);

  // ===== いいね =====
  Route::post('/likes', [LikeController::class, 'store']);
  Route::delete('/likes/{post}', [LikeController::class, 'destroy']);

  // ===== コメント =====
  Route::get('/posts/{post}/comments', [CommentController::class, 'index']);
  Route::post('/comments', [CommentController::class, 'store']);

  Route::get('/health', fn() => response()->json(['ok' => true]));
});
