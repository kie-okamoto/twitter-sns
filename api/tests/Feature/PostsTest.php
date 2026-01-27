<?php

namespace Tests\Feature;

use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use App\Http\Middleware\TestFirebaseToken;
use App\Models\User;
use App\Models\Post;

class PostsTest extends TestCase
{
  use RefreshDatabase;

  protected function setUp(): void
  {
    parent::setUp();

    $router = $this->app['router'];

    // ✅ alias(firebase) を使っている場合に備えて差し替え
    $router->aliasMiddleware('firebase', TestFirebaseToken::class);

    // ✅ ルートで VerifyFirebaseToken::class を直書きしている場合に備えて差し替え（重要）
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

  private function authHeaders(?string $uid = null): array
  {
    // ✅ POST/DELETE が Authorization 必須な実装でも 401 を回避
    $headers = [
      'Authorization' => 'Bearer dummy',
    ];

    // ✅ 他人ユーザー再現用（TestFirebaseToken 側で読む）
    if ($uid !== null) {
      $headers['X-Test-Firebase-Uid'] = $uid;
    }

    return $headers;
  }

  public function test_get_posts_returns_200(): void
  {
    $this->seedUser('test-uid', 'Taro');

    Post::create([
      'user_id' => 'test-uid',
      'user_name' => 'Taro',
      'content' => 'hello',
    ]);

    // GET はヘッダ無しでも通っているのでそのままでOK（付けてもOK）
    $res = $this->getJson('/api/posts');

    $res->assertOk();
    $res->assertJsonFragment(['content' => 'hello']);
  }

  public function test_store_post_success(): void
  {
    $this->seedUser('test-uid', 'Taro');

    $payload = ['content' => 'new post'];

    $res = $this->withHeaders($this->authHeaders())
      ->postJson('/api/posts', $payload);

    // 201/200どちらでもOK
    $res->assertStatus(in_array($res->getStatusCode(), [200, 201], true) ? $res->getStatusCode() : 201);

    $this->assertDatabaseHas('posts', [
      'content' => 'new post',
      'user_id' => 'test-uid',
    ]);
  }

  public function test_store_post_over_120_chars_returns_422(): void
  {
    $this->seedUser('test-uid', 'Taro');

    $payload = ['content' => str_repeat('a', 121)];

    $res = $this->withHeaders($this->authHeaders())
      ->postJson('/api/posts', $payload);

    $res->assertStatus(422);
    $res->assertJsonValidationErrors(['content']);
  }

  public function test_destroy_post_owner_can_delete(): void
  {
    $this->seedUser('test-uid', 'Taro');

    $post = Post::create([
      'user_id' => 'test-uid',
      'user_name' => 'Taro',
      'content' => 'to be deleted',
    ]);

    $res = $this->withHeaders($this->authHeaders())
      ->deleteJson("/api/posts/{$post->id}");

    // 200/204どちらでもOK
    $res->assertStatus(in_array($res->getStatusCode(), [200, 204], true) ? $res->getStatusCode() : 204);

    $this->assertDatabaseMissing('posts', [
      'id' => $post->id,
    ]);
  }

  public function test_destroy_post_other_user_cannot_delete(): void
  {
    // 投稿者
    $this->seedUser('owner-uid', 'Owner', 'owner@example.com');

    $post = Post::create([
      'user_id' => 'owner-uid',
      'user_name' => 'Owner',
      'content' => 'protected',
    ]);

    // 他人として削除リクエスト
    $res = $this->withHeaders($this->authHeaders('other-uid'))
      ->deleteJson("/api/posts/{$post->id}");

    $res->assertStatus(403);

    $this->assertDatabaseHas('posts', [
      'id' => $post->id,
    ]);
  }
}
