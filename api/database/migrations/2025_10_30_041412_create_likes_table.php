<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('likes', function (Blueprint $table) {
            $table->id();

            // posts.id
            $table->foreignId('post_id')
                ->constrained()
                ->cascadeOnDelete();

            // users.firebase_uid
            $table->string('user_id', 255)->comment('Firebase UID');

            $table->timestamps();

            // 同一ユーザーの重複いいね防止
            $table->unique(['post_id', 'user_id']);

            // マイいいね一覧等の検索用
            $table->index('user_id');

            // FK（文字列）
            $table->foreign('user_id')
                ->references('firebase_uid')
                ->on('users')
                ->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('likes');
    }
};
