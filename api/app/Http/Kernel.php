<?php

namespace App\Http;

use Illuminate\Foundation\Http\Kernel as HttpKernel;

class Kernel extends HttpKernel
{
  /**
   * グローバルミドルウェア（全リクエスト共通）
   */
  protected $middleware = [
    // （任意）プロキシ設定を使う場合のみ有効化
    // \App\Http\Middleware\TrustProxies::class,

    // CORS はグローバルでOK
    \Illuminate\Http\Middleware\HandleCors::class,

    // ★ 旧: CheckForMaintenanceMode → 新: PreventRequestsDuringMaintenance
    \Illuminate\Foundation\Http\Middleware\PreventRequestsDuringMaintenance::class,

    \Illuminate\Foundation\Http\Middleware\ValidatePostSize::class,
    \App\Http\Middleware\TrimStrings::class,
    \Illuminate\Foundation\Http\Middleware\ConvertEmptyStringsToNull::class,
  ];

  /**
   * ルートグループ用ミドルウェア
   */
  protected $middlewareGroups = [
    'web' => [
      \App\Http\Middleware\EncryptCookies::class,
      \Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse::class,
      \Illuminate\Session\Middleware\StartSession::class,
      \Illuminate\View\Middleware\ShareErrorsFromSession::class,
      \App\Http\Middleware\VerifyCsrfToken::class,
      \Illuminate\Routing\Middleware\SubstituteBindings::class,
    ],

    'api' => [
      // APIは stateless
      'throttle:api',
      \Illuminate\Routing\Middleware\SubstituteBindings::class,
    ],
  ];

  /**
   * 個別ミドルウェア（エイリアス登録）
   */
  protected $middlewareAliases = [
    'auth'              => \App\Http\Middleware\Authenticate::class,
    'auth.basic'        => \Illuminate\Auth\Middleware\AuthenticateWithBasicAuth::class,
    'cache.headers'     => \Illuminate\Http\Middleware\SetCacheHeaders::class,
    'can'               => \Illuminate\Auth\Middleware\Authorize::class,
    'guest'             => \App\Http\Middleware\RedirectIfAuthenticated::class,
    'password.confirm'  => \Illuminate\Auth\Middleware\RequirePassword::class,
    'signed'            => \Illuminate\Routing\Middleware\ValidateSignature::class,
    'throttle'          => \Illuminate\Routing\Middleware\ThrottleRequests::class,
    'verified'          => \Illuminate\Auth\Middleware\EnsureEmailIsVerified::class,

    // 任意：古い記述との互換のため残しても害はありません
    'bindings'          => \Illuminate\Routing\Middleware\SubstituteBindings::class,

    // 🔥 Firebase 認証（あなたのカスタムミドルウェア）
    'firebase'          => \App\Http\Middleware\VerifyFirebaseToken::class,
  ];
}
