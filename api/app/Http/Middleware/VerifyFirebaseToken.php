<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Kreait\Firebase\Factory;
use Kreait\Firebase\Exception\Auth\FailedToVerifyToken;

class VerifyFirebaseToken
{
    public function handle(Request $request, Closure $next)
    {
        $token = $request->bearerToken();
        if (!$token) {
            return response()->json(['message' => 'Missing bearer token'], Response::HTTP_UNAUTHORIZED);
        }

        $cred = config('services.firebase.credentials');
        $proj = config('services.firebase.project_id');

        // 🔥 Firebase資格情報の存在チェック
        if (!$cred || !file_exists($cred)) {
            return response()->json([
                'message' => 'Firebase credentials not found',
                'path' => $cred ?: '(empty)',
            ], 500);
        }

        // 🔥 サービスアカウントを明示的に使用
        $factory = (new Factory())
            ->withServiceAccount($cred)
            ->withProjectId($proj);

        try {
            $auth = $factory->createAuth();
            $verified = $auth->verifyIdToken($token);
            $uid = $verified->claims()->get('sub'); // Firebase UID
            $request->attributes->set('firebase_uid', $uid);
        } catch (FailedToVerifyToken $e) {
            return response()->json(['message' => 'Invalid token'], 401);
        } catch (\Throwable $e) {
            return response()->json([
                'message' => 'Firebase auth error',
                'error' => $e->getMessage(),
            ], 500);
        }

        return $next($request);
    }
}
