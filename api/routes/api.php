<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\PostController;
use App\Http\Controllers\LikeController;
use App\Http\Controllers\CommentController;

Route::middleware('firebase')->group(function () {

  // ===== 投稿 =====
  Route::get('/posts', [PostController::class, 'index']);
  Route::post('/posts', [PostController::class, 'store']);
  Route::delete('/posts/{post}', [PostController::class, 'destroy']);

  // ===== いいね =====
  Route::post('/likes', [LikeController::class, 'store']);
  Route::delete('/likes', [LikeController::class, 'destroy']);

  // ===== コメント =====
  // 一覧
  Route::get('/posts/{post}/comments', [CommentController::class, 'index']);
  // 追加（★ これが無いと 404 になる）
  Route::post('/comments', [CommentController::class, 'store']);

  // 動作確認用
  Route::get('/health', fn() => response()->json(['ok' => true]));
});
