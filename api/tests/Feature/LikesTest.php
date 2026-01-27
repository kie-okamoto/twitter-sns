<?php

namespace Tests\Feature;

use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use App\Http\Middleware\TestFirebaseToken;
use App\Models\User;
use App\Models\Post;
use App\Models\Like;

class LikesTest extends TestCase
{
  use RefreshDatabase;

  protected function setUp(): void
  {
    parent::setUp();

    $router = $this->app['router'];

    // ✅ alias(firebase) を使っている場合に備えて差し替え
    $router->aliasMiddleware('firebase', TestFirebaseToken::class);

    // ✅ ルートで VerifyFirebaseToken::class を直書きしている場合に備えて差し替え
    $this->app->bind(
      \App\Http\Middleware\VerifyFirebaseToken::class,
      TestFirebaseToken::class
    );

    // ✅ optional 版も保険で差し替え
    if (class_exists(\App\Http\Middleware\VerifyFirebaseTokenOptional::class)) {
      $this->app->bind(
        \App\Http\Middleware\VerifyFirebaseTokenOptional::class,
        TestFirebaseToken::class
      );
    }
  }

  private function seedUser(
    string $uid = 'test-uid',
    string $name = 'Taro',
    string $email = 'taro@example.com'
  ): User {
    return User::create([
      'firebase_uid' => $uid,
      'name' => $name,
      'email' => $email,
    ]);
  }

  private function seedPost(string $uid = 'test-uid', string $userName = 'Taro', string $content = 'hello'): Post
  {
    return Post::create([
      'user_id' => $uid,
      'user_name' => $userName,
      'content' => $content,
    ]);
  }

  private function authHeaders(?string $uid = null): array
  {
    $headers = [
      'Authorization' => 'Bearer dummy',
    ];

    if ($uid !== null) {
      $headers['X-Test-Firebase-Uid'] = $uid;
    }

    return $headers;
  }

  public function test_like_store_adds_like_and_returns_count(): void
  {
    $this->seedUser('test-uid', 'Taro');
    $post = $this->seedPost('test-uid', 'Taro');

    $res = $this->withHeaders($this->authHeaders())
      ->postJson('/api/likes', [
        'post_id' => $post->id,
      ]);

    $res->assertOk();
    $res->assertJson([
      'liked' => true,
      'likes_count' => 1,
    ]);

    $this->assertDatabaseHas('likes', [
      'user_id' => 'test-uid',
      'post_id' => $post->id,
    ]);
  }

  public function test_like_store_is_idempotent_duplicate_like_still_ok(): void
  {
    $this->seedUser('test-uid', 'Taro');
    $post = $this->seedPost('test-uid', 'Taro');

    // 1回目
    $this->withHeaders($this->authHeaders())
      ->postJson('/api/likes', ['post_id' => $post->id])
      ->assertOk()
      ->assertJson(['liked' => true, 'likes_count' => 1]);

    // 2回目（重複＝OKで握りつぶす仕様）
    $this->withHeaders($this->authHeaders())
      ->postJson('/api/likes', ['post_id' => $post->id])
      ->assertOk()
      ->assertJson(['liked' => true, 'likes_count' => 1]);

    // DB的にも1件のまま
    $this->assertSame(1, Like::where('user_id', 'test-uid')->where('post_id', $post->id)->count());
  }

  public function test_like_destroy_removes_like_and_returns_count(): void
  {
    $this->seedUser('test-uid', 'Taro');
    $post = $this->seedPost('test-uid', 'Taro');

    // 先にいいね作成
    Like::create([
      'user_id' => 'test-uid',
      'post_id' => $post->id,
    ]);

    $res = $this->withHeaders($this->authHeaders())
      ->deleteJson("/api/likes/{$post->id}");

    $res->assertOk();
    $res->assertJson([
      'liked' => false,
      'likes_count' => 0,
    ]);

    $this->assertDatabaseMissing('likes', [
      'user_id' => 'test-uid',
      'post_id' => $post->id,
    ]);
  }

  public function test_like_store_requires_post_id(): void
  {
    $this->seedUser('test-uid', 'Taro');

    $res = $this->withHeaders($this->authHeaders())
      ->postJson('/api/likes', []);

    $res->assertStatus(422);
    $res->assertJsonValidationErrors(['post_id']);
  }

  public function test_like_store_requires_existing_post(): void
  {
    $this->seedUser('test-uid', 'Taro');

    $res = $this->withHeaders($this->authHeaders())
      ->postJson('/api/likes', ['post_id' => 999999]);

    $res->assertStatus(422);
    $res->assertJsonValidationErrors(['post_id']);
  }
}
