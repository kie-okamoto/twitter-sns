<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('posts', function (Blueprint $table) {
            $table->id();

            // users.firebase_uid と完全一致させる（型・長さ）
            $table->string('user_id', 255)->comment('Firebase UID');

            // 表示名スナップショット（未設定ユーザーもいるので nullable）
            $table->string('user_name', 255)->nullable()->comment('display name snapshot');

            // メッセージ本文
            $table->text('content');

            $table->timestamps();

            // FK（文字列）
            $table->foreign('user_id')
                ->references('firebase_uid')->on('users')
                ->cascadeOnDelete();

            // 一覧や削除判定
            $table->index('user_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('posts');
    }
};
