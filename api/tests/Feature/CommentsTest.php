<?php

namespace Tests\Feature;

use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use App\Http\Middleware\TestFirebaseToken;
use App\Models\User;
use App\Models\Post;
use App\Models\Comment;

class CommentsTest extends TestCase
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

  public function test_get_comments_returns_latest_first(): void
  {
    $this->seedUser('test-uid', 'Taro');
    $post = $this->seedPost('test-uid', 'Taro');

    // ✅ created_at を “秒まで” 明確にズラす（SQLiteの並び揺れ対策）
    $c1Time = now()->subSeconds(10);
    $c2Time = now()->subSeconds(1);

    $c1 = Comment::create([
      'post_id' => $post->id,
      'user_id' => 'test-uid',
      'user_name' => 'Taro',
      'content' => 'old',
      'created_at' => $c1Time,
      'updated_at' => $c1Time,
    ]);

    $c2 = Comment::create([
      'post_id' => $post->id,
      'user_id' => 'test-uid',
      'user_name' => 'Taro',
      'content' => 'new',
      'created_at' => $c2Time,
      'updated_at' => $c2Time,
    ]);

    $res = $this->getJson("/api/posts/{$post->id}/comments");

    $res->assertOk();

    $json = $res->json();
    $this->assertIsArray($json);

    // 最新(created_atが新しい)が先頭
    $this->assertSame($c2->id, $json[0]['id']);
    $this->assertSame($c1->id, $json[1]['id']);
  }


  public function test_store_comment_success_and_snapshots_user_name(): void
  {
    // コメント投稿者（firebase_uid = test-uid）
    $this->seedUser('test-uid', 'Taro');
    $post = $this->seedPost('test-uid', 'Taro');

    $payload = [
      'post_id' => $post->id,
      'content' => 'nice post',
    ];

    $res = $this->withHeaders($this->authHeaders())
      ->postJson('/api/comments', $payload);

    $res->assertStatus(201);
    $res->assertJsonFragment([
      'post_id' => $post->id,
      'user_id' => 'test-uid',
      'user_name' => 'Taro',      // ✅ users.name から取得される
      'content' => 'nice post',
    ]);

    $this->assertDatabaseHas('comments', [
      'post_id' => $post->id,
      'user_id' => 'test-uid',
      'user_name' => 'Taro',
      'content' => 'nice post',
    ]);
  }

  public function test_store_comment_over_120_chars_returns_422(): void
  {
    $this->seedUser('test-uid', 'Taro');
    $post = $this->seedPost('test-uid', 'Taro');

    $payload = [
      'post_id' => $post->id,
      'content' => str_repeat('a', 121),
    ];

    $res = $this->withHeaders($this->authHeaders())
      ->postJson('/api/comments', $payload);

    $res->assertStatus(422);
    $res->assertJsonValidationErrors(['content']);
  }

  public function test_store_comment_requires_post_id(): void
  {
    $this->seedUser('test-uid', 'Taro');

    $res = $this->withHeaders($this->authHeaders())
      ->postJson('/api/comments', [
        'content' => 'hi',
      ]);

    $res->assertStatus(422);
    $res->assertJsonValidationErrors(['post_id']);
  }

  public function test_store_comment_requires_existing_post(): void
  {
    $this->seedUser('test-uid', 'Taro');

    $res = $this->withHeaders($this->authHeaders())
      ->postJson('/api/comments', [
        'post_id' => 999999,
        'content' => 'hi',
      ]);

    $res->assertStatus(422);
    $res->assertJsonValidationErrors(['post_id']);
  }
}
