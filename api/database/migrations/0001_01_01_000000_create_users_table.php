<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('users', function (Blueprint $table) {
            $table->id();

            // 🔑 Firebase Authentication の UID（posts/comments/likes.user_id の参照先）
            $table->string('firebase_uid', 255)
                ->unique()
                ->comment('Firebase UID');

            // 表示名（Firebase displayName のスナップショット元）
            $table->string('name', 255)
                ->nullable()
                ->comment('display name');

            // ✅ Firebase email（middleware が同期するので追加）
            $table->string('email', 255)
                ->nullable()
                ->comment('Firebase email');

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('users');
    }
};
