<?php

namespace App\Http\Middleware;

use App\Models\User;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Kreait\Firebase\Factory;
use Kreait\Firebase\Exception\Auth\FailedToVerifyToken;
use Kreait\Firebase\Exception\Auth\InvalidToken;
use Kreait\Firebase\Exception\Auth\UserNotFound;

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

        if (!$cred || !file_exists($cred)) {
            return response()->json([
                'message' => 'Firebase credentials not found',
                'path' => $cred ?: '(empty)',
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }

        if (!$proj) {
            return response()->json([
                'message' => 'Firebase project_id not configured',
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }

        $factory = (new Factory())
            ->withServiceAccount($cred)
            ->withProjectId($proj);

        try {
            $auth = $factory->createAuth();

            $verified = $auth->verifyIdToken($token);
            $uid = (string) $verified->claims()->get('sub');

            // uid を request に載せる
            $request->attributes->set('firebase_uid', $uid);

            // Firebaseユーザー情報を取得（displayName/email は null の可能性あり）
            $firebaseUser = $auth->getUser($uid);

            $displayName = $firebaseUser->displayName ?? null;
            $email = $firebaseUser->email ?? null;

            // null で上書きしない（名前が消える事故を防ぐ）
            $updates = [];
            if (is_string($displayName) && trim($displayName) !== '') {
                $updates['name'] = trim($displayName);
            }
            if (is_string($email) && trim($email) !== '') {
                $updates['email'] = trim($email);
            }

            // users レコードが無ければ作る（updates が空でも firebase_uid は保存される）
            User::updateOrCreate(
                ['firebase_uid' => $uid],
                $updates
            );
        } catch (FailedToVerifyToken | InvalidToken $e) {
            return response()->json(['message' => 'Invalid token'], Response::HTTP_UNAUTHORIZED);
        } catch (UserNotFound $e) {
            return response()->json(['message' => 'Firebase user not found'], Response::HTTP_UNAUTHORIZED);
        } catch (\Throwable $e) {
            return response()->json([
                'message' => 'Firebase auth error',
                'error' => $e->getMessage(),
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }

        return $next($request);
    }
}
