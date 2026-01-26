<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('comments', function (Blueprint $table) {
            $table->id();

            // posts.id
            $table->foreignId('post_id')
                ->constrained()
                ->cascadeOnDelete();

            // users.firebase_uid と完全一致
            $table->string('user_id', 255)->comment('Firebase UID');

            // 表示名スナップショット（任意）
            $table->string('user_name', 255)->nullable()->comment('display name snapshot at comment time');

            // コメント本文（必須）
            $table->text('content')->comment('comment content');

            $table->timestamps();

            // indexes（よく使う検索条件）
            $table->index('post_id');
            $table->index('user_id');

            // FK（文字列）
            $table->foreign('user_id')
                ->references('firebase_uid')
                ->on('users')
                ->cascadeOnDelete();

            // ※必要なら重複防止（同一ユーザーが同一投稿に全く同じ文章を連投…などを禁止したい場合のみ）
            // $table->unique(['post_id', 'user_id', 'content']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('comments');
    }
};
