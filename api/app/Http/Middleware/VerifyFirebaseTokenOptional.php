<?php

namespace App\Http\Middleware;

use App\Models\User;
use Closure;
use Illuminate\Http\Request;
use Kreait\Firebase\Factory;
use Kreait\Firebase\Exception\Auth\FailedToVerifyToken;

class VerifyFirebaseTokenOptional
{
  public function handle(Request $request, Closure $next)
  {
    $token = $request->bearerToken();

    // ✅ トークン無しなら未ログインとして通す
    if (!$token) {
      $request->attributes->set('firebase_uid', null);
      return $next($request);
    }

    $cred = config('services.firebase.credentials');
    $proj = config('services.firebase.project_id');

    // 資格情報が無いなら「認証はできない」扱いで未ログインとして通す
    if (!$cred || !file_exists($cred)) {
      $request->attributes->set('firebase_uid', null);
      return $next($request);
    }

    $factory = (new Factory())
      ->withServiceAccount($cred)
      ->withProjectId($proj);

    try {
      $auth = $factory->createAuth();

      // トークン検証
      $verified = $auth->verifyIdToken($token);
      $uid = (string) $verified->claims()->get('sub');

      // uid を載せる
      $request->attributes->set('firebase_uid', $uid);

      // users 同期（A案）
      $firebaseUser = $auth->getUser($uid);
      $displayName = $firebaseUser->displayName ?? null;

      User::updateOrCreate(
        ['firebase_uid' => $uid],
        ['name' => $displayName ?: null]
      );
    } catch (FailedToVerifyToken $e) {
      // ❗トークンが不正なら未ログイン扱いで通す
      $request->attributes->set('firebase_uid', null);
    } catch (\Throwable $e) {
      // 予期せぬエラーも未ログイン扱いで通す（閲覧が落ちるのを防ぐ）
      $request->attributes->set('firebase_uid', null);
    }

    return $next($request);
  }
}
