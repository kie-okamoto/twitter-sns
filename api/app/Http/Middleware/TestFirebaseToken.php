<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class TestFirebaseToken
{
  public function handle(Request $request, Closure $next)
  {
    // ✅ ヘッダでUID切替（無ければ固定）
    $uid = $request->header('X-Test-Firebase-Uid', 'test-uid');

    // ✅ 複数パターンで注入（実装の取り方がどれでも一致するように）
    $request->merge([
      'firebase_uid' => $uid,
      'user_id' => $uid,
    ]);

    $request->attributes->set('firebase_uid', $uid);
    $request->attributes->set('user_id', $uid);

    return $next($request);
  }
}
